<?php
/**
 * Plugin Name: SCYS Coach Education Hub
 * Description: Private coach and volunteer learning, resources and supervised practical development.
 * Version: 0.3.1
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * Author: SoCal Youth Sports
 * License: GPL-2.0-or-later
 * Text Domain: scys-coach-hub
 */
defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/includes/content-store.php';
require_once __DIR__ . '/includes/mentoring-store.php';
require_once __DIR__ . '/includes/privacy.php';

final class SCYS_Coach_Hub {
 const VERSION = '0.3.1';
 const META = '_scys_hub_state';
 const CAP = 'scys_access_hub';
 const REVIEW = 'scys_review_hub';

 public static function activate( $network_wide = false ) {
  if ( $network_wide ) { wp_die( 'Activate SCYS Coach Hub on an individual site. Network-wide activation is not supported in this version.' ); }
  add_role( 'scys_coach', 'SCYS Coach', array( 'read' => true, self::CAP => true ) );
  add_role( 'scys_volunteer', 'SCYS Volunteer', array( 'read' => true, self::CAP => true ) );
  add_role( 'scys_mentor', 'SCYS Mentor', array( 'read' => true, self::CAP => true, self::REVIEW => true ) );
  $admin = get_role( 'administrator' );
  if ( $admin ) { $admin->add_cap( self::CAP ); $admin->add_cap( self::REVIEW ); }
  self::content_types();
  SCYS_Hub_Mentoring::install();
  self::seed_editable_content();
 }
 public static function init() {
  add_shortcode( 'scys_coach_hub', array( __CLASS__, 'shortcode' ) );
  add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
  add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
  add_action( 'show_user_profile', array( __CLASS__, 'profile_fields' ) );
  add_action( 'edit_user_profile', array( __CLASS__, 'profile_fields' ) );
  add_action( 'personal_options_update', array( __CLASS__, 'save_profile' ) );
  add_action( 'edit_user_profile_update', array( __CLASS__, 'save_profile' ) );
  add_action( 'template_redirect', array( __CLASS__, 'private_page' ) );
  add_action( 'init', array( __CLASS__, 'content_types' ) );
  add_action( 'init', array( 'SCYS_Hub_Content', 'migrate' ), 20 );
  add_action( 'add_meta_boxes', array( 'SCYS_Hub_Content', 'boxes' ), 10, 2 );
  add_action( 'save_post', array( 'SCYS_Hub_Content', 'save' ), 10, 2 );
  add_action( 'add_meta_boxes_scys_hub_lesson', array( __CLASS__, 'quiz_box' ) );
  add_action( 'save_post_scys_hub_lesson', array( __CLASS__, 'save_quiz_box' ) );
 }
 public static function raw_catalogue() { return SCYS_Hub_Content::manifest(); }
 public static function lesson_ids( $user_id = 0 ) {
  $data = SCYS_Hub_Content::assigned( self::raw_catalogue(), self::track( $user_id ) );
  return array_column( $data['lessons'], 'id' );
 }
 public static function track( $user_id = 0 ) {
  $user = $user_id ? get_userdata( $user_id ) : wp_get_current_user();
  return $user && in_array( 'scys_volunteer', (array) $user->roles, true ) ? 'volunteer' : 'coach';
 }
 public static function allowed( $item, $user_id = 0 ) {
  return isset( $item['tracks'] ) ? in_array( self::track( $user_id ), $item['tracks'], true ) : in_array( $item['id'], self::lesson_ids( $user_id ), true );
 }
 public static function item( $group, $id ) {
  return SCYS_Hub_Content::detail( $group, $id );
 }
 public static function access() {
  return current_user_can( self::CAP ) ? true : new WP_Error( 'scys_forbidden', 'Your account does not have Coach Academy access.', array( 'status' => is_user_logged_in() ? 403 : 401 ) );
 }
 public static function reviewer() {
  return current_user_can( self::REVIEW ) ? true : new WP_Error( 'scys_forbidden', 'Mentor access is required.', array( 'status' => 403 ) );
 }
 public static function routes() {
  $base = 'scys-hub/v2';
  foreach ( array( 'catalogue' => 'catalogue', 'me' => 'me' ) as $route => $method ) {
   register_rest_route( $base, '/' . $route, array( 'methods' => 'GET', 'callback' => array( __CLASS__, $method ), 'permission_callback' => array( __CLASS__, 'access' ) ) );
  }
  foreach ( array( 'lessons' => 'lesson_detail', 'resources' => 'resource_detail', 'media' => 'media_detail' ) as $group => $method ) {
   register_rest_route( $base, '/' . $group . '/(?P<id>[a-z0-9-]+)', array( 'methods' => 'GET', 'callback' => array( __CLASS__, $method ), 'permission_callback' => array( __CLASS__, 'access' ) ) );
  }
  foreach ( array( 'quiz' => 'quiz', 'reflection' => 'reflection', 'preferences' => 'preferences' ) as $route => $method ) {
   register_rest_route( $base, '/' . $route, array( 'methods' => 'POST', 'callback' => array( __CLASS__, $method ), 'permission_callback' => array( __CLASS__, 'access' ) ) );
  }
  register_rest_route( $base, '/mentoring', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'mentoring' ), 'permission_callback' => array( __CLASS__, 'reviewer' ) ) );
  register_rest_route( $base, '/review', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'review' ), 'permission_callback' => array( __CLASS__, 'reviewer' ) ) );
 }
 public static function private_response( $data ) {
  $response = rest_ensure_response( $data ); $response->header( 'Cache-Control', 'private, no-store, max-age=0' ); return $response;
 }
 public static function catalogue() {
  $data = SCYS_Hub_Content::assigned( self::raw_catalogue(), self::track() );
  $data['tasks'] = array_values( array_filter( $data['tasks'], array( __CLASS__, 'allowed' ) ) );
  return self::private_response( $data );
 }
 public static function lesson_detail( $request ) {
  $id = sanitize_key( $request['id'] );
  if ( ! in_array( $id, self::lesson_ids(), true ) ) { return new WP_Error( 'scys_lesson', 'This lesson is not assigned to your pathway.', array( 'status' => 403 ) ); }
  $lesson = self::item( 'lessons', $id );
  if ( ! $lesson ) { return new WP_Error( 'scys_missing', 'This lesson is no longer available.', array( 'status' => 404 ) ); }
  foreach ( $lesson['quiz'] as &$question ) { unset( $question['correct'], $question['explanation'] ); } unset( $question );
  return self::private_response( $lesson );
 }
 public static function resource_detail( $request ) {
  $item = self::item( 'resources', sanitize_key( $request['id'] ) );
  return $item ? self::private_response( $item ) : new WP_Error( 'scys_missing', 'This activity is no longer available.', array( 'status' => 404 ) );
 }
 public static function media_detail( $request ) {
  $id = sanitize_key( $request['id'] ); $data = self::catalogue()->get_data(); $allowed = array();
  foreach ( $data['lessons'] as $lesson ) { $allowed = array_merge( $allowed, $lesson['mediaIds'] ?? array() ); }
  foreach ( $data['resources'] as $resource ) { if ( ! empty( $resource['mediaId'] ) ) { $allowed[] = $resource['mediaId']; } }
  if ( ! in_array( $id, $allowed, true ) ) { return new WP_Error( 'scys_media', 'This visual is not part of your available learning.', array( 'status' => 403 ) ); }
  $file = __DIR__ . '/content/media/' . $id . '.php';
  return is_file( $file ) ? self::private_response( require $file ) : new WP_Error( 'scys_missing', 'Visual unavailable.', array( 'status' => 404 ) );
 }
 public static function state( $id ) {
  $state = get_user_meta( $id, self::META, true );
  if ( ! is_array( $state ) ) { $state = array(); }
  return wp_parse_args( $state, array( 'completed' => array(), 'attempts' => array(), 'reflections' => array(), 'age' => 'U8', '_version' => 0 ) );
 }
 public static function persist( $id, $state ) {
  $previous = get_user_meta( $id, self::META, true );
  if ( $previous === $state ) { return $state; }
  if ( (int) ( $state['_version'] ?? 0 ) !== (int) ( $previous['_version'] ?? 0 ) ) { return new WP_Error( 'scys_save_conflict', 'Another session changed your progress. Please try again.', array( 'status' => 409 ) ); }
  $state['_version'] = (int) ( $state['_version'] ?? 0 ) + 1;
  // WordPress unslashes the new value, but compares prev_value as supplied.
  $saved = is_array( $previous ) ? update_user_meta( $id, self::META, wp_slash( $state ), $previous ) : add_user_meta( $id, self::META, wp_slash( $state ), true );
  if ( false === $saved ) { return new WP_Error( 'scys_save_failed', 'Your change could not be confirmed. Another session may have saved first. Please try again.', array( 'status' => 409 ) ); }
  return $state;
 }
 public static function me() {
  $user = wp_get_current_user();
  return array( 'id' => $user->ID, 'name' => $user->display_name, 'track' => self::track(), 'canReview' => current_user_can( self::REVIEW ), 'canCoordinate' => current_user_can( 'manage_options' ), 'state' => self::state( $user->ID ) );
 }
 public static function quiz( $request ) {
  $lesson = self::item( 'lessons', sanitize_key( $request->get_param( 'lessonId' ) ) );
  if ( ! $lesson || ! in_array( $lesson['id'], self::lesson_ids(), true ) ) { return new WP_Error( 'scys_lesson', 'This lesson is not assigned to your pathway.', array( 'status' => 403 ) ); }
  if ( count( $lesson['quiz'] ) < 3 ) { return new WP_Error( 'scys_quiz', 'This lesson learning check is not ready. Contact your educator.', array( 'status' => 400 ) ); }
  $answers = $request->get_param( 'answers' );
  if ( ! is_array( $answers ) || count( $answers ) !== count( $lesson['quiz'] ) ) { return new WP_Error( 'scys_answers', 'Answer every question before submitting.', array( 'status' => 400 ) ); }
  $correct = 0; $feedback = array();
  foreach ( $lesson['quiz'] as $index => $question ) {
   if ( ! isset( $answers[ $index ] ) || ! is_int( $answers[ $index ] ) || $answers[ $index ] < 0 || $answers[ $index ] >= count( $question['options'] ) ) { return new WP_Error( 'scys_answers', 'Select a valid answer for each question.', array( 'status' => 400 ) ); }
   $right = $answers[ $index ] === $question['correct'];
   if ( $right ) { ++$correct; }
   $feedback[] = array( 'correct' => $right, 'explanation' => $question['explanation'] );
  }
  // Completion requires all answers correct; explanations support unlimited retries.
  $passed = $correct === count( $lesson['quiz'] );
  $state = self::state( get_current_user_id() );
  $state['attempts'][ $lesson['id'] ] = array( 'score' => $correct, 'total' => count( $lesson['quiz'] ), 'at' => gmdate( 'c' ) );
  if ( $passed && ! in_array( $lesson['id'], $state['completed'], true ) ) { $state['completed'][] = $lesson['id']; }
  $saved = self::persist( get_current_user_id(), $state );
  if ( is_wp_error( $saved ) ) { return $saved; }
  return array( 'score' => $correct, 'total' => count( $lesson['quiz'] ), 'passed' => $passed, 'feedback' => $feedback, 'state' => $saved );
 }
 public static function reflection( $request ) {
  $task = self::item( 'tasks', sanitize_key( $request->get_param( 'taskId' ) ) );
  if ( ! $task || ! self::allowed( $task ) ) { return new WP_Error( 'scys_task', 'This task is not assigned to your pathway.', array( 'status' => 403 ) ); }
  $text = $request->get_param( 'text' );
  if ( ! is_string( $text ) || strlen( $text ) > 12000 || strlen( trim( $text ) ) < 20 ) { return new WP_Error( 'scys_reflection', 'Write a reflection of 20 to 12,000 characters.', array( 'status' => 400 ) ); }
  $state = self::state( get_current_user_id() );
  $previous = $state['reflections'][ $task['id'] ] ?? array();
  $clean = sanitize_textarea_field( $text );
  // A retry of the same submission preserves the review; revised text requires fresh review.
  if ( ( $previous['text'] ?? '' ) !== $clean ) {
   $state['reflections'][ $task['id'] ] = array( 'text' => $clean, 'submittedAt' => gmdate( 'c' ), 'revision' => wp_generate_uuid4(), 'status' => 'submitted', 'review' => null );
  }
  return self::persist( get_current_user_id(), $state );
 }
 public static function preferences( $request ) {
  $age = $request->get_param( 'age' );
  if ( ! in_array( $age, array( 'U6', 'U8', 'U10', 'U12', 'U14' ), true ) ) { return new WP_Error( 'scys_age', 'Choose a supported age group.', array( 'status' => 400 ) ); }
  $state = self::state( get_current_user_id() ); $state['age'] = $age;
  return self::persist( get_current_user_id(), $state );
 }
 public static function assigned( $id ) {
  return current_user_can( 'manage_options' ) || (int) get_user_meta( $id, '_scys_mentor_id', true ) === get_current_user_id();
 }
 public static function mentoring() {
  $args = array( 'role__in' => array( 'scys_coach', 'scys_volunteer' ), 'number' => 200, 'orderby' => 'display_name' );
  if ( ! current_user_can( 'manage_options' ) ) { $args['meta_key'] = '_scys_mentor_id'; $args['meta_value'] = get_current_user_id(); }
  $users = get_users( $args ); $out = array();
  foreach ( $users as $user ) {
   $state = self::state( $user->ID );
   foreach ( $state['reflections'] as $task_id => &$entry ) { $task = self::item( 'tasks', $task_id ); $entry['taskTitle'] = $task ? $task['title'] : $task_id; } unset( $entry );
   $out[] = array( 'id' => $user->ID, 'name' => $user->display_name, 'track' => self::track( $user->ID ), 'state' => $state );
  }
  return array( 'learners' => $out, 'limit' => 200, 'atLimit' => count( $users ) === 200 );
 }
 public static function review( $request ) {
  $id = absint( $request->get_param( 'userId' ) );
  if ( ! $id || $id === get_current_user_id() || ! get_userdata( $id ) || ! self::assigned( $id ) ) { return new WP_Error( 'scys_assignment', 'You can only review other learners assigned to you.', array( 'status' => 403 ) ); }
  $task = self::item( 'tasks', sanitize_key( $request->get_param( 'taskId' ) ) );
  if ( ! $task || ! self::allowed( $task, $id ) ) { return new WP_Error( 'scys_task', 'Task not found for this learner.', array( 'status' => 404 ) ); }
  $state = self::state( $id ); $entry = $state['reflections'][ $task['id'] ] ?? null;
  if ( ! $entry ) { return new WP_Error( 'scys_missing', 'The learner has not submitted this task.', array( 'status' => 400 ) ); }
  if ( $request->get_param( 'revision' ) !== $entry['revision'] ) { return new WP_Error( 'scys_conflict', 'The reflection changed. Reload it before reviewing.', array( 'status' => 409 ) ); }
  $status = $request->get_param( 'status' ); $note = $request->get_param( 'note' );
  if ( ! in_array( $status, array( 'approved', 'revise' ), true ) || ! is_string( $note ) || strlen( trim( $note ) ) < 10 || strlen( $note ) > 12000 ) { return new WP_Error( 'scys_review', 'Choose a result and add feedback of 10 to 12,000 characters.', array( 'status' => 400 ) ); }
  $state['reflections'][ $task['id'] ]['status'] = $status;
  $state['reflections'][ $task['id'] ]['review'] = array( 'text' => sanitize_textarea_field( $note ), 'by' => wp_get_current_user()->display_name, 'at' => gmdate( 'c' ) );
  return self::persist( $id, $state );
 }
 public static function private_page() {
  global $post;
  if ( $post && has_shortcode( $post->post_content, 'scys_coach_hub' ) ) { if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); } nocache_headers(); header( 'X-Robots-Tag: noindex, nofollow', true ); }
 }
 /** Logo for the hub header and sign-in gate: theme custom logo, then site icon. Filter: scys_hub_logo_url. */
 public static function logo_url() {
  $url = '';
  $logo = (int) get_theme_mod( 'custom_logo' );
  if ( $logo ) { $url = (string) wp_get_attachment_image_url( $logo, 'medium' ); }
  if ( ! $url ) { $url = (string) get_site_icon_url( 192 ); }
  return esc_url_raw( (string) apply_filters( 'scys_hub_logo_url', $url ) );
 }
 public static function styles() {
  wp_enqueue_style( 'scys-hub', plugins_url( 'assets/hub.css', __FILE__ ), array(), self::VERSION );
  // Brand layer: re-skins hub.css only. Dequeue 'scys-hub-brand' to roll back to the base look.
  wp_enqueue_style( 'scys-hub-brand', plugins_url( 'assets/brand.css', __FILE__ ), array( 'scys-hub' ), self::VERSION );
  $logo = self::logo_url();
  if ( $logo ) { wp_add_inline_style( 'scys-hub-brand', '#scys-hub{--hub-logo:url("' . esc_url( $logo ) . '")}' ); }
  return $logo;
 }
 public static function shortcode() {
  $logo = self::styles();
  if ( ! current_user_can( self::CAP ) ) {
   $enrolled_msg = is_user_logged_in() ? 'Your account has not been enrolled yet. Please contact your programme coordinator.' : 'Sign in with your invited account to open your learning pathway.';
   $html = '<section class="scys-gate" aria-labelledby="scys-gate-title">';
   if ( $logo ) { $html .= '<img src="' . esc_url( $logo ) . '" alt="" width="76" height="76" loading="lazy">'; }
   $html .= '<h2 id="scys-gate-title">Coach Academy</h2><p>' . esc_html( $enrolled_msg ) . '</p><div class="scys-gate-actions">';
   if ( ! is_user_logged_in() ) {
    $html .= '<a class="scys-gate-primary" href="' . esc_url( wp_login_url( get_permalink() ) ) . '">Sign in</a><a class="scys-gate-secondary" href="' . esc_url( wp_lostpassword_url( get_permalink() ) ) . '">Reset password</a>';
   } else {
    $html .= '<a class="scys-gate-secondary" href="' . esc_url( wp_logout_url( get_permalink() ) ) . '">Sign out</a>';
   }
   return $html . '</div></section>';
  }
  wp_enqueue_script( 'scys-mentoring', plugins_url( 'assets/mentoring.js', __FILE__ ), array(), self::VERSION, true );
  wp_enqueue_script( 'scys-hub', plugins_url( 'assets/hub.js', __FILE__ ), array( 'scys-mentoring' ), self::VERSION, true );
  wp_add_inline_script( 'scys-hub', 'window.SCYS_HUB=' . wp_json_encode( array( 'mode' => 'wordpress', 'api' => rest_url( 'scys-hub/v2/' ), 'nonce' => wp_create_nonce( 'wp_rest' ), 'logoutUrl' => wp_logout_url( get_permalink() ) ) ) . ';', 'before' );
  return '<div id="scys-hub"' . ( $logo ? ' data-has-logo' : '' ) . '><p role="status">Opening your Coach Academy…</p></div>';
 }
 public static function admin_menu() { add_users_page( 'SCYS Coach Hub', 'SCYS Coach Hub', 'manage_options', 'scys-coach-hub', array( __CLASS__, 'admin_page' ) ); }
 public static function admin_page() {
  if ( ! current_user_can( 'manage_options' ) ) { return; }
  echo '<div class="wrap"><h1>SCYS Coach Education Hub</h1><p>Version ' . esc_html( self::VERSION ) . ' · staging candidate.</p><ol><li>Create or invite accounts in Users. Assign SCYS Coach, SCYS Volunteer or SCYS Mentor.</li><li>Edit each learner profile to assign a mentor.</li><li>Create a WordPress page and add the shortcode <code>[scys_coach_hub]</code>.</li><li>Exclude that page and <code>/wp-json/scys-hub/</code> from host/CDN caching.</li><li>Test coach, volunteer, mentor and logged-out access before publishing.</li></ol><p>Progress is stored against WordPress accounts. No email, SMS or external AI calls are made by this plugin. Learning completion and practical mentor approval are separate.</p><p><a href="' . esc_url( admin_url( 'edit.php?post_type=scys_hub_lesson' ) ) . '">Edit lessons and quizzes</a> · <a href="' . esc_url( admin_url( 'edit.php?post_type=scys_hub_resource' ) ) . '">Edit activities</a> · <a href="' . esc_url( admin_url( 'edit.php?post_type=scys_hub_category' ) ) . '">Categories</a> · <a href="' . esc_url( admin_url( 'edit.php?post_type=scys_hub_course' ) ) . '">Courses</a> · <a href="' . esc_url( admin_url( 'edit.php?post_type=scys_hub_pathway' ) ) . '">Pathways</a></p><p>Build lessons, then include them in categories. Include categories in courses, and courses in pathways. Use the order fields to include, remove or rearrange items. Pathways select coach or volunteer audiences. Existing lesson IDs retain completion across every course that uses them. Draft or trash an item to withdraw it; deleting a parent does not delete its children.</p></div>';
 }
 public static function profile_fields( $user ) {
  if ( ! current_user_can( 'manage_options' ) ) { return; }
  $assigned = (int) get_user_meta( $user->ID, '_scys_mentor_id', true );
  echo '<h2>SCYS Coach Hub</h2><p><label>Assigned mentor <select name="scys_mentor"><option value="0">No mentor assigned</option>';
  foreach ( get_users( array( 'role__in' => array( 'scys_mentor', 'administrator' ), 'number' => 200 ) ) as $mentor ) { echo '<option value="' . esc_attr( $mentor->ID ) . '" ' . selected( $assigned, $mentor->ID, false ) . '>' . esc_html( $mentor->display_name ) . '</option>'; }
  echo '</select></label></p>'; wp_nonce_field( 'scys_mentor_assignment', 'scys_mentor_nonce' );
 }
 public static function save_profile( $id ) {
  if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_user', $id ) || ! isset( $_POST['scys_mentor_nonce'], $_POST['scys_mentor'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['scys_mentor_nonce'] ) ), 'scys_mentor_assignment' ) ) { return; }
  $mentor = absint( $_POST['scys_mentor'] );
  if ( $mentor && ! user_can( $mentor, self::REVIEW ) ) { return; }
  update_user_meta( $id, '_scys_mentor_id', $mentor );
 }
 public static function content_types() { SCYS_Hub_Content::register(); }
 public static function seed_editable_content() { SCYS_Hub_Content::migrate(); }
 public static function quiz_box() { add_meta_box( 'scys_quiz', 'Learning check questions', array( __CLASS__, 'quiz_fields' ), 'scys_hub_lesson', 'normal' ); }
 public static function quiz_fields( $post ) {
  $quiz = get_post_meta( $post->ID, '_scys_quiz', true );
  if ( ! is_array( $quiz ) || ! $quiz ) { $quiz = array_fill( 0, 3, array( 'prompt' => '', 'options' => array( '', '', '' ), 'correct' => 0, 'explanation' => '' ) ); }
  wp_nonce_field( 'scys_quiz_edit', 'scys_quiz_nonce' );
  echo '<p>Keep the question focused on a coaching decision. Learners must answer every question correctly; explanations support retries. Editing existing content retains prior completion records.</p>';
  foreach ( (array) $quiz as $i => $q ) {
   echo '<fieldset style="padding:15px 0;border-top:1px solid #ddd"><legend>Question ' . esc_html( $i + 1 ) . '</legend><p><label>Question<br><input class="widefat" name="scys_quiz[' . esc_attr( $i ) . '][prompt]" value="' . esc_attr( $q['prompt'] ) . '" required></label></p>';
   foreach ( $q['options'] as $j => $option ) { echo '<p><label>Answer ' . esc_html( $j + 1 ) . ' <input type="radio" name="scys_quiz[' . esc_attr( $i ) . '][correct]" value="' . esc_attr( $j ) . '" ' . checked( $q['correct'], $j, false ) . '> Correct<br><input class="widefat" name="scys_quiz[' . esc_attr( $i ) . '][options][' . esc_attr( $j ) . ']" value="' . esc_attr( $option ) . '" required></label></p>'; }
   echo '<p><label>Explanation<br><textarea class="widefat" name="scys_quiz[' . esc_attr( $i ) . '][explanation]" required>' . esc_textarea( $q['explanation'] ) . '</textarea></label></p></fieldset>';
  }
 }
 public static function save_quiz_box( $id ) {
  if ( ! current_user_can( 'manage_options' ) || wp_is_post_revision( $id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! isset( $_POST['scys_quiz_nonce'], $_POST['scys_quiz'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['scys_quiz_nonce'] ) ), 'scys_quiz_edit' ) ) { return; }
  $input = wp_unslash( $_POST['scys_quiz'] ); if ( ! is_array( $input ) || count( $input ) < 3 || count( $input ) > 10 ) { return; }
  $out = array();
  foreach ( $input as $i => $q ) {
   if ( ! is_array( $q ) || ! is_string( $q['prompt'] ?? null ) || ! is_string( $q['explanation'] ?? null ) || ! is_array( $q['options'] ?? null ) || count( $q['options'] ) !== 3 || ! isset( $q['correct'] ) || ! in_array( (string) $q['correct'], array( '0', '1', '2' ), true ) ) { return; }
   foreach ( $q['options'] as $option ) { if ( ! is_string( $option ) || ! strlen( trim( $option ) ) ) { return; } }
   if ( ! strlen( trim( $q['prompt'] ) ) || ! strlen( trim( $q['explanation'] ) ) ) { return; }
   $out[] = array( 'id' => 'q' . ( $i + 1 ), 'prompt' => sanitize_text_field( $q['prompt'] ), 'options' => array_map( 'sanitize_text_field', array_values( $q['options'] ) ), 'correct' => (int) $q['correct'], 'explanation' => sanitize_textarea_field( $q['explanation'] ) );
  }
  update_post_meta( $id, '_scys_quiz', wp_slash( $out ) );
 }
}
register_activation_hook( __FILE__, array( 'SCYS_Coach_Hub', 'activate' ) );
SCYS_Coach_Hub::init();
