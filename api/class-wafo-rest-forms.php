<?php
/**
 * REST API: Forms endpoints.
 *
 * @package WAFO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers REST API routes for form management.
 *
 * @since 0.1.0
 */
class WAFO_REST_Forms {

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'wafo/v1';

	/**
	 * Register routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/forms',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_forms' ),
					'permission_callback' => array( $this, 'admin_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_form' ),
					'permission_callback' => array( $this, 'admin_permission' ),
					'args'                => $this->get_create_args(),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/forms/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_form' ),
					'permission_callback' => array( $this, 'admin_permission' ),
				),
				array(
					'methods'             => 'PUT, PATCH',
					'callback'            => array( $this, 'update_form' ),
					'permission_callback' => array( $this, 'admin_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_form' ),
					'permission_callback' => array( $this, 'admin_permission' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/forms/(?P<id>\d+)/fields',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_fields' ),
					'permission_callback' => array( $this, 'admin_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_field' ),
					'permission_callback' => array( $this, 'admin_permission' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/fields/(?P<field_id>\d+)',
			array(
				array(
					'methods'             => 'PUT, PATCH',
					'callback'            => array( $this, 'update_field' ),
					'permission_callback' => array( $this, 'admin_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_field' ),
					'permission_callback' => array( $this, 'admin_permission' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/forms/(?P<id>\d+)/wa-targets',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_wa_targets' ),
					'permission_callback' => array( $this, 'admin_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_wa_target' ),
					'permission_callback' => array( $this, 'admin_permission' ),
				),
			)
		);
	}

	/**
	 * Admin permission check.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function admin_permission() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'wafo_forbidden', __( 'Akses ditolak.', 'wa-form-optin' ), array( 'status' => 403 ) );
		}
		return true;
	}

	/**
	 * Get all forms.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function get_forms( $request ) {
		$forms = WAFO_Model_Form::get_all();
		return new WP_REST_Response( $forms, 200 );
	}

	/**
	 * Get a single form with details.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function get_form( $request ) {
		$form = WAFO_Form_Handler::get_with_details( $request->get_param( 'id' ) );
		if ( ! $form ) {
			return new WP_REST_Response( array( 'code' => 'wafo_not_found', 'message' => 'Form tidak ditemukan.' ), 404 );
		}
		return new WP_REST_Response( $form, 200 );
	}

	/**
	 * Create a form.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function create_form( $request ) {
		$form_id = WAFO_Form_Handler::create( $request->get_json_params() );
		if ( ! $form_id ) {
			return new WP_REST_Response( array( 'code' => 'wafo_create_failed', 'message' => 'Gagal membuat form.' ), 500 );
		}
		$form = WAFO_Form_Handler::get_with_details( $form_id );
		return new WP_REST_Response( $form, 201 );
	}

	/**
	 * Update a form.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function update_form( $request ) {
		$updated = WAFO_Form_Handler::update( $request->get_param( 'id' ), $request->get_json_params() );
		if ( ! $updated ) {
			return new WP_REST_Response( array( 'code' => 'wafo_update_failed', 'message' => 'Gagal update form.' ), 500 );
		}
		$form = WAFO_Form_Handler::get_with_details( $request->get_param( 'id' ) );
		return new WP_REST_Response( $form, 200 );
	}

	/**
	 * Delete a form.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function delete_form( $request ) {
		$deleted = WAFO_Form_Handler::delete( $request->get_param( 'id' ) );
		if ( ! $deleted ) {
			return new WP_REST_Response( array( 'code' => 'wafo_delete_failed', 'message' => 'Gagal hapus form.' ), 500 );
		}
		return new WP_REST_Response( null, 204 );
	}

	/**
	 * Get fields for a form.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function get_fields( $request ) {
		$fields = WAFO_Model_Field::get_by_form( $request->get_param( 'id' ) );
		return new WP_REST_Response( $fields, 200 );
	}

	/**
	 * Create a field.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function create_field( $request ) {
		$params = $request->get_json_params();
		$params['form_id'] = $request->get_param( 'id' );

		$field_id = WAFO_Model_Field::insert( array(
			'form_id'     => $params['form_id'],
			'label'       => WAFO_Sanitizer::text( $params['label'] ),
			'field_type'  => WAFO_Sanitizer::text( $params['field_type'] ),
			'is_required' => ! empty( $params['is_required'] ) ? 1 : 0,
			'order_index' => isset( $params['order_index'] ) ? (int) $params['order_index'] : 0,
			'options'     => isset( $params['options'] ) ? $params['options'] : null,
		) );

		if ( ! $field_id ) {
			return new WP_REST_Response( array( 'code' => 'wafo_field_create_failed', 'message' => 'Gagal membuat field.' ), 500 );
		}

		$field = WAFO_Model_Field::get( $field_id );
		return new WP_REST_Response( $field, 201 );
	}

	/**
	 * Update a field.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function update_field( $request ) {
		$params = $request->get_json_params();
		$updated = WAFO_Model_Field::update( $request->get_param( 'field_id' ), $params );
		if ( ! $updated ) {
			return new WP_REST_Response( array( 'code' => 'wafo_field_update_failed', 'message' => 'Gagal update field.' ), 500 );
		}
		$field = WAFO_Model_Field::get( $request->get_param( 'field_id' ) );
		return new WP_REST_Response( $field, 200 );
	}

	/**
	 * Delete a field.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function delete_field( $request ) {
		$deleted = WAFO_Model_Field::delete( $request->get_param( 'field_id' ) );
		if ( ! $deleted ) {
			return new WP_REST_Response( array( 'code' => 'wafo_field_delete_failed', 'message' => 'Gagal hapus field.' ), 500 );
		}
		return new WP_REST_Response( null, 204 );
	}

	/**
	 * Get WA targets for a form.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function get_wa_targets( $request ) {
		$targets = WAFO_Model_Wa_Target::get_by_form( $request->get_param( 'id' ) );
		return new WP_REST_Response( $targets, 200 );
	}

	/**
	 * Create a WA target.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function create_wa_target( $request ) {
		$params = $request->get_json_params();
		$target_id = WAFO_Model_Wa_Target::insert( array(
			'form_id'      => $request->get_param( 'id' ),
			'phone_number' => WAFO_Sanitizer::phone( $params['phone_number'] ),
			'label'        => isset( $params['label'] ) ? WAFO_Sanitizer::text( $params['label'] ) : null,
		) );

		if ( ! $target_id ) {
			return new WP_REST_Response( array( 'code' => 'wafo_target_create_failed', 'message' => 'Gagal membuat target WA.' ), 500 );
		}

		$targets = WAFO_Model_Wa_Target::get_by_form( $request->get_param( 'id' ) );
		return new WP_REST_Response( $targets, 201 );
	}

	/**
	 * Get create form args.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	private function get_create_args() {
		return array(
			'name'                => array(
				'required' => true,
				'type'     => 'string',
			),
			'wa_message_template' => array(
				'required' => false,
				'type'     => 'string',
			),
			'fields'              => array(
				'required' => false,
				'type'     => 'array',
			),
			'wa_targets'          => array(
				'required' => false,
				'type'     => 'array',
			),
		);
	}
}
