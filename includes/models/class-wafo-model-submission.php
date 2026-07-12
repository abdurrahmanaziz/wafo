<?php
/**
 * Submission model.
 *
 * @package WAFO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Data access layer for submissions.
 *
 * @since 0.1.0
 */
class WAFO_Model_Submission {

	/**
	 * Get table name.
	 *
	 * @return string
	 * @since 0.1.0
	 */
	private static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'wafo_submissions';
	}

	/**
	 * Get a submission by ID.
	 *
	 * @param int $id Submission ID.
	 * @return object|null
	 * @since 0.1.0
	 */
	public static function get( $id ) {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id ) );
	}

	/**
	 * Get submissions with pagination, filtering, and search.
	 *
	 * @param array $args Query arguments.
	 * @return array {results: array, total: int, total_pages: int}
	 * @since 0.1.0
	 */
	public static function get_list( $args = array() ) {
		global $wpdb;
		$table  = self::table_name();
		$defaults = array(
			'form_id'   => 0,
			'status'    => '',
			'search'    => '',
			'date_from' => '',
			'date_to'   => '',
			'orderby'   => 'created_at',
			'order'     => 'DESC',
			'page'      => 1,
			'per_page'  => 20,
		);
		$args   = wp_parse_args( $args, $defaults );

		$where   = array( 'deleted_at IS NULL' );
		$prepare = array();

		if ( ! empty( $args['form_id'] ) ) {
			$where[]   = 'form_id = %d';
			$prepare[] = $args['form_id'];
		}

		if ( ! empty( $args['status'] ) && in_array( $args['status'], array( 'baru', 'dihubungi', 'selesai' ), true ) ) {
			$where[]   = 'status = %s';
			$prepare[] = $args['status'];
		}

		if ( ! empty( $args['date_from'] ) ) {
			$where[]   = 'created_at >= %s';
			$prepare[] = $args['date_from'] . ' 00:00:00';
		}

		if ( ! empty( $args['date_to'] ) ) {
			$where[]   = 'created_at <= %s';
			$prepare[] = $args['date_to'] . ' 23:59:59';
		}

		$where_sql    = implode( ' AND ', $where );
		$allowed_sort = array( 'id', 'created_at', 'status' );
		$orderby      = in_array( $args['orderby'], $allowed_sort, true ) ? $args['orderby'] : 'created_at';
		$order        = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
		$offset       = max( 0, ( (int) $args['page'] - 1 ) * (int) $args['per_page'] );

		$count_query = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		$total       = ! empty( $prepare ) ? (int) $wpdb->get_var( $wpdb->prepare( $count_query, $prepare ) ) : (int) $wpdb->get_var( $count_query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$query = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$prepare[] = (int) $args['per_page'];
		$prepare[] = $offset;

		$results = $wpdb->get_results( $wpdb->prepare( $query, $prepare ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$total_pages = ceil( $total / $args['per_page'] );

		return array(
			'results'     => $results,
			'total'       => $total,
			'total_pages' => $total_pages,
		);
	}

	/**
	 * Insert a new submission.
	 *
	 * @param array $data Submission data.
	 * @return int|false Insert ID or false.
	 * @since 0.1.0
	 */
	public static function insert( $data ) {
		global $wpdb;
		$defaults = array(
			'form_id'        => 0,
			'status'         => 'baru',
			'wa_send_status' => 'pending',
			'ip_address'     => '',
		);
		$data = wp_parse_args( $data, $defaults );

		$result = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table_name(),
			array(
				'form_id'        => $data['form_id'],
				'status'         => $data['status'],
				'wa_send_status' => $data['wa_send_status'],
				'ip_address'     => $data['ip_address'],
				'created_at'     => current_time( 'mysql' ),
				'updated_at'     => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update submission status.
	 *
	 * @param int    $id     Submission ID.
	 * @param string $status New status.
	 * @return bool
	 * @since 0.1.0
	 */
	public static function update_status( $id, $status ) {
		global $wpdb;

		$result = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table_name(),
			array(
				'status'    => $status,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Update WA send status.
	 *
	 * @param int    $id     Submission ID.
	 * @param string $status New WA send status.
	 * @return bool
	 * @since 0.1.0
	 */
	public static function update_wa_status( $id, $status ) {
		global $wpdb;

		$result = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table_name(),
			array(
				'wa_send_status' => $status,
				'updated_at'     => current_time( 'mysql' ),
			),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Soft delete a submission.
	 *
	 * @param int $id Submission ID.
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
