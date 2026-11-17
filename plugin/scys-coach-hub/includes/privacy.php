<?php
/**
 * WordPress personal-data export / erase integration (Tools > Export / Erase Personal Data).
 *
 * Export: learning progress, quiz attempts, reflections + mentor feedback, and mentoring records
 * where the person is the coach.
 * Erase: learning progress and mentor assignment. Mentoring visit records are RETAINED and reported
 * as retained, because they are adult-development operational records awaiting the agreed
 * retention procedure (see Users > Mentoring pilot settings). Change scys_hub_erase_mentoring to
 * true only once that procedure says personal erasure requests should remove them.
 */
defined( 'ABSPATH' ) || exit;

final class SCYS_Hub_Privacy {
	public static function init() {
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_eraser' ) );
		add_action( 'admin_init', array( __CLASS__, 'policy_text' ) );
	}

	public static function register_exporter( $exporters ) {
		$exporters['scys-coach-hub'] = array( 'exporter_friendly_name' => 'SCYS Coach Academy', 'callback' => array( __CLASS__, 'export' ) );
		return $exporters;
	}

	public static function register_eraser( $erasers ) {
		$erasers['scys-coach-hub'] = array( 'eraser_friendly_name' => 'SCYS Coach Academy', 'callback' => array( __CLASS__, 'erase' ) );
		return $erasers;
	}

	private static function pair( $name, $value ) {
		return array( 'name' => $name, 'value' => is_scalar( $value ) ? (string) $value : wp_json_encode( $value ) );
	}

	public static function export( $email, $page = 1 ) {
		$user = get_user_by( 'email', $email );
		if ( ! $user ) { return array( 'data' => array(), 'done' => true ); }
		$items = array();
		if ( 1 === (int) $page ) {
			$state = SCYS_Coach_Hub::state( $user->ID );
			$items[] = array(
				'group_id' => 'scys-hub-learning', 'group_label' => 'Coach Academy learning', 'item_id' => 'scys-learning-' . $user->ID,
				'data' => array(
					self::pair( 'Pathway', SCYS_Coach_Hub::track( $user->ID ) ),
					self::pair( 'Preferred age group', $state['age'] ),
					self::pair( 'Completed lessons', implode( ', ', (array) $state['completed'] ) ),
					self::pair( 'Quiz attempts', $state['attempts'] ),
				),
			);
			foreach ( (array) $state['reflections'] as $task_id => $entry ) {
				$items[] = array(
					'group_id' => 'scys-hub-reflections', 'group_label' => 'Coach Academy reflections', 'item_id' => 'scys-reflection-' . $user->ID . '-' . sanitize_key( $task_id ),
					'data' => array(
						self::pair( 'Task', $task_id ),
						self::pair( 'Submitted', $entry['submittedAt'] ?? '' ),
						self::pair( 'Status', $entry['status'] ?? '' ),
						self::pair( 'Reflection', $entry['text'] ?? '' ),
						self::pair( 'Mentor feedback', $entry['review']['text'] ?? '' ),
					),
				);
			}
			$mentor = (int) get_user_meta( $user->ID, '_scys_mentor_id', true );
			if ( $mentor && ( $m = get_userdata( $mentor ) ) ) {
				$items[] = array( 'group_id' => 'scys-hub-learning', 'group_label' => 'Coach Academy learning', 'item_id' => 'scys-mentor-' . $user->ID, 'data' => array( self::pair( 'Assigned mentor', $m->display_name ) ) );
			}
		}
		// Mentoring records, 25 per page.
		global $wpdb;
		$table = SCYS_Hub_Mentoring::table();
		$per = 25; $offset = ( max( 1, (int) $page ) - 1 ) * $per;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, payload FROM $table WHERE coach_id = %d ORDER BY id ASC LIMIT %d OFFSET %d", $user->ID, $per + 1, $offset ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$done = count( (array) $rows ) <= $per;
		foreach ( array_slice( (array) $rows, 0, $per ) as $row ) {
			$d = json_decode( $row->payload, true );
			if ( ! is_array( $d ) ) { continue; }
			unset( $d['receipts'] );
			$data = array();
			foreach ( array( 'status' => 'Status', 'seasonTeam' => 'Season / team', 'ageGroup' => 'Age group', 'topic' => 'Topic', 'type' => 'Type', 'proposedAt' => 'Proposed', 'location' => 'Location', 'arrangement' => 'Arrangement' ) as $key => $label ) {
				if ( isset( $d[ $key ] ) && '' !== $d[ $key ] ) { $data[] = self::pair( $label, $d[ $key ] ); }
			}
			if ( ! empty( $d['visits'] ) ) { $data[] = self::pair( 'Visits', array_values( (array) $d['visits'] ) ); }
			$items[] = array( 'group_id' => 'scys-hub-mentoring', 'group_label' => 'Coach Academy mentoring records', 'item_id' => 'scys-mentoring-' . (int) $row->id, 'data' => $data );
		}
		return array( 'data' => $items, 'done' => $done );
	}

	public static function erase( $email, $page = 1 ) {
		$user = get_user_by( 'email', $email );
		$out = array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true );
		if ( ! $user ) { return $out; }
		if ( delete_user_meta( $user->ID, SCYS_Coach_Hub::META ) ) { $out['items_removed'] = true; }
		if ( delete_user_meta( $user->ID, '_scys_mentor_id' ) ) { $out['items_removed'] = true; }
		global $wpdb;
		$table = SCYS_Hub_Mentoring::table();
		$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE coach_id = %d", $user->ID ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $count ) {
			if ( apply_filters( 'scys_hub_erase_mentoring', false, $user->ID ) ) {
				$removed = $wpdb->delete( $table, array( 'coach_id' => $user->ID ), array( '%d' ) );
				if ( false === $removed ) {
					$out['items_retained'] = true;
					$out['messages'][] = 'Mentoring deletion could not be confirmed. Records were retained; ask an authorised administrator to investigate.';
				} elseif ( $removed > 0 ) { $out['items_removed'] = true; }
			} else {
				$out['items_retained'] = true;
				$out['messages'][] = sprintf( '%d Coach Academy mentoring record(s) were retained. Follow the approved organisational retention and deletion procedure before removing them.', $count );
			}
		}
		return $out;
	}

	public static function policy_text() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) { return; }
		wp_add_privacy_policy_content( 'SCYS Coach Academy', wp_kses_post( '<p>Coach Academy stores, against your WordPress account: lessons completed, quiz attempts, the age group you choose, written reflections and mentor feedback. If mentoring is enabled, it also stores mentoring requests and visit notes you or your assigned mentor create. Mentors can see the learning records of coaches assigned to them; administrators can see all records. This plugin does not send these records to third parties. Please do not record information that identifies children.</p>' ) );
	}
}
SCYS_Hub_Privacy::init();
