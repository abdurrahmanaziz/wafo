<?php
/**
 * Plugin Name:       WA Form Optin
 * Plugin URI:        https://github.com/abdurrahmanaziz/wafo
 * Description:       Form kontak dengan custom field & integrasi WhatsApp. Setiap submission otomatis dikirim ke nomor WhatsApp admin.
 * Version:           0.1.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            WAFO Team
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wa-form-optin
 * Domain Path:       /languages
 * Update URI:        https://github.com/abdurrahmanaziz/wafo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin constants.
 */
define( 'WAFO_VERSION', '0.1.3' );
define( 'WAFO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WAFO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WAFO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'WAFO_DB_VERSION', '1.0.0' );
define( 'WAFO_OPTION_PREFIX', 'wafo_' );
define( 'WAFO_GITHUB_REPO', 'abdurrahmanaziz/wafo' );

/**
 * Autoload core classes.
 */
require_once WAFO_PLUGIN_DIR . 'includes/class-wafo-loader.php';
require_once WAFO_PLUGIN_DIR . 'database/class-wafo-db-installer.php';
require_once WAFO_PLUGIN_DIR . 'includes/helpers/class-wafo-sanitizer.php';
require_once WAFO_PLUGIN_DIR . 'includes/helpers/class-wafo-wa-validator.php';
require_once WAFO_PLUGIN_DIR . 'includes/helpers/class-wafo-template-parser.php';
require_once WAFO_PLUGIN_DIR . 'includes/helpers/class-wafo-rate-limiter.php';
require_once WAFO_PLUGIN_DIR . 'includes/models/class-wafo-model-form.php';
require_once WAFO_PLUGIN_DIR . 'includes/models/class-wafo-model-field.php';
require_once WAFO_PLUGIN_DIR . 'includes/models/class-wafo-model-submission.php';
require_once WAFO_PLUGIN_DIR . 'includes/models/class-wafo-model-submission-value.php';
require_once WAFO_PLUGIN_DIR . 'includes/models/class-wafo-model-wa-target.php';
require_once WAFO_PLUGIN_DIR . 'includes/models/class-wafo-model-submission-log.php';
require_once WAFO_PLUGIN_DIR . 'includes/controllers/class-wafo-form-handler.php';
require_once WAFO_PLUGIN_DIR . 'includes/controllers/class-wafo-submission-handler.php';
require_once WAFO_PLUGIN_DIR . 'includes/controllers/class-wafo-wa-sender.php';
require_once WAFO_PLUGIN_DIR . 'admin/class-wafo-admin.php';
require_once WAFO_PLUGIN_DIR . 'public/class-wafo-public.php';
require_once WAFO_PLUGIN_DIR . 'api/class-wafo-rest-forms.php';
require_once WAFO_PLUGIN_DIR . 'api/class-wafo-rest-submissions.php';
require_once WAFO_PLUGIN_DIR . 'api/class-wafo-rest-settings.php';
require_once WAFO_PLUGIN_DIR . 'includes/cron/class-wafo-cron.php';
require_once WAFO_PLUGIN_DIR . 'includes/class-wafo-updater.php';

/**
 * Main plugin class.
 *
 * @since 0.1.0
 */
final class WAFO_Plugin {

	/**
	 * Plugin loader instance.
	 *
	 * @var WAFO_Loader
	 */
	private $loader;

	/**
	 * Single instance of the plugin.
	 *
	 * @var WAFO_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get single instance.
	 *
	 * @return WAFO_Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->loader = new WAFO_Loader();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_api_hooks();
		$this->define_wa_hooks();
		$this->define_updater();
	}

	/**
	 * Register admin hooks.
	 *
	 * @since 0.1.0
	 */
	private function define_admin_hooks() {
		$admin = new WAFO_Admin();
		$this->loader->add_action( 'admin_menu', $admin, 'register_menu' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_assets' );
	}

	/**
	 * Register public hooks.
	 *
	 * @since 0.1.0
	 */
	private function define_public_hooks() {
		$public = new WAFO_Public();
		$this->loader->add_action( 'init', $public, 'register_shortcode' );
		$this->loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_assets' );
	}

	/**
	 * Register REST API hooks.
	 *
	 * @since 0.1.0
	 */
	private function define_api_hooks() {
		$rest_forms        = new WAFO_REST_Forms();
		$rest_submissions  = new WAFO_REST_Submissions();
		$rest_settings     = new WAFO_REST_Settings();
		$this->loader->add_action( 'rest_api_init', $rest_forms, 'register_routes' );
		$this->loader->add_action( 'rest_api_init', $rest_submissions, 'register_routes' );
		$this->loader->add_action( 'rest_api_init', $rest_settings, 'register_routes' );
	}

	/**
	 * Register WhatsApp notification hooks.
	 *
	 * @since 0.1.0
	 */
	private function define_wa_hooks() {
		$this->loader->add_action( 'wafo_after_submission_created', 'WAFO_WA_Sender', 'send', 10, 4 );
	}

	/**
	 * Register GitHub auto-updater.
	 *
	 * @since 0.1.0
	 */
	private function define_updater() {
		$updater = new WAFO_Updater( WAFO_GITHUB_REPO, __FILE__ );
		$this->loader->add_action( 'admin_init', $updater, 'init' );
	}

	/**
	 * Run the loader.
	 *
	 * @since 0.1.0
	 */
	public function run() {
		$this->loader->run();
	}
}

/**
 * Plugin activation hook.
 *
 * @since 0.1.0
 */
function wafo_activate() {
	$installer = new WAFO_DB_Installer();
	$installer->create_tables();
	update_option( 'wafo_db_version', WAFO_DB_VERSION );
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'wafo_activate' );

/**
 * Plugin deactivation hook.
 *
 * @since 0.1.0
 */
function wafo_deactivate() {
	flush_rewrite_rules();
	wp_clear_scheduled_hook( 'wafo_cleanup_rate_limits' );
}
register_deactivation_hook( __FILE__, 'wafo_deactivate' );

/**
 * Initialize plugin.
 *
 * @since 0.1.0
 */
function wafo_init() {
	$plugin = WAFO_Plugin::get_instance();
	$plugin->run();
}
add_action( 'plugins_loaded', 'wafo_init' );
