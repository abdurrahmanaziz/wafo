<?php
/**
 * WhatsApp number validation helper.
 *
 * @package WAFO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validates WhatsApp phone numbers.
 *
 * @since 0.1.0
 */
class WAFO_WA_Validator {

	/**
	 * Validate a WhatsApp number format.
	 *
	 * Accepts formats: 08xxxxxxxxxx, +628xxxxxxxxxx, 628xxxxxxxxxx, 8xxxxxxxxxx (10-13 digit).
	 *
	 * @param string $phone Phone number to validate.
	 * @return bool True if valid.
	 * @since 0.1.0
	 */
	public static function is_valid( $phone ) {
		$clean = preg_replace( '/[^0-9]/', '', $phone );

		// Must be 10-15 digits after stripping non-numeric characters.
		if ( strlen( $clean ) < 10 || strlen( $clean ) > 15 ) {
			return false;
		}

		// Must start with 0, 62, or +62.
		if ( ! preg_match( '/^(0|62)/', $clean ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Normalize a phone number to international format (62xxxxxxxxxxx).
	 *
	 * @param string $phone Phone number.
	 * @return string Normalized number.
	 * @since 0.1.0
	 */
	public static function normalize( $phone ) {
		$clean = preg_replace( '/[^0-9]/', '', $phone );

		// Already international format.
		if ( strpos( $clean, '62' ) === 0 ) {
			return $clean;
		}

		// Local format starting with 0.
		if ( strpos( $clean, '0' ) === 0 ) {
			return '62' . substr( $clean, 1 );
		}

		// Just the digits without prefix (e.g. 81234567890) — add 62 prefix.
		return '62' . $clean;
	}

	/**
	 * Get the error message for invalid phone.
	 *
	 * @return string Error message.
	 * @since 0.1.0
	 */
	public static function get_error_message() {
		return __( 'Nomor WhatsApp tidak valid. Gunakan format: 08xxxxxxxxxx atau +628xxxxxxxxxx.', 'wa-form-optin' );
	}

	/**
	 * Generate a WhatsApp click-to-chat link.
	 *
	 * @param string $phone   Phone number.
	 * @param string $message Message text.
	 * @return string WhatsApp link.
	 * @since 0.1.0
	 */
	public static function generate_wa_link( $phone, $message = '' ) {
		$normalized = self::normalize( $phone );
		$base_url   = 'https://wa.me/' . $normalized;

		if ( ! empty( $message ) ) {
			$base_url .= '?text=' . rawurlencode( $message );
		}

		return $base_url;
	}
}
