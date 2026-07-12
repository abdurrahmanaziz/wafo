<?php
/**
 * Rate limiter for form submissions.
 *
 * @package WAFO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rate limiting for public form submission endpoints.
 *
 * @since 0.1.0
 */
class WAFO_Rate_Limiter {

	/**
	 * Transient key prefix.
	 *
	 * @var string
	 */
	const TRANSIENT_PREFIX = 'wafo_rl_';

	/**
	 * Default max submissions per window.
	 *
	 * @var int
	 */
	const DEFAULT_MAX_SUBMISSIONS = 5;

	/**
	 * Default time window in seconds (10 minutes).
	 *
	 * @var int
	 */
	const DEFAULT_WINDOW = 600;

	/**
	 * Check if the IP has exceeded the rate limit.
	 *
	 * @param string $ip_address Client IP address.
	 * @param int    $form_id    Form ID.
	 * @return bool True if rate limited.
	 * @since 0.1.0
	 */
	public static function is_rate_limited( $ip_address, $form_id ) {
		$key       = self::TRANSIENT_PREFIX . md5( $ip_address . '_' . $form_id );
		$counter   = get_transient( $key );
		$max       = apply_filters( 'wafo_rate_limit_max', self::DEFAULT_MAX_SUBMISSIONS );
		$window    = apply_filters( 'wafo_rate_limit_window', self::DEFAULT_WINDOW );

		if ( false === $counter ) {
			return false;
		}

		if ( (int) $counter >= $max ) {
			return true;
		}

		return false;
	}

	/**
	 * Increment the counter for an IP + form combination.
	 *
	 * @param string $ip_address Client IP address.
	 * @param int    $form_id    Form ID.
	 * @since 0.1.0
	 */
	public static function increment( $ip_address, $form_id ) {
		$key     = self::TRANSIENT_PREFIX . md5( $ip_address . '_' . $form_id );
		$counter = get_transient( $key );
		$window  = apply_filters( 'wafo_rate_limit_window', self::DEFAULT_WINDOW );

		if ( false === $counter ) {
			set_transient( $key, 1, $window );
		} else {
			set_transient( $key, (int) $counter + 1, $window );
		}
	}

	/**
	 * Cleanup expired rate limit transients.
	 * Called via WP-Cron.
	 *
	 * @since 0.1.0
	 */
	public static function cleanup() {
		global $wpdb;

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE '_transient_wafo_rl_%'
			   OR option_name LIKE '_transient_timeout_wafo_rl_%'"
		);
	}

	/**
	 * Get remaining allowed submissions for an IP.
	 *
	 * @param string $ip_address Client IP address.
	 * @param int    $form_id    Form ID.
	 * @return int Remaining allowed submissions.
	 * @since 0.1.0
	 */
	public static function get_remaining( $ip_address, $form_id ) {
		$key     = self::TRANSIENT_PREFIX . md5( $ip_address . '_' . $form_id );
		$counter = (int) get_transient( $key );
		$max     = apply_filters( 'wafo_rate_limit_max', self::DEFAULT_MAX_SUBMISSIONS );

		return max( 0, $max - $counter );
	}
}
