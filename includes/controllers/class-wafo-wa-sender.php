<?php
/**
 * WhatsApp sender controller.
 *
 * @package WAFO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles sending notifications via WhatsApp.
 *
 * @since 0.1.0
 */
class WAFO_WA_Sender {

	/**
	 * Send WA notification for a submission.
	 *
	 * @param int   $submission_id Submission ID.
	 * @param int   $form_id       Form ID.
	 * @param array $field_values  Cleaned field values.
	 * @param array $field_labels  Field labels.
	 * @since 0.1.0
	 */
	public static function send( $submission_id, $form_id, $field_values, $field_labels ) {
		$form = WAFO_Model_Form::get( $form_id );
		if ( ! $form ) {
			return;
		}

		$targets = WAFO_Model_Wa_Target::get_by_form( $form_id );
		if ( empty( $targets ) ) {
			WAFO_Model_Submission::update_wa_status( $submission_id, 'failed' );
			WAFO_Model_Submission_Log::insert( array(
				'submission_id' => $submission_id,
				'action'        => 'wa_failed',
				'new_value'     => 'failed',
			) );
			return;
		}

		// Parse template.
		$message = WAFO_Template_Parser::parse( $form->wa_message_template, $field_labels, $field_values );
		$message = apply_filters( 'wafo_wa_message', $message, $form_id, $submission_id );

		// Generate WA link for first target.
		$first_target = reset( $targets );
		$wa_link      = WAFO_WA_Validator::generate_wa_link( $first_target->phone_number, $message );

		/**
		 * Action fired when a WA link is generated.
		 *
		 * @param string $wa_link       Generated WA link.
		 * @param int    $submission_id Submission ID.
		 * @param object $first_target  WA target object.
		 * @param string $message       Formatted message.
		 */
		do_action( 'wafo_before_wa_send', $wa_link, $submission_id, $first_target, $message );

		// Update submission WA status.
		WAFO_Model_Submission::update_wa_status( $submission_id, 'sent' );

		// Log WA send.
		WAFO_Model_Submission_Log::insert( array(
			'submission_id' => $submission_id,
			'action'        => 'wa_sent',
			'new_value'     => 'sent',
		) );
	}

	/**
	 * Generate a WA link for a submission.
	 *
	 * @param int $submission_id Submission ID.
	 * @return string|false WA link or false.
	 * @since 0.1.0
	 */
	public static function generate_link( $submission_id ) {
		$submission = WAFO_Model_Submission::get( $submission_id );
		if ( ! $submission ) {
			return false;
		}

		$form    = WAFO_Model_Form::get( $submission->form_id );
		$targets = WAFO_Model_Wa_Target::get_by_form( $submission->form_id );
		if ( ! $form || empty( $targets ) ) {
			return false;
		}

		$fields  = WAFO_Model_Field::get_by_form( $submission->form_id );
		$values  = WAFO_Model_Submission_Value::get_by_submission( $submission_id );

		$values_map = array();
		foreach ( $values as $v ) {
			$values_map[ (string) $v->field_id ] = $v->value;
		}

		$labels  = array();
		$v_array = array();

		if ( ! empty( $fields ) ) {
			usort( $fields, function ( $a, $b ) {
				return (int) $a->order_index - (int) $b->order_index;
			});

			foreach ( $fields as $f ) {
				$labels[] = $f->label;
				$v_array[] = isset( $values_map[ (string) $f->id ] ) ? $values_map[ (string) $f->id ] : '';
			}
		}

		$message = WAFO_Template_Parser::parse( $form->wa_message_template, $labels, $v_array );
		return WAFO_WA_Validator::generate_wa_link( $targets[0]->phone_number, $message );
	}
}
