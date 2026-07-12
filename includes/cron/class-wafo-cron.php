<?php
/**
 * WP-Cron jobs.
 *
 * @package WAFO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schedule cron events on plugin activation.
 *
 * @since 0.1.0
 */
function wafo_schedule_cron() {
	if ( ! wp_next_scheduled( 'wafo_cleanup_rate_limits' ) ) {
		wp_schedule_event( time(), 'twicedaily', 'wafo_cleanup_rate_limits' );
	}
}
add_action( 'wp', 'wafo_schedule_cron' );

/**
 * Cleanup expired rate limit transients.
 *
 * @since 0.1.0
 */
function wafo_cleanup_rate_limits_handler() {
	WAFO_Rate_Limiter::cleanup();
}
add_action( 'wafo_cleanup_rate_limits', 'wafo_cleanup_rate_limits_handler' );
