<?php
/**
 * WA message template parser.
 *
 * @package WAFO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parses WA message templates and replaces placeholders with field values.
 *
 * @since 0.1.0
 */
class WAFO_Template_Parser {

	/**
	 * Parse a template string by replacing {field_label} placeholders with values.
	 *
	 * @param string $template Template with {label} placeholders.
	 * @param array  $field_labels Array of field labels.
	 * @param array  $field_values Array of field values (same order as labels).
	 * @return string Parsed message.
	 * @since 0.1.0
	 */
	public static function parse( $template, $field_labels, $field_values ) {
		if ( empty( $template ) || empty( $field_labels ) ) {
			return $template;
		}

		$search  = array();
		$replace = array();

		foreach ( $field_labels as $index => $label ) {
			$placeholder = '{' . strtolower( sanitize_title( $label ) ) . '}';
			$search[]     = $placeholder;
			$value       = isset( $field_values[ $index ] ) ? $field_values[ $index ] : '';
			$replace[]   = $value;
		}

		// Also support {field_label} format with spaces.
		$search_alt  = array();
		$replace_alt = array();
		foreach ( $field_labels as $index => $label ) {
			$placeholder   = '{' . $label . '}';
			$search_alt[]   = $placeholder;
			$value         = isset( $field_values[ $index ] ) ? $field_values[ $index ] : '';
			$replace_alt[] = $value;
		}

		$message = str_replace( $search_alt, $replace_alt, $template );
		$message = str_replace( $search, $replace, $message );

		return $message;
	}

	/**
	 * Extract placeholder names from a template.
	 *
	 * @param string $template Template string.
	 * @return array List of placeholder names.
	 * @since 0.1.0
	 */
	public static function get_placeholders( $template ) {
		$placeholders = array();
		if ( preg_match_all( '/\{([^}]+)\}/', $template, $matches ) ) {
			$placeholders = $matches[1];
		}
		return $placeholders;
	}

	/**
	 * Generate a default template from field labels.
	 *
	 * @param array $field_labels Array of field labels.
	 * @return string Default template.
	 * @since 0.1.0
	 */
	public static function generate_default( $field_labels ) {
		$template = "Form Submission:\n";
		foreach ( $field_labels as $label ) {
			$slug             = strtolower( sanitize_title( $label ) );
			$template        .= $label . ': {' . $slug . "}\n";
		}
		return trim( $template );
	}
}
