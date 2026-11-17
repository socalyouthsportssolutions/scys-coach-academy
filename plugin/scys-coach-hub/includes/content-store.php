<?php
/** Independent content records and reusable learning structure. */
defined( 'ABSPATH' ) || exit;

final class SCYS_Hub_Content {
 const TYPES = array( 'lessons' => 'scys_hub_lesson', 'resources' => 'scys_hub_resource', 'categories' => 'scys_hub_category', 'courses' => 'scys_hub_course', 'learningPathways' => 'scys_hub_pathway' );
 const CHILDREN = array( 'categories' => array( 'lessons', 'lessonIds' ), 'courses' => array( 'categories', 'categoryIds' ), 'learningPathways' => array( 'courses', 'courseIds' ) );

 public static function index() { return require dirname( __DIR__ ) . '/content/index.php'; }
 public static function posts( $group, $status = 'publish' ) {
  return get_posts( array( 'post_type' => self::TYPES[ $group ], 'post_status' => $status, 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC' ) );
 }
 public static function find( $group, $id, $status = 'publish' ) {
  $posts = get_posts( array( 'post_type' => self::TYPES[ $group ], 'post_status' => $status, 'numberposts' => 1, 'meta_key' => '_scys_content_id', 'meta_value' => $id ) );
  return $posts[0] ?? null;
 }
 public static function manifest() {
  global $wpdb;
  $out = self::index();
  foreach ( self::TYPES as $group => $type ) {
   $out[ $group ] = array();
   // Read headings only: no post_content or quiz payload is fetched for startup.
   $headings = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title, post_excerpt FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish' ORDER BY ID ASC", $type ) );
   // get_post_meta() would prime every key, including quizzes; read only index keys.
   $rows = $wpdb->get_results( $wpdb->prepare( "SELECT m.post_id, m.meta_key, m.meta_value FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id WHERE p.post_type = %s AND p.post_status = 'publish' AND m.meta_key IN ('_scys_content_id','_scys_summary','_scys_links','_scys_tracks','_scys_minutes') ORDER BY m.meta_id ASC", $type ) );
   $metadata = array();
   foreach ( $rows as $row ) { if ( ! isset( $metadata[ $row->post_id ][ $row->meta_key ] ) ) { $metadata[ $row->post_id ][ $row->meta_key ] = maybe_unserialize( $row->meta_value ); } }
   foreach ( $headings as $post ) {
    $meta = $metadata[ $post->ID ] ?? array();
    $id = $meta['_scys_content_id'] ?? '';
    if ( ! $id ) { continue; }
    $item = $meta['_scys_summary'] ?? array();
    if ( ! is_array( $item ) ) { $item = array(); }
    $item['id'] = $id; $item['title'] = $post->post_title; $item['summary'] = $post->post_excerpt;
    if ( isset( self::CHILDREN[ $group ] ) ) { $item[ self::CHILDREN[ $group ][1] ] = (array) ( $meta['_scys_links'] ?? array() ); }
    if ( 'learningPathways' === $group ) { $item['tracks'] = (array) ( $meta['_scys_tracks'] ?? array() ); }
    if ( 'lessons' === $group ) { $item['minutes'] = max( 1, (int) ( $meta['_scys_minutes'] ?? 6 ) ); }
    $out[ $group ][] = $item;
   }
  }
  return $out;
 }
 public static function detail( $group, $id ) {
  if ( ! isset( self::TYPES[ $group ] ) ) { foreach ( self::index()[ $group ] ?? array() as $item ) { if ( $item['id'] === $id ) { return $item; } } return null; }
  $post = self::find( $group, $id );
  if ( ! $post ) { return null; } // Draft/trash is withdrawal; never resurrect the bundled copy.
  $item = get_post_meta( $post->ID, '_scys_summary', true );
  if ( ! is_array( $item ) ) { $item = array(); }
  $item['id'] = $id; $item['title'] = $post->post_title; $item['summary'] = $post->post_excerpt;
  $file = dirname( __DIR__ ) . '/content/' . $group . '/' . $id . '.php';
  if ( in_array( $group, array( 'lessons', 'resources' ), true ) && is_file( $file ) ) { $item = array_merge( require $file, $item ); }
  if ( 'lessons' === $group ) {
   $item['minutes'] = max( 1, (int) get_post_meta( $post->ID, '_scys_minutes', true ) );
   $item['sections'] = array( array( 'heading' => 'Explore the idea', 'html' => wp_kses_post( $post->post_content ) ) );
   $item['quiz'] = (array) get_post_meta( $post->ID, '_scys_quiz', true );
   $item['mediaIds'] = $item['mediaIds'] ?? array(); $item['takeaway'] = $item['takeaway'] ?? ''; $item['sourceRefs'] = $item['sourceRefs'] ?? array();
  } elseif ( 'resources' === $group ) { $item['bodyHtml'] = wp_kses_post( $post->post_content ); }
  return $item;
 }
 public static function assigned( $data, $track ) {
  $data['learningPathways'] = array_values( array_filter( $data['learningPathways'], function( $p ) use ( $track ) { return in_array( $track, $p['tracks'], true ); } ) );
  $course_ids = array_merge( array(), ...array_column( $data['learningPathways'], 'courseIds' ) );
  $data['courses'] = array_values( array_filter( $data['courses'], function( $c ) use ( $course_ids ) { return in_array( $c['id'], $course_ids, true ); } ) );
  $category_ids = array_merge( array(), ...array_column( $data['courses'], 'categoryIds' ) );
  $data['categories'] = array_values( array_filter( $data['categories'], function( $c ) use ( $category_ids ) { return in_array( $c['id'], $category_ids, true ); } ) );
  $lesson_ids = array_unique( array_merge( array(), ...array_column( $data['categories'], 'lessonIds' ) ) );
  $data['lessons'] = array_values( array_filter( $data['lessons'], function( $l ) use ( $lesson_ids ) { return in_array( $l['id'], $lesson_ids, true ); } ) );
  // Resolve published links only; preserve ordering at each level.
  foreach ( array( array( 'categories', 'lessonIds', 'lessons' ), array( 'courses', 'categoryIds', 'categories' ), array( 'learningPathways', 'courseIds', 'courses' ) ) as $relation ) {
   foreach ( $data[ $relation[0] ] as &$parent ) { $parent[ $relation[1] ] = array_values( array_intersect( $parent[ $relation[1] ], array_column( $data[ $relation[2] ], 'id' ) ) ); } unset( $parent );
  }
  return $data;
 }
 public static function register() {
  $labels = array( 'lessons' => 'Academy Lessons', 'resources' => 'Academy Activities', 'categories' => 'Academy Categories', 'courses' => 'Academy Courses', 'learningPathways' => 'Academy Pathways' );
  foreach ( self::TYPES as $group => $type ) {
   $supports = in_array( $group, array( 'lessons', 'resources' ), true ) ? array( 'title', 'editor', 'excerpt', 'revisions' ) : array( 'title', 'excerpt' );
   $caps = array_fill_keys( array( 'edit_posts', 'edit_others_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'edit_post', 'delete_post', 'read_post', 'create_posts' ), 'manage_options' );
   if ( 'resources' === $group ) { $caps['create_posts'] = 'do_not_allow'; } // Activity tags/setup still come from curated modules.
   register_post_type( $type, array( 'label' => $labels[ $group ], 'public' => false, 'show_ui' => true, 'show_in_rest' => false, 'supports' => $supports, 'capabilities' => $caps, 'map_meta_cap' => false ) );
  }
 }
 public static function migrate() {
  if ( get_option( 'scys_hub_content_version' ) === '0.3.0' ) { return; }
  // Runs on update as well as activation; existing text/quizzes/progress are never replaced.
  $data = self::index();
  foreach ( self::TYPES as $group => $type ) {
   foreach ( $data[ $group ] as $item ) {
    $post = self::find( $group, $item['id'], array( 'publish', 'draft', 'pending', 'private', 'future', 'trash' ) );
    $new = ! $post;
    $html = '';
    if ( $new && in_array( $group, array( 'lessons', 'resources' ), true ) ) {
     $full = require dirname( __DIR__ ) . '/content/' . $group . '/' . $item['id'] . '.php';
     if ( 'lessons' === $group ) { foreach ( $full['sections'] as $s ) { $html .= '<h3>' . esc_html( $s['heading'] ) . '</h3>' . wp_kses_post( $s['html'] ); } }
     else { $html = '<h3>Set up</h3><p>' . esc_html( $full['setup'] ) . '</p>'; foreach ( array( 'instructions' => 'How to play', 'support' => 'Offer more support', 'challenge' => 'Add more challenge', 'observe' => 'What to notice' ) as $key => $heading ) { $html .= '<h3>' . esc_html( $heading ) . '</h3><ul>'; foreach ( $full[ $key ] as $line ) { $html .= '<li>' . esc_html( $line ) . '</li>'; } $html .= '</ul>'; } }
    }
    $id = $post ? $post->ID : wp_insert_post( wp_slash( array( 'post_type' => $type, 'post_status' => 'publish', 'post_title' => $item['title'], 'post_excerpt' => $item['summary'] ?? '', 'post_content' => $html ) ), true );
    if ( is_wp_error( $id ) || ! $id ) { return; }
    update_post_meta( $id, '_scys_content_id', $item['id'] );
    if ( ! metadata_exists( 'post', $id, '_scys_summary' ) ) { update_post_meta( $id, '_scys_summary', wp_slash( $item ) ); }
    if ( isset( self::CHILDREN[ $group ] ) && ! metadata_exists( 'post', $id, '_scys_links' ) ) { update_post_meta( $id, '_scys_links', $item[ self::CHILDREN[ $group ][1] ] ); }
    if ( 'learningPathways' === $group && ! metadata_exists( 'post', $id, '_scys_tracks' ) ) { update_post_meta( $id, '_scys_tracks', $item['tracks'] ); }
    if ( 'lessons' === $group ) {
     if ( ! metadata_exists( 'post', $id, '_scys_minutes' ) ) { update_post_meta( $id, '_scys_minutes', $item['minutes'] ); }
     if ( $new ) { update_post_meta( $id, '_scys_quiz', wp_slash( $full['quiz'] ) ); }
    }
   }
  }
  update_option( 'scys_hub_content_version', '0.3.0', false );
 }
 public static function boxes( $type, $post ) {
  $group = array_search( $type, self::TYPES, true );
  if ( false !== $group ) { add_meta_box( 'scys_structure', 'Module and course structure', array( __CLASS__, 'fields' ), $type, 'normal' ); }
 }
 public static function fields( $post ) {
  $group = array_search( $post->post_type, self::TYPES, true );
  wp_nonce_field( 'scys_structure_edit', 'scys_structure_nonce' );
  echo '<p>Permanent ID: <code>' . esc_html( get_post_meta( $post->ID, '_scys_content_id', true ) ?: 'Assigned when saved' ) . '</code>. Reuse this record rather than copying it. Drafting or trashing it removes it from learning.</p>';
  if ( 'lessons' === $group ) { echo '<label>Estimated minutes <input type="number" name="scys_minutes" min="1" max="240" value="' . esc_attr( get_post_meta( $post->ID, '_scys_minutes', true ) ?: 6 ) . '"></label>'; }
  if ( 'learningPathways' === $group ) {
   $tracks = (array) get_post_meta( $post->ID, '_scys_tracks', true );
   foreach ( array( 'coach' => 'Coaches and mentors', 'volunteer' => 'Volunteers' ) as $track => $label ) { echo '<p><label><input type="checkbox" name="scys_tracks[]" value="' . esc_attr( $track ) . '" ' . checked( in_array( $track, $tracks, true ), true, false ) . '> ' . esc_html( $label ) . '</label></p>'; }
  }
  if ( isset( self::CHILDREN[ $group ] ) ) {
   $children = self::CHILDREN[ $group ]; $selected = (array) get_post_meta( $post->ID, '_scys_links', true );
   echo '<p>Enter an order number to include an item. Leave blank to exclude it. The same item can belong to several parents; existing lesson completion is shared.</p>';
   foreach ( self::posts( $children[0] ) as $child ) {
    $id = get_post_meta( $child->ID, '_scys_content_id', true ); if ( ! $id ) { continue; } $position = array_search( $id, $selected, true );
    echo '<p><label><input type="number" min="1" max="9999" style="width:85px" name="scys_links[' . esc_attr( $id ) . ']" value="' . esc_attr( false === $position ? '' : $position + 1 ) . '"> ' . esc_html( $child->post_title ) . ' <code>' . esc_html( $id ) . '</code></label></p>';
   }
  }
 }
 public static function save( $id, $post ) {
  $group = array_search( $post->post_type, self::TYPES, true );
  if ( false === $group || ! current_user_can( 'manage_options' ) || wp_is_post_revision( $id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! isset( $_POST['scys_structure_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['scys_structure_nonce'] ) ), 'scys_structure_edit' ) ) { return; }
  if ( ! get_post_meta( $id, '_scys_content_id', true ) ) { update_post_meta( $id, '_scys_content_id', sanitize_key( $group . '-' . $id ) ); }
  if ( 'lessons' === $group ) { update_post_meta( $id, '_scys_minutes', min( 240, max( 1, absint( $_POST['scys_minutes'] ?? 6 ) ) ) ); }
  if ( 'learningPathways' === $group ) { $tracks = isset( $_POST['scys_tracks'] ) && is_array( $_POST['scys_tracks'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['scys_tracks'] ) ) : array(); update_post_meta( $id, '_scys_tracks', array_values( array_intersect( $tracks, array( 'coach', 'volunteer' ) ) ) ); }
  if ( isset( self::CHILDREN[ $group ] ) ) {
   $input = $_POST['scys_links'] ?? array(); if ( ! is_array( $input ) ) { return; }
   $links = array(); foreach ( $input as $key => $order ) { if ( is_scalar( $order ) && (string) $order !== '' && absint( $order ) > 0 && self::find( self::CHILDREN[ $group ][0], sanitize_key( $key ) ) ) { $links[ sanitize_key( $key ) ] = absint( $order ); } }
   asort( $links, SORT_NUMERIC ); update_post_meta( $id, '_scys_links', array_keys( $links ) );
  }
 }
}
