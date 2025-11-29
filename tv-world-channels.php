<?php
/**
 * Plugin Name: TV World Channels
 * Plugin URI: https://github.com/Saber4Dev/tv-world-channels
 * Description: Display TV channels from around the world using the iptv-org API. Features sliders, tables, and country filtering.
 * Version: 1.0.1
 * Author: Ranber
 * Author URI: https://ranber.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: tv-world-channels
 * Domain Path: /languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'TV_PLUGIN_VERSION', '1.0.1' );
define( 'TV_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TV_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TV_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'TV_PLUGIN_FILE', __FILE__ );

/**
 * Main plugin class
 */
class TV_World_Channels {
	
	/**
	 * Instance of this class
	 *
	 * @var TV_World_Channels
	 */
	private static $instance = null;
	
	/**
	 * Get instance of this class
	 *
	 * @return TV_World_Channels
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Constructor
	 */
	private function __construct() {
		$this->init();
	}
	
	/**
	 * Initialize plugin
	 */
	private function init() {
		// Load plugin classes
		$this->load_classes();
		
		// Initialize optimizer first
		$this->init_optimizer();
		
		// Initialize frontend classes
		TV_API::get_instance();
		TV_Slider::get_instance();
		TV_Table::get_instance();
		TV_Global_Slider::get_instance();
		
		// Initialize admin settings (only in admin)
		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, 'init_admin' ) );
		}
		
		// Initialize updater (only in admin)
		if ( is_admin() ) {
			TV_Updater::get_instance();
		}
		
		// Cache compatibility hooks
		add_action( 'init', array( $this, 'init_cache_compatibility' ) );
		
		// Register deactivation hook
		register_deactivation_hook( TV_PLUGIN_BASENAME, array( $this, 'handle_deactivation' ) );
		
		// Load text domain
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}
	
	/**
	 * Initialize cache compatibility
	 */
	public function init_cache_compatibility() {
		// LiteSpeed Cache compatibility
		if ( defined( 'LSCWP_V' ) ) {
			// Exclude AJAX endpoints from cache
			add_action( 'litespeed_control_set_nocache', array( $this, 'litespeed_exclude_ajax' ) );
			
			// Add cache tags
			add_action( 'litespeed_tag_finalize', array( $this, 'litespeed_add_cache_tags' ) );
		}
		
		// WP Super Cache compatibility
		if ( function_exists( 'wp_super_cache_disable' ) ) {
			add_action( 'wp_ajax_tv_table_data', array( $this, 'disable_cache_for_ajax' ) );
			add_action( 'wp_ajax_nopriv_tv_table_data', array( $this, 'disable_cache_for_ajax' ) );
		}
		
		// W3 Total Cache compatibility
		if ( defined( 'W3TC' ) ) {
			add_action( 'w3tc_flush_all', array( $this, 'clear_plugin_cache' ) );
		}
		
		// WP Rocket compatibility
		if ( function_exists( 'rocket_clean_domain' ) ) {
			add_action( 'tv_cache_cleared', array( $this, 'rocket_clear_cache' ) );
		}
	}
	
	/**
	 * Exclude AJAX endpoints from LiteSpeed Cache
	 */
	public function litespeed_exclude_ajax() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';
			$tv_actions = array( 'tv_slider', 'tv_table_data' );
			
			if ( in_array( $action, $tv_actions, true ) ) {
				do_action( 'litespeed_control_set_nocache', 'TV AJAX' );
			}
		}
	}
	
	/**
	 * Add LiteSpeed Cache tags
	 */
	public function litespeed_add_cache_tags() {
		if ( function_exists( 'litespeed_tag_add' ) ) {
			litespeed_tag_add( 'TV_CHANNELS' );
			litespeed_tag_add( 'TV_LOGOS' );
			litespeed_tag_add( 'TV_COUNTRIES' );
		}
	}
	
	/**
	 * Disable cache for AJAX requests
	 */
	public function disable_cache_for_ajax() {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
	}
	
	/**
	 * Clear plugin cache
	 */
	public function clear_plugin_cache() {
		$api = TV_API::get_instance();
		$api->clear_cache();
	}
	
	/**
	 * Clear WP Rocket cache
	 */
	public function rocket_clear_cache() {
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
	}
	
	/**
	 * Initialize admin functionality
	 */
	public function init_admin() {
		TV_Settings::get_instance();
	}
	
	/**
	 * Load plugin classes
	 */
	private function load_classes() {
		require_once TV_PLUGIN_DIR . 'includes/class-tv-api.php';
		require_once TV_PLUGIN_DIR . 'includes/class-tv-slider.php';
		require_once TV_PLUGIN_DIR . 'includes/class-tv-table.php';
		require_once TV_PLUGIN_DIR . 'includes/class-tv-global.php';
		require_once TV_PLUGIN_DIR . 'includes/class-tv-settings.php';
		require_once TV_PLUGIN_DIR . 'includes/class-tv-optimizer.php';
		require_once TV_PLUGIN_DIR . 'includes/class-tv-updater.php';
	}
	
	/**
	 * Initialize optimizer
	 */
	public function init_optimizer() {
		TV_Optimizer::get_instance();
	}
	
	/**
	 * Handle plugin deactivation
	 */
	public function handle_deactivation() {
		if ( class_exists( 'TV_Settings' ) ) {
			$settings = TV_Settings::get_instance();
			$settings->handle_deactivation();
		}
	}
	
	/**
	 * Load plugin textdomain
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'tv-world-channels', false, dirname( TV_PLUGIN_BASENAME ) . '/languages' );
	}
}

/**
 * Initialize the plugin
 */
function tv_world_channels_init() {
	return TV_World_Channels::get_instance();
}

// Start the plugin
tv_world_channels_init();

