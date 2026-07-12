<?php
/**
 * Submission Value model.
 *
 * @package WAFO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Data access layer for submission field values.
 *
 * @since 0.1.0
 */
class WAFO_Model_Submission_Value {

	/**
	 * Get table name.
	 *
	 * @return string
	 * @since 0.1.0
	 */
	private static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'wafo_submission_values';
	}

	/**
	 * Get all values for a submission.
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
			$wpdb->prepare( "SELECT * FROM {$table} WHERE submission_id = %d", $submission_id )
		);
	}

	/**
	 * Get values for multiple submissions (for search).
	 *
	 * @param array $submission_ids Array of submission IDs.
	 * @return array
	 * @since 0.1.0
	 */
	public static function get_by_submissions( $submission_ids ) {
		global $wpdb;
		$table = self::table_name();

		if ( empty( $submission_ids ) ) {
			return array();
		}

		$placeholders = implode( ',', array_fill( 0, count( $submission_ids ), '%d' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE submission_id IN ({$placeholders})", $submission_ids )
		);
	}

	/**
	 * Insert a submission value.
	 *
	 * @param array $data Value data.
	 * @return int|false Insert ID or false.
	 * @since 0.1.0
	 */
	public static function insert( $data ) {
		global $wpdb;

		$result = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table_name(),
			array(
				'submission_id' => $data['submission_id'],
				'field_id'      => $data['field_id'],
				'value'         => $data['value'],
			),
			array( '%d', '%d', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Insert multiple values in batch.
	 *
	 * @param array $values Array of value data arrays.
	 * @return bool
	 * @since 0.1.0
	 */
	public static function insert_batch( $values ) {
		global $wpdb;

		if ( empty( $values ) ) {
			return true;
		}

		$formats = array();
		$data     = array();
		foreach ( $values as $value ) {
			$data[] = array(
				'submission_id' => $value['submission_id'],
				'field_id'      => $value['field_id'],
				'value'         => $value['value'],
			);
			$formats[] = array( '%d', '%d', '%s' );
		}

		$result = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table_name(),
			$data,
			$formats
		);

		return false !== $result;
	}

	/**
	 * Delete all values for a submission.
	 *
	 * @param int $submission_id Submission ID.
	 * @return bool
	 * @since 0.1.0
	 */
	public static function delete_by_submission( $submission_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->delete(
			self::table_name(),
			array( 'submission_id' => $submission_id ),
			array( '%d' )
		);
		return false !== $result;
	}
}
