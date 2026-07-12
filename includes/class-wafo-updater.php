<?php
/**
 * GitHub Auto-Updater for WA Form Optin.
 *
 * Checks GitHub releases for new versions and triggers
 * WordPress plugin update notifications.
 *
 * @package WAFO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lightweight GitHub-based plugin updater.
 *
 * Uses GitHub Releases API to check for new versions
 * and integrates with WordPress native update system.
 *
 * @since 0.1.0
 */
class WAFO_Updater {

	/**
	 * GitHub repository URL (owner/repo format).
	 *
	 * @var string
	 */
	private $repo_url;

	/**
	 * Current plugin file path.
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Cache key for transient.
	 *
	 * @var string
	 */
	const CACHE_KEY = 'wafo_update_info';

	/**
	 * Cache duration (12 hours).
	 *
	 * @var int
	 */
	const CACHE_DURATION = 43200;

	/**
	 * Constructor.
	 *
	 * @param string $repo_url    GitHub repo in owner/repo format.
	 * @param string $plugin_file Main plugin file path.
	 * @since 0.1.0
	 */
	public function __construct( $repo_url, $plugin_file ) {
		$this->repo_url    = $repo_url;
		$this->plugin_file = $plugin_file;
	}

	/**
	 * Initialize the updater.
	 *
	 * @since 0.1.0
	 */
	public function init() {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugins_api_handler' ), 10, 3 );
		add_filter( 'plugin_row_meta', array( $this, 'add_update_row_meta' ), 10, 2 );

		// Clear cached update check on plugins page so WP re-checks.
		if ( isset( $_GET['page'] ) && 'plugins.php' === $_GET['page'] ) {
			delete_site_transient( 'update_plugins' );
		}

		// Schedule check every 12 hours.
		if ( ! wp_next_scheduled( 'wafo_check_updates' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'wafo_check_updates' );
		}
		add_action( 'wafo_check_updates', array( $this, 'manual_update_check' ) );
	}

	/**
	 * Check for update and inject into WordPress transient.
	 *
	 * @param object $transient_data Existing transient data.
	 * @return object Modified transient data.
	 * @since 0.1.0
	 */
	public function check_for_update( $transient_data ) {
		if ( empty( $transient_data->response ) ) {
			$transient_data->response = array();
		}

		$release = $this->get_remote_release();

		if ( ! $release || ! isset( $release['version'] ) ) {
			return $transient_data;
		}

		$remote_version = ltrim( $release['version'], 'v' );
		$local_version  = WAFO_VERSION;

		if ( version_compare( $remote_version, $local_version, '>' ) ) {
			$slug                  = plugin_basename( $this->plugin_file );
			$update                = new stdClass();
			$update->slug          = dirname( $slug );
			$update->plugin        = $slug;
			$update->new_version   = $remote_version;
			$update->url           = $release['html_url'];
			$update->package       = $release['zip_url'];
			$update->tested        = '6.5';
			$update->requires      = '5.8';
			$update->requires_php  = '7.4';
			$update->last_updated  = $release['published_at'];

			$transient_data->response[ $slug ] = $update;
		}

		return $transient_data;
	}

	/**
	 * Handle plugins_api for detail view.
	 *
	 * @param object|false $response API response.
	 * @param string       $action   API action.
	 * @param object       $args     API args.
	 * @return object|false
	 * @since 0.1.0
	 */
	public function plugins_api_handler( $response, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $response;
		}

		if ( ! isset( $args->slug ) || $args->slug !== dirname( plugin_basename( $this->plugin_file ) ) ) {
			return $response;
		}

		$release = $this->get_remote_release();
		if ( ! $release ) {
			return $response;
		}

		$info                = new stdClass();
		$info->name          = 'WA Form Optin';
		$info->slug          = dirname( plugin_basename( $this->plugin_file ) );
		$info->version       = ltrim( $release['version'], 'v' );
		$info->author        = 'WAFO Team';
		$info->author_homepage = 'https://github.com/' . $this->repo_url;
		$info->homepage      = 'https://github.com/' . $this->repo_url;
		$info->requires      = '5.8';
		$info->requires_php  = '7.4';
		$info->tested        = '6.5';
		$info->rating        = 100;
		$info->num_ratings   = 1;
		$info->downloaded    = 0;
		$info->last_updated  = isset( $release['published_at'] ) ? $release['published_at'] : gmdate( 'Y-m-d' );
		$info->sections      = array(
			'description'  => $this->get_release_notes( $release ),
			'changelog'    => $this->get_release_notes( $release ),
		);
		$info->download_link = $release['zip_url'];

		return $info;
	}

	/**
	 * Get cached or fresh release data from GitHub.
	 *
	 * @return array|false Release data or false on failure.
	 * @since 0.1.0
	 */
	private function get_remote_release() {
		$cached = get_transient( self::CACHE_KEY );
		if ( false !== $cached ) {
			return $cached;
		}

		$api_url = 'https://api.github.com/repos/' . $this->repo_url . '/releases/latest';

		$response = wp_remote_get( $api_url, array(
			'timeout' => 15,
			'headers' => array(
				'Accept'     => 'application/vnd.github.v3+json',
				'User-Agent' => 'WAFO-Plugin-Updater/' . WAFO_VERSION,
			),
		) );

		if ( is_wp_error( $response ) ) {
			error_log( '[WAFO Updater] GitHub API error: ' . $response->get_error_message() );
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			error_log( '[WAFO Updater] GitHub API returned: ' . $code );
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! $body || ! isset( $body['tag_name'] ) ) {
			return false;
		}

		// Find the zip asset (look for wafo*.zip or any .zip).
		$zip_url = '';
		if ( ! empty( $body['assets'] ) ) {
			// First pass: look for plugin zip specifically.
			foreach ( $body['assets'] as $asset ) {
				if ( isset( $asset['name'] ) && preg_match( '/wafo.*\.zip$/i', $asset['name'] ) ) {
					$zip_url = $asset['browser_download_url'];
					break;
				}
			}
			// Second pass: any .zip asset.
			if ( empty( $zip_url ) ) {
				foreach ( $body['assets'] as $asset ) {
					if ( isset( $asset['name'] ) && preg_match( '/\.zip$/i', $asset['name'] ) ) {
						$zip_url = $asset['browser_download_url'];
						break;
					}
				}
			}
		}

		// Fallback: construct zip URL from release tag.
		if ( empty( $zip_url ) ) {
			$tag = $body['tag_name'];
			// Try the zipball URL as last resort.
			$zip_url = 'https://github.com/' . $this->repo_url . '/archive/refs/tags/' . $tag . '.zip';
		}

		$release = array(
			'version'      => $body['tag_name'],
			'html_url'     => $body['html_url'],
			'zip_url'      => $zip_url,
			'body'         => isset( $body['body'] ) ? $body['body'] : '',
			'published_at' => isset( $body['published_at'] ) ? substr( $body['published_at'], 0, 10 ) : '',
		);

		set_transient( self::CACHE_KEY, $release, self::CACHE_DURATION );

		return $release;
	}

	/**
	 * Get release notes from release data.
	 *
	 * @param array $release Release data.
	 * @return string Release notes HTML.
	 * @since 0.1.0
	 */
	private function get_release_notes( $release ) {
		$body = isset( $release['body'] ) ? $release['body'] : '';
		if ( empty( $body ) ) {
			return '<p>Versi baru tersedia: ' . esc_html( ltrim( $release['version'], 'v' ) ) . '</p>';
		}
		return wp_kses_post( nl2br( $body ) );
	}

	/**
	 * Manual update check via admin action.
	 *
	 * @since 0.1.0
	 */
	public function manual_update_check() {
		delete_transient( self::CACHE_KEY );
		$this->get_remote_release();
	}

	/**
	 * Add update info to plugin row meta.
	 *
	 * @param array  $links    Plugin row meta links.
	 * @param string $file     Plugin file path.
	 * @return array Modified links.
	 * @since 0.1.0
	 */
	public function add_update_row_meta( $links, $file ) {
		if ( $file !== plugin_basename( $this->plugin_file ) ) {
			return $links;
		}

		$release = $this->get_remote_release();
		if ( ! $release || ! isset( $release['version'] ) ) {
			return $links;
		}

		$remote_version = ltrim( $release['version'], 'v' );
		if ( version_compare( $remote_version, WAFO_VERSION, '>' ) ) {
			$links['update_available'] = sprintf(
				'<span style="color:#25D366;font-weight:600;">Versi baru: %s</span>',
				esc_html( $remote_version )
			);
		}

		return $links;
	}
}
