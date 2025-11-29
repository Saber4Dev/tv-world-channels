<?php
/**
 * GitHub Updater Class
 *
 * Handles automatic updates from GitHub repository
 *
 * @package TV_World_Channels
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TV_Updater Class
 */
class TV_Updater {
	
	/**
	 * GitHub repository owner
	 *
	 * @var string
	 */
	private $owner = 'Saber4Dev';
	
	/**
	 * GitHub repository name
	 *
	 * @var string
	 */
	private $repo = 'tv-world-channels';
	
	/**
	 * GitHub API base URL
	 *
	 * @var string
	 */
	private $api_base = 'https://api.github.com/repos/';
	
	/**
	 * Transient key for update check
	 *
	 * @var string
	 */
	private $transient_key = 'tv_world_channels_update_check';
	
	/**
	 * Get instance of this class
	 *
	 * @return TV_Updater
	 */
	public static function get_instance() {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new self();
		}
		return $instance;
	}
	
	/**
	 * Constructor
	 */
	private function __construct() {
		$this->init();
	}
	
	/**
	 * Initialize hooks
	 */
	private function init() {
		// Only run in admin
		if ( ! is_admin() ) {
			return;
		}
		
		// Check for updates every 12 hours
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_updates' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
		add_filter( 'upgrader_post_install', array( $this, 'after_install' ), 10, 3 );
	}
	
	/**
	 * Check for updates
	 *
	 * @param object $transient Update transient
	 * @return object Modified transient
	 */
	public function check_for_updates( $transient ) {
		// If we've already checked recently, return
		if ( empty( $transient->checked ) ) {
			return $transient;
		}
		
		// Get current plugin version
		$plugin_data = get_plugin_data( TV_PLUGIN_FILE );
		$current_version = $plugin_data['Version'];
		
		// Get latest release from GitHub
		$latest_release = $this->get_latest_release();
		
		if ( false === $latest_release ) {
			return $transient;
		}
		
		$latest_version = $latest_release['tag_name'];
		
		// Remove 'v' prefix if present
		$latest_version = ltrim( $latest_version, 'v' );
		
		// Compare versions
		if ( version_compare( $current_version, $latest_version, '<' ) ) {
			$plugin_slug = TV_PLUGIN_BASENAME;
			
			// Get download URL from release assets or use zipball
			$package_url = $latest_release['zipball_url'];
			
			// Try to find a ZIP asset in the release
			if ( isset( $latest_release['assets'] ) && is_array( $latest_release['assets'] ) ) {
				foreach ( $latest_release['assets'] as $asset ) {
					if ( isset( $asset['browser_download_url'] ) && 'zip' === substr( $asset['browser_download_url'], -3 ) ) {
						$package_url = $asset['browser_download_url'];
						break;
					}
				}
			}
			
			$transient->response[ $plugin_slug ] = (object) array(
				'slug' => 'tv-world-channels',
				'plugin' => $plugin_slug,
				'new_version' => $latest_version,
				'url' => $plugin_data['PluginURI'],
				'package' => $package_url,
			);
		}
		
		return $transient;
	}
	
	/**
	 * Get plugin information for update screen
	 *
	 * @param false|object|array $result The result object or array
	 * @param string $action The type of information being requested
	 * @param object $args Plugin API arguments
	 * @return false|object|array
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}
		
		if ( 'tv-world-channels' !== $args->slug ) {
			return $result;
		}
		
		$plugin_data = get_plugin_data( TV_PLUGIN_FILE );
		$latest_release = $this->get_latest_release();
		
		if ( false === $latest_release ) {
			return $result;
		}
		
		$info = new stdClass();
		$info->name = $plugin_data['Name'];
		$info->slug = 'tv-world-channels';
		$info->version = ltrim( $latest_release['tag_name'], 'v' );
		$info->author = $plugin_data['Author'];
		$info->author_profile = 'https://github.com/' . $this->owner;
		$info->homepage = $plugin_data['PluginURI'];
		$info->requires = '6.0';
		$info->tested = '6.4';
		$info->last_updated = $latest_release['published_at'];
		
		// Get download URL from release assets or use zipball
		$package_url = $latest_release['zipball_url'];
		
		// Try to find a ZIP asset in the release
		if ( isset( $latest_release['assets'] ) && is_array( $latest_release['assets'] ) ) {
			foreach ( $latest_release['assets'] as $asset ) {
				if ( isset( $asset['browser_download_url'] ) && 'zip' === substr( $asset['browser_download_url'], -3 ) ) {
					$package_url = $asset['browser_download_url'];
					break;
				}
			}
		}
		
		$info->download_link = $package_url;
		$info->sections = array(
			'description' => $plugin_data['Description'],
			'changelog' => $this->format_changelog( $latest_release ),
		);
		
		return $info;
	}
	
	/**
	 * After plugin installation
	 *
	 * @param bool $response Installation response
	 * @param array $hook_extra Extra arguments
	 * @param array $result Installation result
	 * @return bool
	 */
	public function after_install( $response, $hook_extra, $result ) {
		if ( empty( $hook_extra['plugin'] ) || TV_PLUGIN_BASENAME !== $hook_extra['plugin'] ) {
			return $response;
		}
		
		// Move files to correct location
		global $wp_filesystem;
		
		$install_directory = plugin_dir_path( TV_PLUGIN_FILE );
		$wp_filesystem->move( $result['destination'], $install_directory );
		$result['destination'] = $install_directory;
		
		return $response;
	}
	
	/**
	 * Get latest release from GitHub
	 *
	 * @return array|false Release data or false on error
	 */
	private function get_latest_release() {
		// Check transient cache first
		$cached = get_transient( $this->transient_key );
		if ( false !== $cached ) {
			return $cached;
		}
		
		$url = $this->api_base . $this->owner . '/' . $this->repo . '/releases/latest';
		
		$response = wp_remote_get( $url, array(
			'timeout' => 10,
			'headers' => array(
				'Accept' => 'application/vnd.github.v3+json',
				'User-Agent' => 'WordPress-TV-World-Channels-Plugin',
			),
		) );
		
		if ( is_wp_error( $response ) ) {
			return false;
		}
		
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		
		if ( ! is_array( $data ) || ! isset( $data['tag_name'] ) ) {
			return false;
		}
		
		// Cache for 12 hours
		set_transient( $this->transient_key, $data, 12 * HOUR_IN_SECONDS );
		
		return $data;
	}
	
	/**
	 * Format changelog from release
	 *
	 * @param array $release Release data
	 * @return string Formatted changelog
	 */
	private function format_changelog( $release ) {
		$changelog = '<h3>' . esc_html__( 'Version', 'tv-world-channels' ) . ' ' . esc_html( ltrim( $release['tag_name'], 'v' ) ) . '</h3>';
		
		if ( ! empty( $release['body'] ) ) {
			$changelog .= '<div class="tv-changelog">';
			$changelog .= wp_kses_post( nl2br( $release['body'] ) );
			$changelog .= '</div>';
		} else {
			$changelog .= '<p>' . esc_html__( 'No changelog available for this release.', 'tv-world-channels' ) . '</p>';
		}
		
		return $changelog;
	}
}

