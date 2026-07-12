<?php
/**
 * Submission Log model.
 *
 * @package WAFO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Data access layer for submission audit logs.
 *
 * @since 0.1.0
 */
class WAFO_Model_Submission_Log {

	/**
	 * Get table name.
	 *
	 * @return string
	 * @since 0.1.0
	 */
	private static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'wafo_submission_logs';
	}

	/**
	 * Get logs for a submission.
	 *
	 * @param int $submission_id Submission ID.
	 * @return array
	 * @since 0.1.0
	 */
	public static function get_by_submission( $submission_id ) {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE submission_id = %d ORDER BY created_at DESC", $submission_id )
		);
	}

	/**
	 * Insert a log entry.
	 *
	 * @param array $data Log data.
	 * @return int|false Insert ID or false.
	 * @since 0.1.0
	 */
	public static function insert( $data ) {
		global $wpdb;
		$defaults = array(
			'submission_id' => 0,
			'actor_id'      => null,
			'action'        => '',
			'old_value'     => null,
			'new_value'     => null,
		);
		$data = wp_parse_args( $data, $defaults );

		$result = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table_name(),
			array(
				'submission_id' => $data['submission_id'],
				'actor_id'      => $data['actor_id'],
				'action'        => $data['action'],
				'old_value'     => $data['old_value'],
				'new_value'     => $data['new_value'],
			),
			array( '%d', '%d', '%s', '%s', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}
}
