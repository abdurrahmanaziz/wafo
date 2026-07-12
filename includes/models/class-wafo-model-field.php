<?php
/**
 * Field model.
 *
 * @package WAFO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Data access layer for form fields.
 *
 * @since 0.1.0
 */
class WAFO_Model_Field {

	/**
	 * Get table name.
	 *
	 * @return string
	 * @since 0.1.0
	 */
	private static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'wafo_fields';
	}

	/**
	 * Get a field by ID.
	 *
	 * @param int $id Field ID.
	 * @return object|null
	 * @since 0.1.0
	 */
	public static function get( $id ) {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
	}

	/**
	 * Get all fields for a form.
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
			$wpdb->prepare( "SELECT * FROM {$table} WHERE form_id = %d ORDER BY order_index ASC", $form_id )
		);
	}

	/**
	 * Insert a new field.
	 *
	 * @param array $data Field data.
	 * @return int|false Insert ID or false.
	 * @since 0.1.0
	 */
	public static function insert( $data ) {
		global $wpdb;
		$defaults = array(
			'form_id'     => 0,
			'label'       => '',
			'field_type'  => 'text',
			'is_required' => 0,
			'order_index' => 0,
			'options'     => null,
		);
		$data = wp_parse_args( $data, $defaults );

		$result = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table_name(),
			array(
				'form_id'     => $data['form_id'],
				'label'       => $data['label'],
				'field_type'  => $data['field_type'],
				'is_required' => $data['is_required'],
				'order_index' => $data['order_index'],
				'options'     => $data['options'],
			),
			array( '%d', '%s', '%s', '%d', '%d', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update an existing field.
	 *
	 * @param int   $id   Field ID.
	 * @param array $data Field data.
	 * @return bool
	 * @since 0.1.0
	 */
	public static function update( $id, $data ) {
		global $wpdb;
		$allowed = array_intersect_key(
			$data,
			array(
				'label'       => true,
				'field_type'  => true,
				'is_required' => true,
				'order_index' => true,
				'options'     => true,
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
	 * Delete all fields for a form.
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
	 * Delete a single field.
	 *
	 * @param int $id Field ID.
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
