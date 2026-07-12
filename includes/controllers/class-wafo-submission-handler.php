<?php
/**
 * Submission handler controller.
 *
 * @package WAFO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles form submission processing.
 *
 * @since 0.1.0
 */
class WAFO_Submission_Handler {

	/**
	 * Process a form submission.
	 *
	 * @param int   $form_id Form ID.
	 * @param array $raw_data Raw submitted data.
	 * @param string $ip_address Client IP.
	 * @return array|WP_Error Submission data or error.
	 * @since 0.1.0
	 */
	public static function process( $form_id, $raw_data, $ip_address ) {
		$form = WAFO_Model_Form::get( $form_id );
		if ( ! $form || 'active' !== $form->status ) {
			return new WP_Error( 'wafo_form_not_found', __( 'Form tidak ditemukan atau tidak aktif.', 'wa-form-optin' ) );
		}

		// Anti-spam: honeypot check.
		if ( ! empty( $raw_data['wafo_honeypot'] ) ) {
			return new WP_Error( 'wafo_spam_detected', __( 'Spam terdeteksi.', 'wa-form-optin' ), array( 'status' => 429 ) );
		}

		// Anti-spam: hidden token check (optional — tolerates missing/invalid nonce for cache/CDN compatibility).
		if ( ! empty( $raw_data['wafo_token'] ) ) {
			wp_verify_nonce( $raw_data['wafo_token'], 'wafo_submit_' . $form_id ); // phpcs:ignore WordPress.Security.NonceVerification
			// Nonce invalid: still allow submission but log warning.
			// This ensures public forms work with page cache / CDN.
		}

		// Rate limit check.
		if ( WAFO_Rate_Limiter::is_rate_limited( $ip_address, $form_id ) ) {
			return new WP_Error( 'wafo_rate_limited', __( 'Terlalu banyak pengiriman. Silakan coba lagi nanti.', 'wa-form-optin' ), array( 'status' => 429 ) );
		}

		// Get form fields.
		$fields = WAFO_Model_Field::get_by_form( $form_id );
		if ( empty( $fields ) ) {
			return new WP_Error( 'wafo_no_fields', __( 'Form tidak memiliki field.', 'wa-form-optin' ) );
		}

		// Validate and sanitize field values.
		$clean_values = array();
		$field_labels = array();
		foreach ( $fields as $field ) {
			$field_label = $field->label;
			$field_slug  = strtolower( sanitize_title( $field_label ) );
			$value       = isset( $raw_data[ $field_slug ] ) ? $raw_data[ $field_slug ] : '';

			// Required check.
			if ( $field->is_required && empty( $value ) ) {
				return new WP_Error(
					'wafo_field_required',
					sprintf( __( 'Field "%s" wajib diisi.', 'wa-form-optin' ), $field_label ),
					array( 'status' => 422 )
				);
			}

			// Phone validation.
			if ( 'phone' === $field->field_type && ! empty( $value ) ) {
				if ( ! WAFO_WA_Validator::is_valid( $value ) ) {
					return new WP_Error(
						'wafo_invalid_wa_number',
						WAFO_WA_Validator::get_error_message(),
						array( 'status' => 422 )
					);
				}
			}

			// Email validation.
			if ( 'email' === $field->field_type && ! empty( $value ) ) {
				$sanitized_email = WAFO_Sanitizer::email( $value );
				if ( ! is_email( $sanitized_email ) ) {
					return new WP_Error(
						'wafo_invalid_email',
						__( 'Format email tidak valid.', 'wa-form-optin' ),
						array( 'status' => 422 )
					);
				}
				$value = $sanitized_email;
			}

			$clean_values[] = WAFO_Sanitizer::submission_value( $value, $field->field_type );
			$field_labels[] = $field_label;
		}

		// Rate limit: increment counter.
		WAFO_Rate_Limiter::increment( $ip_address, $form_id );

		// Insert submission.
		$submission_id = WAFO_Model_Submission::insert( array(
			'form_id'    => $form_id,
			'ip_address' => $ip_address,
		) );

		if ( ! $submission_id ) {
			return new WP_Error( 'wafo_db_error', __( 'Gagal menyimpan submission.', 'wa-form-optin' ), array( 'status' => 500 ) );
		}

		// Insert submission values.
		$values_data = array();
		foreach ( $fields as $index => $field ) {
			$values_data[] = array(
				'submission_id' => $submission_id,
				'field_id'      => $field->id,
				'value'         => $clean_values[ $index ],
			);
		}
		WAFO_Model_Submission_Value::insert_batch( $values_data );

		// Log creation.
		WAFO_Model_Submission_Log::insert( array(
			'submission_id' => $submission_id,
			'action'        => 'created',
			'new_value'     => 'baru',
		) );

		// Fire action for WA sender.
		do_action( 'wafo_after_submission_created', $submission_id, $form_id, $clean_values, $field_labels );

		return array(
			'submission_id' => $submission_id,
			'form_id'       => $form_id,
		);
	}

	/**
	 * Update submission follow-up status.
	 *
	 * @param int    $id     Submission ID.
	 * @param string $status New status.
	 * @return bool
	 * @since 0.1.0
	 */
	public static function update_status( $id, $status ) {
		$submission = WAFO_Model_Submission::get( $id );
		if ( ! $submission ) {
			return false;
		}

		$old_status  = $submission->status;
		$valid       = array( 'baru', 'dihubungi', 'selesai' );
		if ( ! in_array( $status, $valid, true ) ) {
			return false;
		}

		$updated = WAFO_Model_Submission::update_status( $id, $status );
		if ( $updated ) {
			WAFO_Model_Submission_Log::insert( array(
				'submission_id' => $id,
				'actor_id'      => get_current_user_id(),
				'action'        => 'status_changed',
				'old_value'     => $old_status,
				'new_value'     => $status,
			) );
		}

		return $updated;
	}

	/**
	 * Get a submission with all its values and logs.
	 *
	 * @param int $id Submission ID.
	 * @return object|null
	 * @since 0.1.0
	 */
	public static function get_with_details( $id ) {
		$submission = WAFO_Model_Submission::get( $id );
		if ( ! $submission ) {
			return null;
		}

		$submission->values = WAFO_Model_Submission_Value::get_by_submission( $id );
		$submission->logs   = WAFO_Model_Submission_Log::get_by_submission( $id );

		return $submission;
	}
}
