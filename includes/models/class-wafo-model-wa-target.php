<?php
/**
 * WA Target model.
 *
 * @package WAFO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Data access layer for WhatsApp target numbers.
 *
 * @since 0.1.0
 */
class WAFO_Model_Wa_Target {

	/**
	 * Get table name.
	 *
	 * @return string
	 * @since 0.1.0
	 */
	private static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'wafo_wa_targets';
	}

	/**
	 * Get all targets for a form.
	 *
	 * @param int $form_id Form ID.
	 * @return array
	 * @since 0.1.0
	 */
	public static function get_by_form( $form_id ) {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE form_id = %d", $form_id )
		);
	}

	/**
	 * Insert a WA target.
	 *
	 * @param array $data Target data.
	 * @return int|false Insert ID or false.
	 * @since 0.1.0
	 */
	public static function insert( $data ) {
		global $wpdb;

		$result = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table_name(),
			array(
				'form_id'      => $data['form_id'],
				'phone_number' => $data['phone_number'],
				'label'        => isset( $data['label'] ) ? $data['label'] : null,
			),
			array( '%d', '%s', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Delete all targets for a form.
	 *
	 * @param int $form_id Form ID.
	 * @return bool
	 * @since 0.1.0
	 */
	public static function delete_by_form( $form_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->delete(
			self::table_name(),
			array( 'form_id' => $form_id ),
			array( '%d' )
		);
		return false !== $result;
	}

	/**
	 * Delete a single target.
	 *
	 * @param int $id Target ID.
	 * @return bool
	 * @since 0.1.0
	 */
	public static function delete( $id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->delete(
			self::table_name(),
			array( 'id' => $id ),
			array( '%d' )
		);
		return false !== $result;
	}
}
