<?php
/**
 * REST API: Settings endpoints.
 *
 * @package WAFO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers REST API routes for plugin settings.
 *
 * @since 0.1.0
 */
class WAFO_REST_Settings {

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
			'/settings/wa-template',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_wa_template' ),
				'permission_callback' => array( $this, 'admin_permission' ),
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
	 * Update WA template for a form.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function update_wa_template( $request ) {
		$params   = $request->get_json_params();
		$form_id  = isset( $params['form_id'] ) ? (int) $params['form_id'] : 0;
		$template = isset( $params['template'] ) ? WAFO_Sanitizer::textarea( $params['template'] ) : '';

		if ( ! $form_id ) {
			return new WP_REST_Response(
				array( 'code' => 'wafo_missing_form_id', 'message' => 'Form ID diperlukan.' ),
				400
			);
		}

		$form = WAFO_Model_Form::get( $form_id );
		if ( ! $form ) {
			return new WP_REST_Response(
				array( 'code' => 'wafo_form_not_found', 'message' => 'Form tidak ditemukan.' ),
				404
			);
		}

		$updated = WAFO_Model_Form::update( $form_id, array( 'wa_message_template' => $template ) );
		if ( ! $updated ) {
			return new WP_REST_Response(
				array( 'code' => 'wafo_update_failed', 'message' => 'Gagal update template.' ),
				500
			);
		}

		$form = WAFO_Model_Form::get( $form_id );
		return new WP_REST_Response( $form, 200 );
	}
}
