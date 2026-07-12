<?php
/**
 * Public class for shortcode rendering.
 *
 * @package WAFO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles public-facing form rendering and submission.
 *
 * @since 0.1.0
 */
class WAFO_Public {

	/**
	 * Register shortcodes.
	 *
	 * @since 0.1.0
	 */
	public function register_shortcode() {
		add_shortcode( 'wafo_form', array( $this, 'render_form_shortcode' ) );
	}

	/**
	 * Enqueue public assets.
	 *
	 * @since 0.1.0
	 */
	public function enqueue_assets() {
		if ( ! is_singular() ) {
			return;
		}

		wp_enqueue_style(
			'wafo-public-css',
			WAFO_PLUGIN_URL . 'public/assets/css/wafo-public.css',
			array(),
			WAFO_VERSION
		);

		wp_enqueue_style( 'wafo-poppins-public', 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap', array(), WAFO_VERSION );

		wp_enqueue_script(
			'wafo-public-js',
			WAFO_PLUGIN_URL . 'public/assets/js/wafo-public.js',
			array( 'jquery' ),
			WAFO_VERSION,
			true
		);

		wp_localize_script(
			'wafo-public-js',
			'wafoPublic',
			array(
				'restUrl' => esc_url_raw( rest_url( 'wafo/v1/' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

	/**
	 * Render form via shortcode [wafo_form id="1"].
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML form.
	 * @since 0.1.0
	 */
	public function render_form_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts,
			'wafo_form'
		);

		$form_id = absint( $atts['id'] );
		if ( ! $form_id ) {
			return '<p class="wafo-error">' . esc_html__( 'Form ID tidak valid.', 'wa-form-optin' ) . '</p>';
		}

		$form = WAFO_Model_Form::get( $form_id );
		if ( ! $form || 'active' !== $form->status ) {
			return '<p class="wafo-error">' . esc_html__( 'Form tidak ditemukan atau tidak aktif.', 'wa-form-optin' ) . '</p>';
		}

		$fields = WAFO_Model_Field::get_by_form( $form_id );
		if ( empty( $fields ) ) {
			return '<p class="wafo-error">' . esc_html__( 'Form tidak memiliki field.', 'wa-form-optin' ) . '</p>';
		}

		// Ensure assets are loaded (fallback if enqueue_assets missed the shortcode).
		if ( ! wp_style_is( 'wafo-public-css', 'enqueued' ) ) {
			$this->enqueue_assets();
		}

		ob_start();
		echo '<div class="wafo-form-wrap" data-form-id="' . esc_attr( $form_id ) . '">';
		echo '<form class="wafo-form" id="wafo-form-' . esc_attr( $form_id ) . '" data-form-id="' . esc_attr( $form_id ) . '">';

		wp_nonce_field( 'wafo_submit_' . $form_id, 'wafo_nonce', false );
		echo '<input type="hidden" name="wafo_token" value="' . esc_attr( wp_create_nonce( 'wafo_submit_' . $form_id ) ) . '">';
		echo '<input type="text" name="wafo_honeypot" class="wafo-honeypot" tabindex="-1" autocomplete="off">';

		foreach ( $fields as $field ) {
			$slug      = strtolower( sanitize_title( $field->label ) );
			$required  = $field->is_required ? 'required' : '';
			$req_label = $field->is_required ? '<span class="wafo-required">*</span>' : '';

			echo '<div class="wafo-field">';
			echo '<label for="wafo-field-' . esc_attr( $slug ) . '">' . esc_html( $field->label ) . $req_label . '</label>';

			switch ( $field->field_type ) {
				case 'textarea':
					echo '<textarea id="wafo-field-' . esc_attr( $slug ) . '" name="' . esc_attr( $slug ) . '" rows="4" ' . $required . '></textarea>';
					break;
				case 'select':
					echo '<select id="wafo-field-' . esc_attr( $slug ) . '" name="' . esc_attr( $slug ) . '" ' . $required . '>';
					echo '<option value="">' . esc_html__( 'Pilih...', 'wa-form-optin' ) . '</option>';
					if ( ! empty( $field->options ) ) {
						$options = json_decode( $field->options, true );
						if ( is_array( $options ) ) {
							foreach ( $options as $opt ) {
								echo '<option value="' . esc_attr( $opt ) . '">' . esc_html( $opt ) . '</option>';
							}
						}
					}
					echo '</select>';
					break;
				case 'radio':
					if ( ! empty( $field->options ) ) {
						$options = json_decode( $field->options, true );
						if ( is_array( $options ) ) {
							foreach ( $options as $opt ) {
								echo '<label class="wafo-radio-label">';
								echo '<input type="radio" name="' . esc_attr( $slug ) . '" value="' . esc_attr( $opt ) . '" ' . $required . '>';
								echo esc_html( $opt );
								echo '</label>';
							}
						}
					}
					break;
				default:
					$type = 'text';
					if ( 'phone' === $field->field_type ) {
						$type = 'tel';
					} elseif ( 'email' === $field->field_type ) {
						$type = 'email';
					}
					echo '<input type="' . esc_attr( $type ) . '" id="wafo-field-' . esc_attr( $slug ) . '" name="' . esc_attr( $slug ) . '" ' . $required . '>';
					break;
			}

			echo '</div>';
		}

		echo '<div class="wafo-submit-wrap">';
		echo '<button type="submit" class="wafo-submit-btn">' .
			'<span class="wafo-submit-spinner"></span>' .
			'<span class="wafo-btn-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg></span>' .
			'<span class="wafo-btn-text">' . esc_html__( 'Kirim via WhatsApp', 'wa-form-optin' ) . '</span>' .
			'</button>';
		echo '</div>';

		echo '<div class="wafo-message" style="display:none;"></div>';
		echo '</form>';
		echo '</div>';

		return ob_get_clean();
	}
}
