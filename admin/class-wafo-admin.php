<?php
/**
 * Admin class.
 *
 * @package WAFO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles WordPress admin area for the plugin.
 *
 * @since 0.1.0
 */
class WAFO_Admin {

	/**
	 * Register admin menus.
	 *
	 * @since 0.1.0
	 */
	public function register_menu() {
		add_menu_page(
			__( 'WA Form Optin', 'wa-form-optin' ),
			__( 'WA Form Optin', 'wa-form-optin' ),
			'manage_options',
			'wafo-dashboard',
			array( $this, 'render_dashboard_page' ),
			'dashicons-whatsapp',
			30
		);

		add_submenu_page(
			'wafo-dashboard',
			__( 'Form Builder', 'wa-form-optin' ),
			__( 'Form Builder', 'wa-form-optin' ),
			'manage_options',
			'wafo-forms',
			array( $this, 'render_forms_page' )
		);

		add_submenu_page(
			'wafo-dashboard',
			__( 'Submissions', 'wa-form-optin' ),
			__( 'Submissions', 'wa-form-optin' ),
			'manage_options',
			'wafo-submissions',
			array( $this, 'render_submissions_page' )
		);

		add_submenu_page(
			'wafo-dashboard',
			__( 'Settings', 'wa-form-optin' ),
			__( 'Settings', 'wa-form-optin' ),
			'manage_options',
			'wafo-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page.
	 * @since 0.1.0
	 */
	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'wafo' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'wafo-admin-css',
			WAFO_PLUGIN_URL . 'admin/assets/css/wafo-admin.css',
			array(),
			WAFO_VERSION
		);

		wp_enqueue_style( 'wafo-poppins', 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap', array(), WAFO_VERSION );

		wp_enqueue_script( 'jquery-ui-sortable' );

		wp_enqueue_script(
			'wafo-admin-js',
			WAFO_PLUGIN_URL . 'admin/assets/js/wafo-admin.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			WAFO_VERSION,
			true
		);

		wp_localize_script(
			'wafo-admin-js',
			'wafoAdmin',
			array(
				'restUrl'    => rest_url( 'wafo/v1/' ),
				'restNonce'  => wp_create_nonce( 'wp_rest' ),
				'adminUrl'   => admin_url(),
				'pluginUrl'  => WAFO_PLUGIN_URL,
			)
		);
	}

	/**
	 * Render dashboard page.
	 *
	 * @since 0.1.0
	 */
	public function render_dashboard_page() {
		require_once WAFO_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	/**
	 * Render forms page.
	 *
	 * @since 0.1.0
	 */
	public function render_forms_page() {
		require_once WAFO_PLUGIN_DIR . 'admin/views/forms.php';
	}

	/**
	 * Render submissions page.
	 *
	 * @since 0.1.0
	 */
	public function render_submissions_page() {
		require_once WAFO_PLUGIN_DIR . 'admin/views/submissions.php';
	}

	/**
	 * Render settings page.
	 *
	 * @since 0.1.0
	 */
	public function render_settings_page() {
		require_once WAFO_PLUGIN_DIR . 'admin/views/settings.php';
	}
}
