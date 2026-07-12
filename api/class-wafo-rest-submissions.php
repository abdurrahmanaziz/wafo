<?php
/**
 * REST API: Submissions endpoints.
 *
 * @package WAFO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers REST API routes for submissions.
 *
 * @since 0.1.0
 */
class WAFO_REST_Submissions {

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
		// Public submit endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/submit/(?P<form_id>\d+)',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'submit_form' ),
				'permission_callback' => '__return_true',
				'args'                => $this->get_submit_args(),
			)
		);

		// Admin submission endpoints.
		register_rest_route(
			self::NAMESPACE,
			'/submissions',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_submissions' ),
				'permission_callback' => array( $this, 'admin_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/submissions/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_submission' ),
				'permission_callback' => array( $this, 'admin_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/submissions/(?P<id>\d+)/status',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_status' ),
				'permission_callback' => array( $this, 'admin_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/submissions/export',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'export_submissions' ),
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
	 * Submit a form (public endpoint).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function submit_form( $request ) {
		$form_id   = (int) $request->get_param( 'form_id' );
		$raw_data  = $request->get_json_params();
		$ip_address = $request->get_header( 'REMOTE_ADDR' );
		if ( empty( $ip_address ) ) {
			$ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0'; // phpcs:ignore WordPress.Security.NonceVerification
		}

		// Rate limit check.
		if ( WAFO_Rate_Limiter::is_rate_limited( $ip_address, $form_id ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'code'    => 'wafo_rate_limited',
					'message' => __( 'Terlalu banyak pengiriman. Silakan coba lagi nanti.', 'wa-form-optin' ),
				),
				429
			);
		}

		$result = WAFO_Submission_Handler::process( $form_id, $raw_data, $ip_address );

		if ( is_wp_error( $result ) ) {
			$status = isset( $result->get_error_data()['status'] ) ? $result->get_error_data()['status'] : 400;
			return new WP_REST_Response(
				array(
					'success' => false,
					'code'    => $result->get_error_code(),
					'message' => $result->get_error_message(),
				),
				$status
			);
		}

		// Generate WA link for response.
		$form    = WAFO_Model_Form::get( $form_id );
		$targets = WAFO_Model_Wa_Target::get_by_form( $form_id );
		$wa_link = '';

		if ( empty( $targets ) ) {
			return new WP_REST_Response(
				array(
					'success'       => true,
					'submission_id' => $result['submission_id'],
					'wa_link'       => '',
					'message'       => __( 'Submission berhasil, namun belum ada nomor WA tujuan yang dikonfigurasi.', 'wa-form-optin' ),
				),
				201
			);
		}

		$fields   = WAFO_Model_Field::get_by_form( $form_id );
		$values   = WAFO_Model_Submission_Value::get_by_submission( $result['submission_id'] );
		$labels   = array();
		$v_array  = array();

		if ( ! empty( $fields ) ) {
			// Build values map indexed by field_id.
			$values_map = array();
			foreach ( $values as $v ) {
				$values_map[ (string) $v->field_id ] = $v->value;
			}

			// Maintain field order using order_index.
			usort( $fields, function ( $a, $b ) {
				return (int) $a->order_index - (int) $b->order_index;
			});

			foreach ( $fields as $f ) {
				$labels[] = $f->label;
				$key      = (string) $f->id;
				$v_array[] = isset( $values_map[ $key ] ) ? $values_map[ $key ] : '';
			}
		}

		$template = ! empty( $form->wa_message_template ) ? $form->wa_message_template : '';
		$message  = WAFO_Template_Parser::parse( $template, $labels, $v_array );
		$wa_link  = WAFO_WA_Validator::generate_wa_link( $targets[0]->phone_number, $message );

		return new WP_REST_Response(
			array(
				'success'       => true,
				'submission_id' => $result['submission_id'],
				'wa_link'       => $wa_link,
			),
			201
		);
	}

	/**
	 * Get submissions list.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function get_submissions( $request ) {
		$args = array(
			'form_id'   => $request->get_param( 'form_id' ),
			'status'    => $request->get_param( 'status' ),
			'search'    => $request->get_param( 'search' ),
			'date_from' => $request->get_param( 'date_from' ),
			'date_to'   => $request->get_param( 'date_to' ),
			'orderby'   => $request->get_param( 'orderby' ),
			'order'     => $request->get_param( 'order' ),
			'page'      => $request->get_param( 'page' ) ?: 1,
			'per_page'  => $request->get_param( 'per_page' ) ?: 20,
		);

		$result = WAFO_Model_Submission::get_list( $args );

		$response = new WP_REST_Response( $result['results'], 200 );
		$response->header( 'X-WP-Total', $result['total'] );
		$response->header( 'X-WP-TotalPages', $result['total_pages'] );

		return $response;
	}

	/**
	 * Get a single submission.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function get_submission( $request ) {
		$submission = WAFO_Submission_Handler::get_with_details( $request->get_param( 'id' ) );
		if ( ! $submission ) {
			return new WP_REST_Response( array( 'code' => 'wafo_not_found', 'message' => 'Submission tidak ditemukan.' ), 404 );
		}
		return new WP_REST_Response( $submission, 200 );
	}

	/**
	 * Update submission status.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function update_status( $request ) {
		$params = $request->get_json_params();
		$status = isset( $params['status'] ) ? $params['status'] : '';
		$updated = WAFO_Submission_Handler::update_status( $request->get_param( 'id' ), $status );
		if ( ! $updated ) {
			return new WP_REST_Response( array( 'code' => 'wafo_update_failed', 'message' => 'Gagal update status.' ), 500 );
		}
		$submission = WAFO_Model_Submission::get( $request->get_param( 'id' ) );
		return new WP_REST_Response( $submission, 200 );
	}

	/**
	 * Export submissions as CSV.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function export_submissions( $request ) {
		$args = array(
			'form_id'   => $request->get_param( 'form_id' ),
			'status'    => $request->get_param( 'status' ),
			'date_from' => $request->get_param( 'date_from' ),
			'date_to'   => $request->get_param( 'date_to' ),
			'per_page'  => 10000,
			'page'      => 1,
		);

		$result  = WAFO_Model_Submission::get_list( $args );
		$csv_dir = wp_upload_dir();
		$csv_path = $csv_dir['path'] . '/wafo_export_' . gmdate( 'Y-m-d_H-i-s' ) . '.csv';

		$fp = fopen( $csv_path, 'w' );
		if ( ! $fp ) {
			return new WP_REST_Response( array( 'code' => 'wafo_export_failed', 'message' => 'Gagal membuat file export.' ), 500 );
		}

		// Header row.
		fputcsv( $fp, array( 'ID', 'Form ID', 'Status', 'WA Status', 'IP Address', 'Created At' ) );

		foreach ( $result['results'] as $row ) {
			fputcsv( $fp, array( $row->id, $row->form_id, $row->status, $row->wa_send_status, $row->ip_address, $row->created_at ) );
		}

		fclose( $fp );

		return new WP_REST_Response(
			array(
				'file_url' => $csv_dir['baseurl'] . '/' . basename( $csv_path ),
				'total'    => $result['total'],
			),
			200
		);
	}

	/**
	 * Get submit endpoint args.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	private function get_submit_args() {
		return array(
			'wafo_token'   => array(
				'required' => false,
				'type'     => 'string',
			),
			'wafo_honeypot' => array(
				'required' => false,
				'type'     => 'string',
			),
		);
	}
}
