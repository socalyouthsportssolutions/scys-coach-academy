<?php
/** Private operational records. Never posts, media, startup content or learner state. */
defined( 'ABSPATH' ) || exit;
final class SCYS_Hub_Mentoring {
 const STATUSES = array( 'requested', 'arranging', 'confirmed', 'delivered', 'follow-up due', 'closed', 'postponed', 'cancelled' );
 public static function table() { global $wpdb; return $wpdb->prefix . 'scys_mentoring'; }
 public static function install() {
  if ( get_option( 'scys_mentoring_schema' ) === '1' ) { return; }
  global $wpdb;
  require_once ABSPATH . 'wp-admin/includes/upgrade.php';
  $table = self::table(); $charset = $wpdb->get_charset_collate();
  dbDelta( "CREATE TABLE $table (
id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
coach_id bigint(20) unsigned NOT NULL,
create_key varchar(96) NOT NULL,
version bigint(20) unsigned NOT NULL DEFAULT 1,
payload longtext NOT NULL,
PRIMARY KEY  (id),
UNIQUE KEY create_key (create_key),
KEY coach_id (coach_id)
) $charset;" );
  if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table ) { update_option( 'scys_mentoring_schema', '1', false ); }
 }
 public static function init() {
  add_action( 'init', array( __CLASS__, 'install' ), 21 );
  add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
  add_action( 'rest_post_dispatch', array( __CLASS__, 'no_store' ), 10, 3 );
  add_action( 'admin_menu', function() { add_users_page( 'Mentoring pilot settings', 'Mentoring pilot settings', 'manage_options', 'scys-mentoring-settings', array( __CLASS__, 'settings' ) ); } );
 }
 public static function no_store( $response, $server, $request ) {
  if ( str_starts_with( $request->get_route(), '/scys-hub/v2/support' ) ) { $response->header( 'Cache-Control', 'private, no-store, max-age=0' ); $response->header( 'X-Robots-Tag', 'noindex, nofollow' ); }
  return $response;
 }
 public static function settings() {
  if ( ! current_user_can( 'manage_options' ) ) { return; }
  if ( isset( $_POST['scys_pilot_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['scys_pilot_nonce'] ) ), 'scys_pilot' ) ) {
   $url = esc_url_raw( wp_unslash( $_POST['reporting_url'] ?? '' ), array( 'https' ) );
   $approval = sanitize_textarea_field( wp_unslash( $_POST['approval'] ?? '' ) );
   update_option( 'scys_mentoring_policy', array( 'reportingUrl' => $url, 'approval' => $approval, 'enabled' => ! empty( $_POST['enabled'] ) && $url && strlen( $approval ) >= 20, 'by' => get_current_user_id(), 'at' => gmdate( 'c' ) ), false );
   echo '<div class="notice notice-success"><p>Pilot settings saved.</p></div>';
  }
  $p = self::policy();
  echo '<div class="wrap"><h1>Mentoring pilot settings</h1><p>Collection is disabled by default. Before enabling: obtain coaching-lead approval and technical/privacy acceptance, confirm the restricted reporting procedure, retention/deletion and backup handling, and coordinator capacity. This switch does not provide or replace those decisions.</p><form method="post">';
  wp_nonce_field( 'scys_pilot', 'scys_pilot_nonce' );
  echo '<p><label>Approved reporting procedure (HTTPS)<br><input class="widefat" type="url" name="reporting_url" value="' . esc_attr( $p['reportingUrl'] ) . '"></label></p><p><label>Approval reference and agreed retention/deletion/backup procedure<br><textarea class="widefat" name="approval" rows="5">' . esc_textarea( $p['approval'] ?? '' ) . '</textarea></label></p><p><label><input type="checkbox" name="enabled" value="1" ' . checked( $p['enabled'], true, false ) . '> Required approvals and procedures are recorded; enable collection</label></p><button class="button button-primary">Save settings</button></form></div>';
 }
 public static function policy() { return wp_parse_args( (array) get_option( 'scys_mentoring_policy', array() ), array( 'enabled' => false, 'reportingUrl' => '', 'approval' => '' ) ); }
 public static function error( $message, $status = 400 ) { return new WP_Error( 'scys_support', $message, array( 'status' => $status ) ); }
 public static function access() { return SCYS_Coach_Hub::access(); }
 public static function write_access( $request ) {
  $access = self::access(); if ( is_wp_error( $access ) ) { return $access; }
  // Explicit nonce also protects direct mutation calls; cookie authentication is supplied by WP REST.
  return wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' ) ? true : self::error( 'Reload and sign in before saving.', 403 );
 }
 public static function routes() {
  foreach ( array( '/support' => 'listing', '/support/(?P<id>[0-9]+)' => 'detail' ) as $route => $method ) {
   register_rest_route( 'scys-hub/v2', $route, array( 'methods' => 'GET', 'callback' => array( __CLASS__, $method ), 'permission_callback' => array( __CLASS__, 'access' ) ) );
  }
  register_rest_route( 'scys-hub/v2', '/support/save', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'save' ), 'permission_callback' => array( __CLASS__, 'write_access' ) ) );
 }
 public static function mentor( $coach ) { $id = (int) get_user_meta( $coach, '_scys_mentor_id', true ); return $id && user_can( $id, SCYS_Coach_Hub::CAP ) && user_can( $id, SCYS_Coach_Hub::REVIEW ) ? $id : 0; }
 public static function can_read( $coach ) {
  return current_user_can( SCYS_Coach_Hub::CAP ) && ( get_current_user_id() === (int) $coach || current_user_can( 'manage_options' ) || ( current_user_can( SCYS_Coach_Hub::REVIEW ) && self::mentor( $coach ) === get_current_user_id() ) );
 }
 public static function row( $id ) { global $wpdb; return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) ); }
 public static function output( $row ) {
  $data = json_decode( $row->payload, true ); $data['id'] = (int) $row->id; $data['version'] = (int) $row->version; $data['coachId'] = (int) $row->coach_id;
  $data['assignedMentor'] = self::mentor( $row->coach_id );
  $coach = get_userdata( $row->coach_id ); $mentor = get_userdata( $data['assignedMentor'] );
  $data['coachName'] = $coach ? $coach->display_name : 'Removed adult account'; $data['mentorName'] = $mentor ? $mentor->display_name : 'Not assigned';
  if ( (int) ( $data['mentorAcceptedBy'] ?? 0 ) !== $data['assignedMentor'] ) { $data['mentorAcceptedBy'] = 0; if ( $data['status'] === 'confirmed' ) { $data['status'] = 'arranging'; } }
  unset( $data['receipts'] );
  return $data;
 }
 public static function listing( $request ) {
  global $wpdb; $page = max( 1, absint( $request->get_param( 'page' ) ) ); $limit = 50;
  $table = self::table(); $uid = get_current_user_id();
  $where = current_user_can( 'manage_options' ) ? '1=1' : $wpdb->prepare( 'coach_id = %d', $uid );
  if ( ! current_user_can( 'manage_options' ) && current_user_can( SCYS_Coach_Hub::REVIEW ) ) {
   $where .= $wpdb->prepare( " OR coach_id IN (SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = '_scys_mentor_id' AND meta_value = %s)", (string) $uid );
  }
  // Filter access before serialisation too, including revoked mentor capabilities.
  $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE ($where) ORDER BY id DESC LIMIT %d OFFSET %d", $limit + 1, ( $page - 1 ) * $limit ) );
  $more = count( $rows ) > $limit; $rows = array_slice( $rows, 0, $limit ); $records = array();
  foreach ( $rows as $row ) { if ( ! self::can_read( $row->coach_id ) ) { continue; } $d = self::output( $row ); $pending = array(); $upcoming = array();
   foreach ( $d['visits'] as $v ) { if ( ! empty( $v['followUpDate'] ) && ( empty( $v['result'] ) || ( $v['followUpState'] ?? 'complete' ) === 'open' ) ) { $pending[] = array( 'visitId' => $v['id'], 'date' => $v['followUpDate'], 'owner' => $v['followUpOwner'] ?? '', 'overdue' => $v['followUpDate'] < current_time( 'Y-m-d' ) ); } if ( ! empty( $v['date'] ) && empty( $v['attendanceBy'] ) ) { $upcoming[] = array( 'date' => $v['date'], 'type' => $v['type'] ); } }
   $records[] = array_intersect_key( $d, array_flip( array( 'id', 'coachId', 'coachName', 'mentorName', 'status', 'seasonTeam', 'ageGroup', 'proposedAt', 'type', 'version' ) ) ) + array( 'followUps' => $pending, 'upcoming' => $upcoming );
  }
  $p = self::policy();
  return SCYS_Coach_Hub::private_response( array( 'records' => $records, 'page' => $page, 'hasMore' => $more, 'enabled' => (bool) $p['enabled'], 'reportingUrl' => $p['reportingUrl'], 'canCoordinate' => current_user_can( 'manage_options' ) ) );
 }
 public static function detail( $request ) {
  $row = self::row( absint( $request['id'] ) );
  return $row && self::can_read( $row->coach_id ) ? SCYS_Coach_Hub::private_response( self::output( $row ) ) : self::error( 'Record unavailable for this account.', 404 );
 }
 public static function text( $input, $key, $max = 1500 ) {
  if ( ! isset( $input[ $key ] ) ) { return ''; }
  if ( ! is_string( $input[ $key ] ) || strlen( $input[ $key ] ) > $max ) { throw new InvalidArgumentException( 'Check ' . $key . ': text is too long or invalid.' ); }
  return sanitize_textarea_field( $input[ $key ] );
 }
 public static function date( $value, $time = false ) {
  if ( '' === $value ) { return ''; }
  $format = $time ? 'Y-m-d\TH:i' : 'Y-m-d'; $dt = DateTimeImmutable::createFromFormat( '!' . $format, $value );
  if ( ! $dt || $dt->format( $format ) !== $value ) { throw new InvalidArgumentException( 'Use a valid date/time in the displayed format.' ); }
  return $value;
 }
 public static function pending( $d ) { foreach ( $d['visits'] as $v ) { if ( ! empty( $v['nextAction'] ) && ( empty( $v['result'] ) || ( $v['followUpState'] ?? 'complete' ) === 'open' ) ) { return true; } } return false; }
 public static function delivered( $d ) { foreach ( $d['visits'] as $v ) { if ( ! empty( $v['attendanceBy'] ) ) { return true; } } return false; }
 public static function save( $request ) {
  $access = self::write_access( $request ); if ( is_wp_error( $access ) ) { return $access; }
  if ( ! self::policy()['enabled'] ) { return self::error( 'Pilot collection is disabled pending content, privacy and reporting-procedure acceptance.', 403 ); }
  $in = $request->get_json_params(); if ( ! is_array( $in ) || strlen( wp_json_encode( $in ) ) > 22000 ) { return self::error( 'Invalid or oversized record.' ); }
  $uid = get_current_user_id(); $id = absint( $in['id'] ?? 0 ); $row = $id ? self::row( $id ) : null;
  if ( $id && ( ! $row || ! self::can_read( $row->coach_id ) ) ) { return self::error( 'Record unavailable for this account.', 404 ); }
  $coach = $row ? (int) $row->coach_id : $uid;
  if ( isset( $in['coachId'] ) && ( ! is_int( $in['coachId'] ) || $in['coachId'] !== $coach ) ) { return self::error( 'Coach ownership cannot be changed.', 403 ); }
  $token = $in['token'] ?? ''; if ( ! is_string( $token ) || ! preg_match( '/^[a-zA-Z0-9-]{16,64}$/D', $token ) ) { return self::error( 'A valid submission token is required.' ); }
  global $wpdb; $table = self::table();
  $create_key = $coach . ':' . $token;
  if ( ! $row ) { $prior = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE create_key = %s", $create_key ) ); if ( $prior ) { $row = $prior; $id = (int) $row->id; } }
  $d = $row ? json_decode( $row->payload, true ) : array( 'status' => 'requested', 'visits' => array(), 'history' => array(), 'receipts' => array(), 'coachAgreedBy' => 0, 'mentorAcceptedBy' => 0, 'createdBy' => $uid, 'createdAt' => gmdate( 'c' ) );
  $hash_input = $in; unset( $hash_input['token'] ); $hash = hash( 'sha256', wp_json_encode( $hash_input ) );
  if ( isset( $d['receipts'][ $token ] ) ) { return $d['receipts'][ $token ] === $hash ? SCYS_Coach_Hub::private_response( self::output( $row ) ) : self::error( 'Submission token already used for different content.', 409 ); }
  if ( $row && ( ! isset( $in['version'] ) || ! is_int( $in['version'] ) || $in['version'] !== (int) $row->version ) ) { return self::error( 'Another save changed this record. Keep your draft, reopen the current record and reconcile before saving.', 409 ); }
  $before = $d; $mentor = self::mentor( $coach );
  if ( (int) $d['mentorAcceptedBy'] !== $mentor ) { $d['mentorAcceptedBy'] = 0; if ( $d['status'] === 'confirmed' ) { $d['status'] = 'arranging'; } }
  try {
   if ( ( $in['action'] ?? 'request' ) === 'request' ) {
    $fields = $in['fields'] ?? array(); if ( ! is_array( $fields ) ) { throw new InvalidArgumentException( 'Invalid request fields.' ); }
    foreach ( array( 'seasonTeam', 'ageGroup', 'topic', 'type', 'proposedAt', 'location', 'arrangement' ) as $key ) { $d[ $key ] = self::text( $fields, $key, $key === 'topic' || $key === 'arrangement' ? 1500 : 200 ); }
    foreach ( array( 'seasonTeam', 'ageGroup', 'topic', 'location' ) as $key ) { if ( ! $d[ $key ] ) { throw new InvalidArgumentException( 'Complete ' . $key . '.' ); } }
    if ( ! in_array( $d['type'], array( 'practice', 'game' ), true ) ) { throw new InvalidArgumentException( 'Choose practice or game support.' ); }
    self::date( $d['proposedAt'], true );
    $changed = false; foreach ( array( 'seasonTeam', 'ageGroup', 'topic', 'type', 'proposedAt', 'location', 'arrangement' ) as $key ) { if ( ( $before[ $key ] ?? '' ) !== $d[ $key ] ) { $changed = true; } }
    if ( $changed ) { $d['coachAgreedBy'] = 0; $d['mentorAcceptedBy'] = 0; }
    if ( ! empty( $in['agree'] ) && $uid === $coach ) { $d['coachAgreedBy'] = $uid; $d['coachAgreedAt'] = gmdate( 'c' ); }
    if ( ! empty( $in['accept'] ) && $uid === $mentor ) { $d['mentorAcceptedBy'] = $uid; $d['mentorAcceptedAt'] = gmdate( 'c' ); }
    $status = $fields['status'] ?? 'requested'; if ( ! in_array( $status, self::STATUSES, true ) ) { throw new InvalidArgumentException( 'Choose a supported status.' ); }
    $transitions = array(
     'requested' => array( 'requested', 'arranging', 'confirmed', 'postponed', 'cancelled' ),
     'arranging' => array( 'arranging', 'confirmed', 'postponed', 'cancelled' ),
     'confirmed' => array( 'confirmed', 'arranging', 'delivered', 'follow-up due', 'postponed', 'cancelled' ),
     'delivered' => array( 'delivered', 'arranging', 'follow-up due', 'closed' ),
     'follow-up due' => array( 'follow-up due', 'arranging', 'delivered', 'closed', 'postponed' ),
     'postponed' => array( 'postponed', 'arranging', 'confirmed', 'delivered', 'follow-up due', 'cancelled', 'closed' ),
     'cancelled' => array( 'cancelled', 'arranging' ),
     'closed' => array( 'closed', 'arranging' ),
    );
    if ( ! in_array( $status, $transitions[ $d['status'] ], true ) ) { throw new InvalidArgumentException( 'Reopen or arrange the request before moving to that status.' ); }
    if ( $status === 'confirmed' && ( ! $mentor || (int) $d['mentorAcceptedBy'] !== $mentor || (int) $d['coachAgreedBy'] !== $coach || ! $d['proposedAt'] || ! $d['arrangement'] ) ) { throw new InvalidArgumentException( 'Confirmation needs the coach’s agreement, current mentor’s acceptance, time and recorded arrangement.' ); }
    if ( in_array( $status, array( 'delivered', 'follow-up due', 'closed' ), true ) && ! self::delivered( $d ) ) { throw new InvalidArgumentException( 'Delivery needs a coach or mentor confirmation that a visit happened.' ); }
    if ( in_array( $status, array( 'closed', 'cancelled' ), true ) && self::pending( $d ) ) { throw new InvalidArgumentException( 'Resolve outstanding actions with a result or closure reason first.' ); }
    if ( $status === 'closed' && ! self::text( $fields, 'closureReason' ) ) { throw new InvalidArgumentException( 'Add a closure reason.' ); }
    $d['closureReason'] = self::text( $fields, 'closureReason' ); $d['status'] = $status;
   } elseif ( ( $in['action'] ?? '' ) === 'visit' && $row ) {
    $fields = $in['fields'] ?? array(); if ( ! is_array( $fields ) ) { throw new InvalidArgumentException( 'Invalid visit fields.' ); }
    $vid = $in['visitId'] ?? wp_generate_uuid4(); if ( ! is_string( $vid ) || ! preg_match( '/^[a-zA-Z0-9-]{16,64}$/D', $vid ) ) { throw new InvalidArgumentException( 'Invalid visit reference.' ); }
    $old = $d['visits'][ $vid ] ?? null; if ( ! $old && isset( $in['visitId'] ) ) { throw new InvalidArgumentException( 'Visit no longer exists. Reopen the request.' ); }
    $v = $old ?? array( 'id' => $vid, 'createdBy' => $uid, 'createdAt' => gmdate( 'c' ), 'mentorId' => $mentor );
    if ( empty( $v['attendanceBy'] ) ) { $v['mentorId'] = $mentor; }
    foreach ( array( 'date', 'type', 'focus', 'observation1', 'observation2', 'strength', 'nextAction', 'resourceId', 'followUpOwner', 'followUpDate', 'result', 'followUpState' ) as $key ) { $v[ $key ] = self::text( $fields, $key ); }
    self::date( $v['date'], true ); self::date( $v['followUpDate'] );
    $v['followUpState'] = $v['followUpState'] ?: 'open';
    if ( ! in_array( $v['followUpState'], array( 'open', 'complete' ), true ) || ( $v['followUpState'] === 'complete' && ! $v['result'] ) ) { throw new InvalidArgumentException( 'Keep the follow-up open, or record a result/closure reason before completing it.' ); }
    if ( $v['type'] && ! in_array( $v['type'], array( 'practice', 'game' ), true ) ) { throw new InvalidArgumentException( 'Choose practice or game.' ); }
    if ( $v['resourceId'] && ! SCYS_Coach_Hub::item( 'resources', $v['resourceId'] ) && ! SCYS_Coach_Hub::item( 'lessons', $v['resourceId'] ) ) { throw new InvalidArgumentException( 'Choose an available Hub resource.' ); }
    if ( $v['followUpOwner'] && ! in_array( $v['followUpOwner'], array( 'coach', 'mentor', 'coordinator' ), true ) ) { throw new InvalidArgumentException( 'Choose a follow-up owner.' ); }
    $v['draft'] = empty( $in['complete'] ) && empty( $old['attendanceBy'] );
    if ( ! $v['draft'] ) {
     foreach ( array( 'date', 'type', 'focus', 'observation1', 'observation2', 'strength', 'nextAction', 'resourceId', 'followUpOwner', 'followUpDate' ) as $key ) { if ( ! $v[ $key ] ) { throw new InvalidArgumentException( 'Complete ' . $key . ' or save a draft.' ); } }
     if ( $v['date'] > current_time( 'Y-m-d\TH:i' ) ) { throw new InvalidArgumentException( 'A future visit cannot be delivered.' ); }
     if ( ! $v['mentorId'] ) { throw new InvalidArgumentException( 'Record a visit with an assigned mentor.' ); }
     if ( empty( $old['attendanceBy'] ) ) { if ( empty( $in['confirmAttendance'] ) || ! in_array( $uid, array( $coach, $mentor ), true ) ) { throw new InvalidArgumentException( 'The coach or current mentor must explicitly confirm attendance.' ); } $v['attendanceBy'] = $uid; $v['attendanceAt'] = gmdate( 'c' ); }
    }
    if ( $old && ! empty( $old['attendanceBy'] ) && ( $v['date'] !== $old['date'] || $v['type'] !== $old['type'] ) ) { unset( $v['attendanceBy'], $v['attendanceAt'] ); $v['draft'] = true; }
    $v['updatedBy'] = $uid; $v['updatedAt'] = gmdate( 'c' ); $d['visits'][ $vid ] = $v;
    if ( self::delivered( $d ) ) { $d['status'] = self::pending( $d ) ? 'follow-up due' : 'delivered'; }
    elseif ( in_array( $d['status'], array( 'delivered', 'follow-up due', 'closed' ), true ) ) { $d['status'] = 'arranging'; }
   } else { throw new InvalidArgumentException( 'Choose a request or linked visit action.' ); }
   if ( count( $d['visits'] ) > 100 ) { throw new InvalidArgumentException( 'Start another request for additional visits.' ); }
  } catch ( InvalidArgumentException $e ) { return self::error( $e->getMessage() ); }
  $changed_fields = array(); foreach ( $d as $key => $value ) { if ( ( $before[ $key ] ?? null ) !== $value ) { $changed_fields[] = $key; } }
  $d['updatedBy'] = $uid; $d['updatedAt'] = gmdate( 'c' );
  $d['history'][] = array( 'by' => $uid, 'at' => $d['updatedAt'], 'action' => $in['action'] ?? 'request', 'visitId' => $vid ?? null, 'fields' => $changed_fields );
  $d['receipts'][ $token ] = $hash;
  // Bounded retry window; old retries fail the version check rather than overwrite.
  $d['receipts'] = array_slice( $d['receipts'], -100, null, true );
  if ( $row ) {
   $saved = $wpdb->query( $wpdb->prepare( "UPDATE $table SET payload = %s, version = version + 1 WHERE id = %d AND version = %d", wp_json_encode( $d ), $id, (int) $row->version ) );
   if ( $saved !== 1 ) { return self::error( 'Save not confirmed. Keep your draft; reload the current record and reconcile changes.', 409 ); }
  } else {
   $saved = $wpdb->insert( $table, array( 'coach_id' => $coach, 'create_key' => $create_key, 'version' => 1, 'payload' => wp_json_encode( $d ) ), array( '%d', '%s', '%d', '%s' ) );
   if ( ! $saved ) { $prior = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE create_key = %s", $create_key ) ); if ( $prior && ( json_decode( $prior->payload, true )['receipts'][ $token ] ?? '' ) === $hash ) { return SCYS_Coach_Hub::private_response( self::output( $prior ) ); } return self::error( 'Request could not be saved. Retry the same draft.', 409 ); }
   $id = (int) $wpdb->insert_id;
  }
  return SCYS_Coach_Hub::private_response( self::output( self::row( $id ) ) );
 }
}
SCYS_Hub_Mentoring::init();
