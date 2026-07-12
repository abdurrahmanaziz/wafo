<?php
/**
 * Database installer for creating custom tables.
 *
 * @package WAFO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles creation of custom database tables.
 *
 * @since 0.1.0
 */
class WAFO_DB_Installer {

	/**
	 * Create all custom tables.
	 *
	 * @since 0.1.0
	 */
	public function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$this->create_forms_table( $charset_collate );
		$this->create_fields_table( $charset_collate );
		$this->create_wa_targets_table( $charset_collate );
		$this->create_submissions_table( $charset_collate );
		$this->create_submission_values_table( $charset_collate );
		$this->create_submission_logs_table( $charset_collate );
	}

	/**
	 * Create wp_wafo_forms table.
	 *
	 * @param string $charset_collate Database charset collation.
	 * @since 0.1.0
	 */
	private function create_forms_table( $charset_collate ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wafo_forms';

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(191) NOT NULL,
			wa_message_template text NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			created_by bigint(20) unsigned NOT NULL,
			created_at datetime DEFAULT NULL,
			updated_at datetime DEFAULT NULL,
			deleted_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY idx_status (status),
			KEY idx_deleted_at (deleted_at)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Create wp_wafo_fields table.
	 *
	 * @param string $charset_collate Database charset collation.
	 * @since 0.1.0
	 */
	private function create_fields_table( $charset_collate ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wafo_fields';

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			form_id bigint(20) unsigned NOT NULL,
			label varchar(191) NOT NULL,
			field_type varchar(50) NOT NULL,
			is_required tinyint(1) NOT NULL DEFAULT 0,
			order_index int(11) NOT NULL DEFAULT 0,
			options text DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY idx_form_id (form_id)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Create wp_wafo_wa_targets table.
	 *
	 * @param string $charset_collate Database charset collation.
	 * @since 0.1.0
	 */
	private function create_wa_targets_table( $charset_collate ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wafo_wa_targets';

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			form_id bigint(20) unsigned NOT NULL,
			phone_number varchar(20) NOT NULL,
			label varchar(100) DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY idx_form_id (form_id)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Create wp_wafo_submissions table.
	 *
	 * @param string $charset_collate Database charset collation.
	 * @since 0.1.0
	 */
	private function create_submissions_table( $charset_collate ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wafo_submissions';

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			form_id bigint(20) unsigned NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'baru',
			wa_send_status varchar(20) NOT NULL DEFAULT 'pending',
			ip_address varchar(45) NOT NULL,
			created_at datetime DEFAULT NULL,
			updated_at datetime DEFAULT NULL,
			deleted_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY idx_form_id (form_id),
			KEY idx_status (status),
			KEY idx_created_at (created_at),
			KEY idx_deleted_at (deleted_at)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Create wp_wafo_submission_values table.
	 *
	 * @param string $charset_collate Database charset collation.
	 * @since 0.1.0
	 */
	private function create_submission_values_table( $charset_collate ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wafo_submission_values';

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			submission_id bigint(20) unsigned NOT NULL,
			field_id bigint(20) unsigned NOT NULL,
			value text DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY idx_submission_id (submission_id),
			KEY idx_field_id (field_id)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Create wp_wafo_submission_logs table.
	 *
	 * @param string $charset_collate Database charset collation.
	 * @since 0.1.0
	 */
	private function create_submission_logs_table( $charset_collate ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wafo_submission_logs';

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			submission_id bigint(20) unsigned NOT NULL,
			actor_id bigint(20) unsigned DEFAULT NULL,
			action varchar(100) NOT NULL,
			old_value varchar(191) DEFAULT NULL,
			new_value varchar(191) DEFAULT NULL,
			created_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY idx_submission_id (submission_id),
			KEY idx_created_at (created_at)
		) {$charset_collate};";

		dbDelta( $sql );
	}
}
