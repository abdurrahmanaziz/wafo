<?php
/**
 * Form handler controller.
 *
 * @package WAFO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles form CRUD operations.
 *
 * @since 0.1.0
 */
class WAFO_Form_Handler {

	/**
	 * Create a form with fields and WA targets.
	 *
	 * @param array $data Form data including fields and wa_targets.
	 * @return int|false Form ID or false.
	 * @since 0.1.0
	 */
	public static function create( $data ) {
		$form_id = WAFO_Model_Form::insert( array(
			'name'                => WAFO_Sanitizer::text( $data['name'] ),
			'wa_message_template' => WAFO_Sanitizer::textarea( $data['wa_message_template'] ),
		) );

		if ( ! $form_id ) {
			return false;
		}

		if ( ! empty( $data['fields'] ) && is_array( $data['fields'] ) ) {
			self::sync_fields( $form_id, $data['fields'] );
		}

		if ( ! empty( $data['wa_targets'] ) && is_array( $data['wa_targets'] ) ) {
			self::sync_wa_targets( $form_id, $data['wa_targets'] );
		}

		return $form_id;
	}

	/**
	 * Update a form with fields and WA targets.
	 *
	 * @param int   $id   Form ID.
	 * @param array $data Form data.
	 * @return bool
	 * @since 0.1.0
	 */
	public static function update( $id, $data ) {
		$form = WAFO_Model_Form::get( $id );
		if ( ! $form ) {
			return false;
		}

		$update_data = array();
		if ( isset( $data['name'] ) ) {
			$update_data['name'] = WAFO_Sanitizer::text( $data['name'] );
		}
		if ( isset( $data['wa_message_template'] ) ) {
			$update_data['wa_message_template'] = WAFO_Sanitizer::textarea( $data['wa_message_template'] );
		}
		if ( isset( $data['status'] ) ) {
			$update_data['status'] = WAFO_Sanitizer::select( $data['status'], array( 'active', 'inactive' ) );
		}

		if ( ! empty( $update_data ) ) {
			WAFO_Model_Form::update( $id, $update_data );
		}

		if ( isset( $data['fields'] ) && is_array( $data['fields'] ) ) {
			self::sync_fields( $id, $data['fields'] );
		}

		if ( isset( $data['wa_targets'] ) && is_array( $data['wa_targets'] ) ) {
			self::sync_wa_targets( $id, $data['wa_targets'] );
		}

		return true;
	}

	/**
	 * Delete a form and all related data.
	 *
	 * @param int $id Form ID.
	 * @return bool
	 * @since 0.1.0
	 */
	public static function delete( $id ) {
		$form = WAFO_Model_Form::get( $id );
		if ( ! $form ) {
			return false;
		}

		return WAFO_Model_Form::delete( $id );
	}

	/**
	 * Sync fields for a form (replace all fields).
	 *
	 * @param int   $form_id Form ID.
	 * @param array $fields  Array of field data.
	 * @since 0.1.0
	 */
	private static function sync_fields( $form_id, $fields ) {
		WAFO_Model_Field::delete_by_form( $form_id );

		$order = 0;
		foreach ( $fields as $field ) {
			$options = null;
			if ( in_array( $field['field_type'], array( 'select', 'radio' ), true ) && ! empty( $field['options'] ) ) {
				$options = is_array( $field['options'] ) ? wp_json_encode( $field['options'] ) : $field['options'];
			}

			WAFO_Model_Field::insert( array(
				'form_id'     => $form_id,
				'label'       => WAFO_Sanitizer::text( $field['label'] ),
				'field_type'  => WAFO_Sanitizer::text( $field['field_type'] ),
				'is_required' => ! empty( $field['is_required'] ) ? 1 : 0,
				'order_index' => $order++,
				'options'     => $options,
			) );
		}
	}

	/**
	 * Sync WA targets for a form.
	 *
	 * @param int   $form_id Form ID.
	 * @param array $targets Array of target data.
	 * @since 0.1.0
	 */
	private static function sync_wa_targets( $form_id, $targets ) {
		WAFO_Model_Wa_Target::delete_by_form( $form_id );

		foreach ( $targets as $target ) {
			WAFO_Model_Wa_Target::insert( array(
				'form_id'      => $form_id,
				'phone_number' => WAFO_Sanitizer::phone( $target['phone_number'] ),
				'label'        => isset( $target['label'] ) ? WAFO_Sanitizer::text( $target['label'] ) : null,
			) );
		}
	}

	/**
	 * Get a form with all its fields and targets.
	 *
	 * @param int $id Form ID.
	 * @return object|null Form object with fields and targets.
	 * @since 0.1.0
	 */
	public static function get_with_details( $id ) {
		$form = WAFO_Model_Form::get( $id );
		if ( ! $form ) {
			return null;
		}

		$form->fields      = WAFO_Model_Field::get_by_form( $id );
		$form->wa_targets  = WAFO_Model_Wa_Target::get_by_form( $id );

		return $form;
	}
}
