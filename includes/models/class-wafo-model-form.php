<?php
/**
 * Form model.
 *
 * @package WAFO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Data access layer for forms.
 *
 * @since 0.1.0
 */
class WAFO_Model_Form {

	/**
	 * Get table name.
	 *
	 * @return string
	 * @since 0.1.0
	 */
	private static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'wafo_forms';
	}

	/**
	 * Get a single form by ID.
	 *
	 * @param int $id Form ID.
	 * @return object|null Form object or null.
	 * @since 0.1.0
	 */
	public static function get( $id ) {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id ) );
	}

	/**
	 * Get all active forms.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public static function get_all() {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results( "SELECT * FROM {$table} WHERE deleted_at IS NULL ORDER BY id DESC" );
	}

	/**
	 * Insert a new form.
	 *
	 * @param array $data Form data.
	 * @return int|false Insert ID or false.
	 * @since 0.1.0
	 */
	public static function insert( $data ) {
		global $wpdb;
		$defaults = array(
			'name'                => '',
			'wa_message_template' => '',
			'status'              => 'active',
			'created_by'          => get_current_user_id(),
		);
		$data = wp_parse_args( $data, $defaults );

		$result = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table_name(),
			array(
				'name'                => $data['name'],
				'wa_message_template' => $data['wa_message_template'],
				'status'              => $data['status'],
				'created_by'          => $data['created_by'],
			),
			array( '%s', '%s', '%s', '%d' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update an existing form.
	 *
	 * @param int   $id   Form ID.
	 * @param array $data Form data.
	 * @return bool
	 * @since 0.1.0
	 */
	public static function update( $id, $data ) {
		global $wpdb;
		$allowed = array_intersect_key(
			$data,
			array(
				'name'                => true,
				'wa_message_template' => true,
				'status'              => true,
			)
		);

		if ( empty( $allowed ) ) {
			return false;
		}

		$formats = array();
		foreach ( $allowed as $key => $value ) {
			$formats[ $key ] = is_numeric( $value ) ? '%d' : '%s';
		}

		$result = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table_name(),
			$allowed,
			array( 'id' => $id ),
			array_values( $formats ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Soft delete a form.
	 *
	 * @param int $id Form ID.
	 * @return bool
	 * @since 0.1.0
	 */
	public static function delete( $id ) {
		global $wpdb;

		$result = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table_name(),
			array( 'deleted_at' => current_time( 'mysql' ) ),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);

		return false !== $result;
	}
}
