<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package WAFO
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$tables = array(
	'wafo_submission_logs',
	'wafo_submission_values',
	'wafo_submissions',
	'wafo_wa_targets',
	'wafo_fields',
	'wafo_forms',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

delete_option( 'wafo_db_version' );
delete_option( 'wafo_settings' );
delete_transient( 'wafo_rate_limits' );
