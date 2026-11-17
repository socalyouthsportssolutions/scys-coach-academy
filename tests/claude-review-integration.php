<?php
// Synthetic fixtures only, after the existing integration suites in disposable WordPress.
wp_set_current_user( 1 );
add_filter( 'pre_wp_mail', '__return_true' ); // Prevent any test-account email from leaving this runtime.
wp_update_user( array( 'ID' => $coach, 'user_email' => 'coach-privacy@example.test' ) );
wp_update_user( array( 'ID' => $volunteer, 'user_email' => 'volunteer-privacy@example.test' ) );
update_user_meta( $coach, '_scys_mentor_id', $mentor );
$exporters = apply_filters( 'wp_privacy_personal_data_exporters', array() );
$erasers = apply_filters( 'wp_privacy_personal_data_erasers', array() );
verify_hub( isset( $exporters['scys-coach-hub'], $erasers['scys-coach-hub'] ), 'Claude privacy tools register with native WordPress workflow' );
$export = SCYS_Hub_Privacy::export( 'coach-privacy@example.test' );
$groups = array_column( $export['data'], 'group_id' );
verify_hub( in_array( 'scys-hub-learning', $groups, true ) && in_array( 'scys-hub-reflections', $groups, true ) && in_array( 'scys-hub-mentoring', $groups, true ), 'Coach export contains learning, reflections and own mentoring records' );
verify_hub( ! str_contains( wp_json_encode( $export ), 'receipts' ), 'Privacy export excludes internal retry receipts' );
$other_export = SCYS_Hub_Privacy::export( 'volunteer-privacy@example.test' );
verify_hub( ! str_contains( wp_json_encode( $other_export ), 'Synthetic autumn team' ), 'Another adult export excludes the coach mentoring notes' );
verify_hub( SCYS_Hub_Privacy::export( 'not-an-account@example.test' ) === array( 'data' => array(), 'done' => true ), 'Unknown email exports no records' );
global $wpdb;
$table = SCYS_Hub_Mentoring::table();
for ( $i = 0; $i < 26; $i++ ) { $wpdb->insert( $table, array( 'coach_id' => $coach, 'create_key' => 'privacy-test-' . $i, 'version' => 1, 'payload' => wp_json_encode( array( 'status' => 'requested', 'topic' => 'Synthetic pagination fixture ' . $i, 'visits' => array(), 'receipts' => array( 'hidden' => 'hidden' ) ) ) ) ); }
$first = SCYS_Hub_Privacy::export( 'coach-privacy@example.test', 1 );
$second = SCYS_Hub_Privacy::export( 'coach-privacy@example.test', 2 );
$own_group = static function( $entry ) { return $entry['group_id'] === 'scys-hub-mentoring'; };
$first_records = array_values( array_filter( $first['data'], $own_group ) );
$second_records = array_values( array_filter( $second['data'], $own_group ) );
verify_hub( count( $first_records ) === 25 && ! $first['done'] && count( $second_records ) === 2 && $second['done'], 'Privacy export paginates 27 mentoring records without truncation' );
verify_hub( ! array_intersect( array_column( $first_records, 'item_id' ), array_column( $second_records, 'item_id' ) ), 'Privacy export pages do not duplicate mentoring rows' );
$before_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
$erased = SCYS_Hub_Privacy::erase( 'coach-privacy@example.test' );
verify_hub( $erased['items_removed'] && ! get_user_meta( $coach, SCYS_Coach_Hub::META, true ) && ! get_user_meta( $coach, '_scys_mentor_id', true ), 'Native eraser removes learning state and own mentor assignment' );
verify_hub( $erased['items_retained'] && ! empty( $erased['messages'] ) && (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" ) === $before_count, 'Native eraser retains operational records by default and reports that clearly' );
wp_set_current_user( $mentor );
verify_hub( support_api( 'GET', 'support/' . $rid )->get_status() === 404, 'Erased assignment removes former mentor direct access' );
wp_set_current_user( 1 );
add_filter( 'scys_hub_erase_mentoring', '__return_true' );
$break_delete = static function( $sql ) use ( $table ) { return str_starts_with( $sql, "DELETE FROM `$table`" ) ? str_replace( "`$table`", '`synthetic_missing_table`', $sql ) : $sql; };
$previous_suppression = $wpdb->suppress_errors( true ); add_filter( 'query', $break_delete );
$failed = SCYS_Hub_Privacy::erase( 'coach-privacy@example.test' );
remove_filter( 'query', $break_delete ); $wpdb->suppress_errors( $previous_suppression );
verify_hub( $failed['items_retained'] && ! $failed['items_removed'] && (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" ) === $before_count, 'Failed approved erasure reports retention instead of false success' );
$erased = SCYS_Hub_Privacy::erase( 'coach-privacy@example.test' );
verify_hub( $erased['items_removed'] && ! $erased['items_retained'] && !(int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE coach_id = %d", $coach ) ), 'Explicit synthetic policy override can erase own operational records' );
remove_filter( 'scys_hub_erase_mentoring', '__return_true' );

// Access gates must style the page without loading learner JS or any content bodies.
wp_set_current_user( 0 );
$signed_out = SCYS_Coach_Hub::shortcode();
verify_hub( str_contains( $signed_out, 'scys-gate' ) && str_contains( $signed_out, '>Sign in<' ) && str_contains( $signed_out, 'Reset password' ), 'Signed-out branded gate offers sign-in and reset' );
verify_hub( wp_style_is( 'scys-hub-brand', 'enqueued' ) && ! wp_script_is( 'scys-hub', 'enqueued' ), 'Signed-out gate loads styling without learner script or lesson bodies' );
wp_set_current_user( $subscriber );
$unenrolled = SCYS_Coach_Hub::shortcode();
verify_hub( str_contains( $unenrolled, 'not been enrolled' ) && str_contains( $unenrolled, '>Sign out<' ) && ! str_contains( $unenrolled, '>Sign in<' ), 'Signed-in unenrolled gate offers sign-out rather than another sign-in' );
add_filter( 'scys_hub_logo_url', static function() { return 'https://example.test/synthetic-logo.png'; } );
verify_hub( SCYS_Coach_Hub::logo_url() === 'https://example.test/synthetic-logo.png', 'Brand logo filter is supported without an external font dependency' );
remove_all_filters( 'scys_hub_logo_url' );
wp_set_current_user( $volunteer );
$signed_in = SCYS_Coach_Hub::shortcode();
verify_hub( str_contains( $signed_in, 'id="scys-hub"' ) && wp_script_is( 'scys-hub', 'enqueued' ), 'Enrolled learner receives the shared interface with the brand layer' );

// Deleting the plugin retains data unless a deliberate opt-in is recorded.
wp_set_current_user( 1 );
define( 'WP_UNINSTALL_PLUGIN', 'scys-coach-hub/scys-coach-hub.php' );
$content_count = count( SCYS_Hub_Content::posts( 'lessons' ) );
delete_option( 'scys_hub_delete_data_on_uninstall' );
include WP_PLUGIN_DIR . '/scys-coach-hub/uninstall.php';
verify_hub( count( SCYS_Hub_Content::posts( 'lessons' ) ) === $content_count && get_role( 'scys_coach' ), 'Uninstall keeps content and roles by default' );
update_option( 'scys_hub_delete_data_on_uninstall', '1' );
include WP_PLUGIN_DIR . '/scys-coach-hub/uninstall.php';
verify_hub( ! count( SCYS_Hub_Content::posts( 'lessons' ) ) && ! get_role( 'scys_coach' ) && ! get_option( 'scys_mentoring_schema' ), 'Explicit opt-in cleanup works only inside this disposable synthetic runtime' );
echo "ALL CLAUDE REVIEW INTEGRATION CHECKS PASSED\n";
