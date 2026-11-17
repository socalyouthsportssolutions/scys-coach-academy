<?php
/**
 * SCYS Coach Academy uninstall.
 *
 * SAFE BY DEFAULT: deleting the plugin keeps every learning record, content post, mentoring record,
 * role and option. Data is only removed when an administrator has deliberately set the option
 * `scys_hub_delete_data_on_uninstall` to '1' (e.g. `wp option update scys_hub_delete_data_on_uninstall 1`)
 * after taking a backup and confirming the retention procedure allows it.
 */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( '1' !== get_option( 'scys_hub_delete_data_on_uninstall' ) ) {
	return;
}

global $wpdb;

foreach ( array( 'scys_hub_lesson', 'scys_hub_resource', 'scys_hub_category', 'scys_hub_course', 'scys_hub_pathway' ) as $type ) {
	$ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", $type ) );
	foreach ( $ids as $id ) { wp_delete_post( (int) $id, true ); }
}

delete_metadata( 'user', 0, '_scys_hub_state', '', true );
delete_metadata( 'user', 0, '_scys_mentor_id', '', true );

$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'scys_mentoring' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange

foreach ( array( 'scys_coach', 'scys_volunteer', 'scys_mentor' ) as $role ) { remove_role( $role ); }
$admin = get_role( 'administrator' );
if ( $admin ) { $admin->remove_cap( 'scys_access_hub' ); $admin->remove_cap( 'scys_review_hub' ); }

foreach ( array( 'scys_hub_content_version', 'scys_mentoring_schema', 'scys_mentoring_policy', 'scys_hub_delete_data_on_uninstall' ) as $option ) {
	delete_option( $option );
}
