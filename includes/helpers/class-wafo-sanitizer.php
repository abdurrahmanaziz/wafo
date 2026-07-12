<?php
/**
 * Input sanitizer helper.
 *
 * @package WAFO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitizes user input data for the plugin.
 *
 * @since 0.1.0
 */
class WAFO_Sanitizer {

	/**
	 * Sanitize a string field.
	 *
	 * @param string $input Raw input.
	 * @return string Sanitized string.
	 * @since 0.1.0
	 */
	public static function text( $input ) {
		return sanitize_text_field( wp_unslash( $input ) );
	}

	/**
	 * Sanitize a textarea field.
	 *
	 * @param string $input Raw input.
	 * @return string Sanitized string.
	 * @since 0.1.0
	 */
	public static function textarea( $input ) {
		return sanitize_textarea_field( wp_unslash( $input ) );
	}

	/**
	 * Sanitize an email field.
	 *
	 * @param string $input Raw input.
	 * @return string Sanitized email.
	 * @since 0.1.0
	 */
	public static function email( $input ) {
		return sanitize_email( wp_unslash( $input ) );
	}

	/**
	 * Sanitize a phone number (strip non-numeric characters).
	 *
	 * @param string $input Raw input.
	 * @return string Sanitized phone number.
	 * @since 0.1.0
	 */
	public static function phone( $input ) {
		$clean = preg_replace( '/[^0-9+\-\s()]/', '', wp_unslash( $input ) );
		return trim( $clean );
	}

	/**
	 * Sanitize an integer field.
	 *
	 * @param mixed  $input Raw input.
	 * @param int    $default Default value.
	 * @return int Sanitized integer.
	 * @since 0.1.0
	 */
	public static function integer( $input, $default = 0 ) {
		return absint( $input ) ? absint( $input ) : $default;
	}

	/**
	 * Sanitize a select/radio field value.
	 *
	 * @param string $input    Raw input.
	 * @param array  $allowed  Allowed values.
	 * @return string Sanitized value or empty string.
	 * @since 0.1.0
	 */
	public static function select( $input, $allowed = array() ) {
		$clean = sanitize_text_field( wp_unslash( $input ) );
		if ( in_array( $clean, $allowed, true ) ) {
			return $clean;
		}
		return '';
	}

	/**
	 * Sanitize JSON options for select/radio fields.
	 *
	 * @param string $input Raw JSON string.
	 * @return string Sanitized JSON or empty.
	 * @since 0.1.0
	 */
	public static function json_options( $input ) {
		$decoded = json_decode( $input, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return '';
		}
		if ( ! is_array( $decoded ) ) {
			return '';
		}
		$clean = array_map( 'sanitize_text_field', $decoded );
		return wp_json_encode( $clean );
	}

	/**
	 * Sanitize a URL field.
	 *
	 * @param string $input Raw URL.
	 * @return string Sanitized URL.
	 * @since 0.1.0
	 */
	public static function url( $input ) {
		return esc_url_raw( wp_unslash( $input ) );
	}

	/**
	 * Sanitize submission field value based on field type.
	 *
	 * @param string $value     Raw value.
	 * @param string $field_type Field type.
	 * @return string Sanitized value.
	 * @since 0.1.0
	 */
	public static function submission_value( $value, $field_type ) {
		switch ( $field_type ) {
			case 'email':
				return self::email( $value );
			case 'phone':
				return self::phone( $value );
			case 'textarea':
				return self::textarea( $value );
			case 'select':
			case 'radio':
				return self::text( $value );
			default:
				return self::text( $value );
		}
	}
}
