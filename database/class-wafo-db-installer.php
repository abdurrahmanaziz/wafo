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
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(191) NOT NULL,
			wa_message_template TEXT NOT NULL,
			status ENUM('active','inactive') DEFAULT 'active',
			created_by BIGINT UNSIGNED NOT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			deleted_at DATETIME NULL,
			PRIMARY KEY (id),
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
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			form_id BIGINT UNSIGNED NOT NULL,
			label VARCHAR(191) NOT NULL,
			field_type VARCHAR(50) NOT NULL,
			is_required TINYINT(1) DEFAULT 0,
			order_index INT DEFAULT 0,
			options TEXT NULL,
			PRIMARY KEY (id),
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
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			form_id BIGINT UNSIGNED NOT NULL,
			phone_number VARCHAR(20) NOT NULL,
			label VARCHAR(100) NULL,
			PRIMARY KEY (id),
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
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			form_id BIGINT UNSIGNED NOT NULL,
			status ENUM('baru','dihubungi','selesai') DEFAULT 'baru',
			wa_send_status ENUM('pending','sent','failed') DEFAULT 'pending',
			ip_address VARCHAR(45) NOT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			deleted_at DATETIME NULL,
			PRIMARY KEY (id),
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
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			submission_id BIGINT UNSIGNED NOT NULL,
			field_id BIGINT UNSIGNED NOT NULL,
			value TEXT,
			PRIMARY KEY (id),
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
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			submission_id BIGINT UNSIGNED NOT NULL,
			actor_id BIGINT UNSIGNED NULL,
			action VARCHAR(100) NOT NULL,
			old_value VARCHAR(191) NULL,
			new_value VARCHAR(191) NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_submission_id (submission_id),
			KEY idx_created_at (created_at)
		) {$charset_collate};";

		dbDelta( $sql );
	}
}
