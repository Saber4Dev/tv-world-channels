<?php
/**
 * Settings Class
 *
 * Handles admin settings page and options management
 *
 * @package TV_World_Channels
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TV_Settings Class
 */
class TV_Settings {
	
	/**
	 * Instance of this class
	 *
	 * @var TV_Settings
	 */
	private static $instance = null;
	
	/**
	 * Settings option name
	 *
	 * @var string
	 */
	private $option_name = 'tv_world_channels_settings';
	
	/**
	 * Get instance of this class
	 *
	 * @return TV_Settings
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
	 * Initialize hooks
	 */
	private function init() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_filter( 'plugin_action_links_' . TV_PLUGIN_BASENAME, array( $this, 'add_plugin_action_links' ) );
		add_action( 'admin_post_tv_clear_cache', array( $this, 'handle_clear_cache' ) );
	}
	
	/**
	 * Add settings page to WordPress admin
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'TV World Channels Settings', 'tv-world-channels' ),
			__( 'TV World Channels', 'tv-world-channels' ),
			'manage_options',
			'tv_world_channels_settings',
			array( $this, 'render_settings_page' )
		);
	}
	
	/**
	 * Register all settings
	 */
	public function register_settings() {
		// Register setting
		register_setting(
			$this->option_name,
			$this->option_name,
			array( $this, 'sanitize_settings' )
		);
		
		// Slider Settings Section
		add_settings_section(
			'tv_slider_settings',
			__( 'Slider Settings', 'tv-world-channels' ),
			array( $this, 'render_slider_section_description' ),
			'tv_world_channels_settings'
		);
		
		// Slider Speed
		add_settings_field(
			'slider_speed',
			__( 'Slider Speed (milliseconds)', 'tv-world-channels' ),
			array( $this, 'render_slider_speed_field' ),
			'tv_world_channels_settings',
			'tv_slider_settings'
		);
		
		// Logo Width
		add_settings_field(
			'logo_width',
			__( 'Logo Width (pixels)', 'tv-world-channels' ),
			array( $this, 'render_logo_width_field' ),
			'tv_world_channels_settings',
			'tv_slider_settings'
		);
		
		// Enable Autoplay
		add_settings_field(
			'enable_autoplay',
			__( 'Enable Autoplay', 'tv-world-channels' ),
			array( $this, 'render_enable_autoplay_field' ),
			'tv_world_channels_settings',
			'tv_slider_settings'
		);
		
		// Pause on Hover
		add_settings_field(
			'pause_on_hover',
			__( 'Pause on Hover', 'tv-world-channels' ),
			array( $this, 'render_pause_on_hover_field' ),
			'tv_world_channels_settings',
			'tv_slider_settings'
		);
		
		// Loop Slider
		add_settings_field(
			'loop_slider',
			__( 'Loop Slider', 'tv-world-channels' ),
			array( $this, 'render_loop_slider_field' ),
			'tv_world_channels_settings',
			'tv_slider_settings'
		);
		
		// Logo Background Color
		add_settings_field(
			'logo_background',
			__( 'Logo Background Color', 'tv-world-channels' ),
			array( $this, 'render_logo_background_field' ),
			'tv_world_channels_settings',
			'tv_slider_settings'
		);
		
		// Logo Padding
		add_settings_field(
			'logo_padding',
			__( 'Logo Padding (pixels)', 'tv-world-channels' ),
			array( $this, 'render_logo_padding_field' ),
			'tv_world_channels_settings',
			'tv_slider_settings'
		);
		
		// Logo Margin
		add_settings_field(
			'logo_margin',
			__( 'Logo Margin (pixels)', 'tv-world-channels' ),
			array( $this, 'render_logo_margin_field' ),
			'tv_world_channels_settings',
			'tv_slider_settings'
		);
		
		// Gap Between Logos
		add_settings_field(
			'logo_gap',
			__( 'Gap Between Logos (pixels)', 'tv-world-channels' ),
			array( $this, 'render_logo_gap_field' ),
			'tv_world_channels_settings',
			'tv_slider_settings'
		);
		
		// Hide Scrollbar
		add_settings_field(
			'hide_scrollbar',
			__( 'Hide Scrollbar', 'tv-world-channels' ),
			array( $this, 'render_hide_scrollbar_field' ),
			'tv_world_channels_settings',
			'tv_slider_settings'
		);
		
		// Show Slider Controls (Dots & Navigation)
		add_settings_field(
			'show_slider_controls',
			__( 'Show Slider Controls', 'tv-world-channels' ),
			array( $this, 'render_show_slider_controls_field' ),
			'tv_world_channels_settings',
			'tv_slider_settings'
		);
		
		// Table Settings Section
		add_settings_section(
			'tv_table_settings',
			__( 'Table Settings', 'tv-world-channels' ),
			array( $this, 'render_table_section_description' ),
			'tv_world_channels_settings'
		);
		
		// Show Logos in Table
		add_settings_field(
			'show_logos_in_table',
			__( 'Show Logos in Table', 'tv-world-channels' ),
			array( $this, 'render_show_logos_field' ),
			'tv_world_channels_settings',
			'tv_table_settings'
		);
		
		// Rows Per Page
		add_settings_field(
			'rows_per_page',
			__( 'Rows Per Page', 'tv-world-channels' ),
			array( $this, 'render_rows_per_page_field' ),
			'tv_world_channels_settings',
			'tv_table_settings'
		);
		
		// Enable Country Flags
		add_settings_field(
			'enable_country_flags',
			__( 'Enable Country Flags', 'tv-world-channels' ),
			array( $this, 'render_enable_country_flags_field' ),
			'tv_world_channels_settings',
			'tv_table_settings'
		);
		
		// Table Styling Section
		add_settings_section(
			'tv_table_styling',
			__( 'Table Styling', 'tv-world-channels' ),
			array( $this, 'render_table_styling_section_description' ),
			'tv_world_channels_settings'
		);
		
		// Table Background Color
		add_settings_field(
			'table_bg_color',
			__( 'Table Background Color', 'tv-world-channels' ),
			array( $this, 'render_table_bg_color_field' ),
			'tv_world_channels_settings',
			'tv_table_styling'
		);
		
		// Table Row Background Color
		add_settings_field(
			'table_row_bg_color',
			__( 'Table Row Background Color', 'tv-world-channels' ),
			array( $this, 'render_table_row_bg_color_field' ),
			'tv_world_channels_settings',
			'tv_table_styling'
		);
		
		// Table Row Hover Background Color
		add_settings_field(
			'table_row_hover_bg_color',
			__( 'Table Row Hover Background Color', 'tv-world-channels' ),
			array( $this, 'render_table_row_hover_bg_color_field' ),
			'tv_world_channels_settings',
			'tv_table_styling'
		);
		
		// Table Header Background Color
		add_settings_field(
			'table_header_bg_color',
			__( 'Table Header Background Color', 'tv-world-channels' ),
			array( $this, 'render_table_header_bg_color_field' ),
			'tv_world_channels_settings',
			'tv_table_styling'
		);
		
		// Sidebar Background Color
		add_settings_field(
			'sidebar_bg_color',
			__( 'Sidebar Background Color', 'tv-world-channels' ),
			array( $this, 'render_sidebar_bg_color_field' ),
			'tv_world_channels_settings',
			'tv_table_styling'
		);
		
		// Sidebar Active Item Background Color
		add_settings_field(
			'sidebar_active_bg_color',
			__( 'Sidebar Active Item Background Color', 'tv-world-channels' ),
			array( $this, 'render_sidebar_active_bg_color_field' ),
			'tv_world_channels_settings',
			'tv_table_styling'
		);
		
		// Button Background Color
		add_settings_field(
			'button_bg_color',
			__( 'Button Background Color', 'tv-world-channels' ),
			array( $this, 'render_button_bg_color_field' ),
			'tv_world_channels_settings',
			'tv_table_styling'
		);
		
		// Button Text Color
		add_settings_field(
			'button_text_color',
			__( 'Button Text Color', 'tv-world-channels' ),
			array( $this, 'render_button_text_color_field' ),
			'tv_world_channels_settings',
			'tv_table_styling'
		);
		
		// Button Hover Background Color
		add_settings_field(
			'button_hover_bg_color',
			__( 'Button Hover Background Color', 'tv-world-channels' ),
			array( $this, 'render_button_hover_bg_color_field' ),
			'tv_world_channels_settings',
			'tv_table_styling'
		);
		
		// Button Hover Text Color
		add_settings_field(
			'button_hover_text_color',
			__( 'Button Hover Text Color', 'tv-world-channels' ),
			array( $this, 'render_button_hover_text_color_field' ),
			'tv_world_channels_settings',
			'tv_table_styling'
		);
		
		// Button Border Radius
		add_settings_field(
			'button_border_radius',
			__( 'Button Border Radius (pixels)', 'tv-world-channels' ),
			array( $this, 'render_button_border_radius_field' ),
			'tv_world_channels_settings',
			'tv_table_styling'
		);
		
		// Box Shadow
		add_settings_field(
			'table_box_shadow',
			__( 'Table Box Shadow', 'tv-world-channels' ),
			array( $this, 'render_table_box_shadow_field' ),
			'tv_world_channels_settings',
			'tv_table_styling'
		);
		
		// Border Color
		add_settings_field(
			'table_border_color',
			__( 'Table Border Color', 'tv-world-channels' ),
			array( $this, 'render_table_border_color_field' ),
			'tv_world_channels_settings',
			'tv_table_styling'
		);
		
		// Category Background Color
		add_settings_field(
			'category_bg_color',
			__( 'Category Background Color', 'tv-world-channels' ),
			array( $this, 'render_category_bg_color_field' ),
			'tv_world_channels_settings',
			'tv_table_styling'
		);
		
		// Pagination Text Color
		add_settings_field(
			'pagination_text_color',
			__( 'Pagination Text Color', 'tv-world-channels' ),
			array( $this, 'render_pagination_text_color_field' ),
			'tv_world_channels_settings',
			'tv_table_styling'
		);
		
		// Pagination Font Family
		add_settings_field(
			'pagination_font_family',
			__( 'Pagination Font Family', 'tv-world-channels' ),
			array( $this, 'render_pagination_font_family_field' ),
			'tv_world_channels_settings',
			'tv_table_styling'
		);
		
		// Pagination Font Size
		add_settings_field(
			'pagination_font_size',
			__( 'Pagination Font Size (pixels)', 'tv-world-channels' ),
			array( $this, 'render_pagination_font_size_field' ),
			'tv_world_channels_settings',
			'tv_table_styling'
		);
		
		// Plugin Maintenance Section
		add_settings_section(
			'tv_maintenance_settings',
			__( 'Plugin Maintenance', 'tv-world-channels' ),
			array( $this, 'render_maintenance_section_description' ),
			'tv_world_channels_settings'
		);
		
		// Cleanup On Deactivation
		add_settings_field(
			'cleanup_on_deactivation',
			__( 'Cleanup On Deactivation', 'tv-world-channels' ),
			array( $this, 'render_cleanup_on_deactivation_field' ),
			'tv_world_channels_settings',
			'tv_maintenance_settings'
		);
	}
	
	/**
	 * Sanitize settings input
	 *
	 * @param array $input Raw input data
	 * @return array Sanitized data
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();
		
		// Slider settings
		$sanitized['slider_speed'] = isset( $input['slider_speed'] ) ? absint( $input['slider_speed'] ) : 3000;
		$sanitized['logo_width'] = isset( $input['logo_width'] ) ? absint( $input['logo_width'] ) : 120;
		$sanitized['enable_autoplay'] = isset( $input['enable_autoplay'] ) ? 1 : 0;
		$sanitized['pause_on_hover'] = isset( $input['pause_on_hover'] ) ? 1 : 0;
		$sanitized['loop_slider'] = isset( $input['loop_slider'] ) ? 1 : 0;
		$sanitized['logo_background'] = isset( $input['logo_background'] ) ? sanitize_text_field( $input['logo_background'] ) : '';
		$sanitized['logo_padding'] = isset( $input['logo_padding'] ) ? absint( $input['logo_padding'] ) : 10;
		$sanitized['logo_margin'] = isset( $input['logo_margin'] ) ? absint( $input['logo_margin'] ) : 0;
		$sanitized['logo_gap'] = isset( $input['logo_gap'] ) ? absint( $input['logo_gap'] ) : 20;
		$sanitized['hide_scrollbar'] = isset( $input['hide_scrollbar'] ) ? 1 : 0;
		$sanitized['show_slider_controls'] = isset( $input['show_slider_controls'] ) ? 1 : 0;
		
		// Table settings
		$sanitized['show_logos_in_table'] = isset( $input['show_logos_in_table'] ) ? 1 : 0;
		$sanitized['rows_per_page'] = isset( $input['rows_per_page'] ) ? absint( $input['rows_per_page'] ) : 25;
		$sanitized['enable_country_flags'] = isset( $input['enable_country_flags'] ) ? 1 : 0;
		
		// Table styling settings
		$sanitized['table_bg_color'] = isset( $input['table_bg_color'] ) ? sanitize_hex_color( $input['table_bg_color'] ) : '#ffffff';
		$sanitized['table_row_bg_color'] = isset( $input['table_row_bg_color'] ) ? sanitize_hex_color( $input['table_row_bg_color'] ) : '#ffffff';
		$sanitized['table_row_hover_bg_color'] = isset( $input['table_row_hover_bg_color'] ) ? sanitize_hex_color( $input['table_row_hover_bg_color'] ) : '#f5f5f5';
		$sanitized['table_header_bg_color'] = isset( $input['table_header_bg_color'] ) ? sanitize_hex_color( $input['table_header_bg_color'] ) : '#0073aa';
		$sanitized['sidebar_bg_color'] = isset( $input['sidebar_bg_color'] ) ? sanitize_hex_color( $input['sidebar_bg_color'] ) : '#f8f9fa';
		$sanitized['sidebar_active_bg_color'] = isset( $input['sidebar_active_bg_color'] ) ? sanitize_hex_color( $input['sidebar_active_bg_color'] ) : '#0073aa';
		$sanitized['button_bg_color'] = isset( $input['button_bg_color'] ) ? sanitize_hex_color( $input['button_bg_color'] ) : '#0073aa';
		$sanitized['button_text_color'] = isset( $input['button_text_color'] ) ? sanitize_hex_color( $input['button_text_color'] ) : '#ffffff';
		$sanitized['button_hover_bg_color'] = isset( $input['button_hover_bg_color'] ) ? sanitize_hex_color( $input['button_hover_bg_color'] ) : '#005a87';
		$sanitized['button_hover_text_color'] = isset( $input['button_hover_text_color'] ) ? sanitize_hex_color( $input['button_hover_text_color'] ) : '#ffffff';
		$sanitized['button_border_radius'] = isset( $input['button_border_radius'] ) ? absint( $input['button_border_radius'] ) : 4;
		$sanitized['table_box_shadow'] = isset( $input['table_box_shadow'] ) ? sanitize_text_field( $input['table_box_shadow'] ) : '0 2px 4px rgba(0,0,0,0.1)';
		$sanitized['table_border_color'] = isset( $input['table_border_color'] ) ? sanitize_hex_color( $input['table_border_color'] ) : '#dee2e6';
		$sanitized['category_bg_color'] = isset( $input['category_bg_color'] ) ? sanitize_hex_color( $input['category_bg_color'] ) : '#e9ecef';
		$sanitized['pagination_text_color'] = isset( $input['pagination_text_color'] ) ? sanitize_hex_color( $input['pagination_text_color'] ) : '#333333';
		$sanitized['pagination_font_family'] = isset( $input['pagination_font_family'] ) ? sanitize_text_field( $input['pagination_font_family'] ) : '';
		$sanitized['pagination_font_size'] = isset( $input['pagination_font_size'] ) ? absint( $input['pagination_font_size'] ) : 14;
		
		// Maintenance settings
		$sanitized['cleanup_on_deactivation'] = isset( $input['cleanup_on_deactivation'] ) ? 1 : 0;
		
		return $sanitized;
	}
	
	/**
	 * Get default settings
	 *
	 * @return array Default settings
	 */
	public function get_default_settings() {
		return array(
			'slider_speed' => 3000,
			'logo_width' => 120,
			'enable_autoplay' => 1,
			'pause_on_hover' => 1,
			'loop_slider' => 1,
			'logo_background' => '',
			'logo_padding' => 10,
			'logo_margin' => 0,
			'logo_gap' => 20,
			'hide_scrollbar' => 0,
			'show_slider_controls' => 1,
			'show_logos_in_table' => 0,
			'rows_per_page' => 25,
			'enable_country_flags' => 1,
			'table_bg_color' => '#ffffff',
			'table_row_bg_color' => '#ffffff',
			'table_row_hover_bg_color' => '#f5f5f5',
			'table_header_bg_color' => '#0073aa',
			'sidebar_bg_color' => '#f8f9fa',
			'sidebar_active_bg_color' => '#0073aa',
			'button_bg_color' => '#0073aa',
			'button_text_color' => '#ffffff',
			'button_hover_bg_color' => '#005a87',
			'button_hover_text_color' => '#ffffff',
			'button_border_radius' => 4,
			'table_box_shadow' => '0 2px 4px rgba(0,0,0,0.1)',
			'table_border_color' => '#dee2e6',
			'category_bg_color' => '#e9ecef',
			'pagination_text_color' => '#333333',
			'pagination_font_family' => '',
			'pagination_font_size' => 14,
			'cleanup_on_deactivation' => 0,
		);
	}
	
	/**
	 * Get settings with defaults
	 *
	 * @return array Settings array
	 */
	public function get_settings() {
		$settings = get_option( $this->option_name, array() );
		$defaults = $this->get_default_settings();
		return wp_parse_args( $settings, $defaults );
	}
	
	/**
	 * Get a specific setting value
	 *
	 * @param string $key Setting key
	 * @param mixed $default Default value
	 * @return mixed Setting value
	 */
	public function get_setting( $key, $default = null ) {
		$settings = $this->get_settings();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}
	
	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		// Handle tab switching
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'settings';
		
		// Show success message if cache was cleared
		if ( isset( $_GET['cache_cleared'] ) && '1' === $_GET['cache_cleared'] ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Cached API data has been cleared successfully.', 'tv-world-channels' ) . '</p></div>';
		}
		
		?>
		<div class="wrap tv-settings-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<nav class="nav-tab-wrapper">
				<a href="?page=tv_world_channels_settings&tab=settings" class="nav-tab <?php echo 'settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Settings', 'tv-world-channels' ); ?>
				</a>
				<a href="?page=tv_world_channels_settings&tab=help" class="nav-tab <?php echo 'help' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Help / Documentation', 'tv-world-channels' ); ?>
				</a>
			</nav>
			
			<div class="tv-settings-content">
				<?php if ( 'settings' === $active_tab ) : ?>
					<form method="post" action="options.php">
						<?php
						settings_fields( $this->option_name );
						do_settings_sections( 'tv_world_channels_settings' );
						submit_button();
						?>
					</form>
					
					<?php $this->render_maintenance_actions(); ?>
				<?php else : ?>
					<?php $this->render_help_tab(); ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
	
	/**
	 * Render maintenance actions (clear cache button)
	 */
	private function render_maintenance_actions() {
		?>
		<div class="tv-maintenance-actions">
			<h2><?php esc_html_e( 'Quick Actions', 'tv-world-channels' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'tv_clear_cache', 'tv_clear_cache_nonce' ); ?>
				<input type="hidden" name="action" value="tv_clear_cache" />
				<p>
					<button type="submit" class="button button-secondary tv-button-danger" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to clear all cached API data?', 'tv-world-channels' ) ); ?>');">
						<?php esc_html_e( 'Delete Cached API Data', 'tv-world-channels' ); ?>
					</button>
					<span class="description">
						<?php esc_html_e( 'This will clear all cached data from the tv-org API. Data will be re-fetched on next page load.', 'tv-world-channels' ); ?>
					</span>
				</p>
			</form>
		</div>
		<?php
	}
	
	/**
	 * Handle clear cache action
	 */
	public function handle_clear_cache() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'tv-world-channels' ) );
		}
		
		if ( ! isset( $_POST['tv_clear_cache_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tv_clear_cache_nonce'] ) ), 'tv_clear_cache' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'tv-world-channels' ) );
		}
		
		// Clear all plugin cache
		$api = TV_API::get_instance();
		$api->clear_cache();
		
		// Clear page cache if supported
		if ( function_exists( 'litespeed_purge_all' ) ) {
			litespeed_purge_all();
		}
		
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}
		
		// Redirect with success message
		wp_safe_redirect( add_query_arg( array(
			'cache_cleared' => '1',
			'tab' => 'settings',
		), admin_url( 'options-general.php?page=tv_world_channels_settings' ) ) );
		exit;
	}
	
	/**
	 * Handle plugin deactivation
	 */
	public function handle_deactivation() {
		$settings = $this->get_settings();
		
		if ( isset( $settings['cleanup_on_deactivation'] ) && 1 === $settings['cleanup_on_deactivation'] ) {
			// Delete all transients
			delete_transient( 'tv_channels_data' );
			delete_transient( 'tv_logos_data' );
			delete_transient( 'tv_countries_data' );
			
			// Delete settings option
			delete_option( $this->option_name );
		}
	}
	
	/**
	 * Add plugin action links
	 *
	 * @param array $links Existing links
	 * @return array Modified links
	 */
	public function add_plugin_action_links( $links ) {
		$settings_link = '<a href="' . admin_url( 'options-general.php?page=tv_world_channels_settings' ) . '">' . esc_html__( 'Settings', 'tv-world-channels' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
	
	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'settings_page_tv_world_channels_settings' !== $hook ) {
			return;
		}
		
		wp_enqueue_style(
			'tv-admin-css',
			TV_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			TV_PLUGIN_VERSION
		);
		
		// Enqueue WordPress color picker
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script(
			'tv-admin-js',
			TV_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-color-picker' ),
			TV_PLUGIN_VERSION,
			true
		);
	}
	
	// ============================================
	// Section Descriptions
	// ============================================
	
	/**
	 * Render slider section description
	 */
	public function render_slider_section_description() {
		echo '<p>' . esc_html__( 'Configure the appearance and behavior of channel sliders.', 'tv-world-channels' ) . '</p>';
	}
	
	/**
	 * Render table section description
	 */
	public function render_table_section_description() {
		echo '<p>' . esc_html__( 'Configure the channels directory table display options.', 'tv-world-channels' ) . '</p>';
	}
	
	/**
	 * Render maintenance section description
	 */
	public function render_maintenance_section_description() {
		echo '<p>' . esc_html__( 'Plugin maintenance and cleanup options.', 'tv-world-channels' ) . '</p>';
	}
	
	// ============================================
	// Field Renderers - Slider Settings
	// ============================================
	
	/**
	 * Render slider speed field
	 */
	public function render_slider_speed_field() {
		$value = $this->get_setting( 'slider_speed', 3000 );
		?>
		<input type="number" name="<?php echo esc_attr( $this->option_name ); ?>[slider_speed]" value="<?php echo esc_attr( $value ); ?>" min="500" max="10000" step="100" class="regular-text" />
		<p class="description"><?php esc_html_e( 'Interval delay between scroll movements in milliseconds. Default: 3000ms.', 'tv-world-channels' ); ?></p>
		<?php
	}
	
	/**
	 * Render logo width field
	 */
	public function render_logo_width_field() {
		$value = $this->get_setting( 'logo_width', 120 );
		?>
		<input type="number" name="<?php echo esc_attr( $this->option_name ); ?>[logo_width]" value="<?php echo esc_attr( $value ); ?>" min="40" max="300" step="10" class="regular-text" />
		<p class="description"><?php esc_html_e( 'Controls the displayed logo size in pixels. Default: 120px.', 'tv-world-channels' ); ?></p>
		<?php
	}
	
	/**
	 * Render enable autoplay field
	 */
	public function render_enable_autoplay_field() {
		$value = $this->get_setting( 'enable_autoplay', 1 );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[enable_autoplay]" value="1" <?php checked( $value, 1 ); ?> />
			<?php esc_html_e( 'Enable automatic scrolling of the slider', 'tv-world-channels' ); ?>
		</label>
		<?php
	}
	
	/**
	 * Render pause on hover field
	 */
	public function render_pause_on_hover_field() {
		$value = $this->get_setting( 'pause_on_hover', 1 );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[pause_on_hover]" value="1" <?php checked( $value, 1 ); ?> />
			<?php esc_html_e( 'Pause slider when user hovers over it', 'tv-world-channels' ); ?>
		</label>
		<?php
	}
	
	/**
	 * Render loop slider field
	 */
	public function render_loop_slider_field() {
		$value = $this->get_setting( 'loop_slider', 1 );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[loop_slider]" value="1" <?php checked( $value, 1 ); ?> />
			<?php esc_html_e( 'Loop slider continuously (restart from beginning when reaching the end)', 'tv-world-channels' ); ?>
		</label>
		<?php
	}
	
	/**
	 * Render logo background field
	 */
	public function render_logo_background_field() {
		$value = $this->get_setting( 'logo_background', '' );
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[logo_background]" value="<?php echo esc_attr( $value ); ?>" class="regular-text tv-color-picker" placeholder="#f5f5f5" />
		<p class="description"><?php esc_html_e( 'Background color for logo containers. Leave empty for transparent. Use hex color (e.g., #f5f5f5) or CSS color name.', 'tv-world-channels' ); ?></p>
		<?php
	}
	
	/**
	 * Render logo padding field
	 */
	public function render_logo_padding_field() {
		$value = $this->get_setting( 'logo_padding', 10 );
		?>
		<input type="number" name="<?php echo esc_attr( $this->option_name ); ?>[logo_padding]" value="<?php echo esc_attr( $value ); ?>" min="0" max="50" step="1" class="small-text" /> px
		<p class="description"><?php esc_html_e( 'Padding inside the logo container. Default: 10px.', 'tv-world-channels' ); ?></p>
		<?php
	}
	
	/**
	 * Render logo margin field
	 */
	public function render_logo_margin_field() {
		$value = $this->get_setting( 'logo_margin', 0 );
		?>
		<input type="number" name="<?php echo esc_attr( $this->option_name ); ?>[logo_margin]" value="<?php echo esc_attr( $value ); ?>" min="0" max="50" step="1" class="small-text" /> px
		<p class="description"><?php esc_html_e( 'Margin around the logo container. Default: 0px.', 'tv-world-channels' ); ?></p>
		<?php
	}
	
	/**
	 * Render logo gap field
	 */
	public function render_logo_gap_field() {
		$value = $this->get_setting( 'logo_gap', 20 );
		?>
		<input type="number" name="<?php echo esc_attr( $this->option_name ); ?>[logo_gap]" value="<?php echo esc_attr( $value ); ?>" min="0" max="100" step="5" class="small-text" /> px
		<p class="description"><?php esc_html_e( 'Gap between logo items in the slider. Default: 20px.', 'tv-world-channels' ); ?></p>
		<?php
	}
	
	/**
	 * Render hide scrollbar field
	 */
	public function render_hide_scrollbar_field() {
		$value = $this->get_setting( 'hide_scrollbar', 0 );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[hide_scrollbar]" value="1" <?php checked( $value, 1 ); ?> />
			<?php esc_html_e( 'Hide the scrollbar on logo sliders', 'tv-world-channels' ); ?>
		</label>
		<?php
	}
	
	/**
	 * Render show slider controls field
	 */
	public function render_show_slider_controls_field() {
		$value = $this->get_setting( 'show_slider_controls', 1 );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[show_slider_controls]" value="1" <?php checked( $value, 1 ); ?> />
			<?php esc_html_e( 'Show navigation arrows and dots below the slider', 'tv-world-channels' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'When enabled, displays previous/next arrows and dot indicators for navigation.', 'tv-world-channels' ); ?></p>
		<?php
	}
	
	// ============================================
	// Field Renderers - Table Settings
	// ============================================
	
	/**
	 * Render show logos field
	 */
	public function render_show_logos_field() {
		$value = $this->get_setting( 'show_logos_in_table', 0 );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[show_logos_in_table]" value="1" <?php checked( $value, 1 ); ?> />
			<?php esc_html_e( 'Display channel logos in the table (40px size)', 'tv-world-channels' ); ?>
		</label>
		<?php
	}
	
	/**
	 * Render rows per page field
	 */
	public function render_rows_per_page_field() {
		$value = $this->get_setting( 'rows_per_page', 25 );
		?>
		<input type="number" name="<?php echo esc_attr( $this->option_name ); ?>[rows_per_page]" value="<?php echo esc_attr( $value ); ?>" min="10" max="100" step="5" class="regular-text" />
		<p class="description"><?php esc_html_e( 'Number of rows to display per page in the table. Default: 25.', 'tv-world-channels' ); ?></p>
		<?php
	}
	
	/**
	 * Render enable country flags field
	 */
	public function render_enable_country_flags_field() {
		$value = $this->get_setting( 'enable_country_flags', 1 );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[enable_country_flags]" value="1" <?php checked( $value, 1 ); ?> />
			<?php esc_html_e( 'Display country flags next to country names in the table', 'tv-world-channels' ); ?>
		</label>
		<?php
	}
	
	// ============================================
	// Field Renderers - Table Styling
	// ============================================
	
	/**
	 * Render table styling section description
	 */
	public function render_table_styling_section_description() {
		echo '<p>' . esc_html__( 'Customize the appearance of the channels table, including colors, buttons, and visual effects.', 'tv-world-channels' ) . '</p>';
	}
	
	/**
	 * Render table background color field
	 */
	public function render_table_bg_color_field() {
		$value = $this->get_setting( 'table_bg_color', '#ffffff' );
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[table_bg_color]" value="<?php echo esc_attr( $value ); ?>" class="tv-color-picker" data-default-color="#ffffff" />
		<p class="description"><?php esc_html_e( 'Background color for the table container. Default: #ffffff (white).', 'tv-world-channels' ); ?></p>
		<?php
	}
	
	/**
	 * Render table row background color field
	 */
	public function render_table_row_bg_color_field() {
		$value = $this->get_setting( 'table_row_bg_color', '#ffffff' );
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[table_row_bg_color]" value="<?php echo esc_attr( $value ); ?>" class="tv-color-picker" data-default-color="#ffffff" />
		<p class="description"><?php esc_html_e( 'Background color for table rows. Default: #ffffff (white).', 'tv-world-channels' ); ?></p>
		<?php
	}
	
	/**
	 * Render table row hover background color field
	 */
	public function render_table_row_hover_bg_color_field() {
		$value = $this->get_setting( 'table_row_hover_bg_color', '#f5f5f5' );
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[table_row_hover_bg_color]" value="<?php echo esc_attr( $value ); ?>" class="tv-color-picker" data-default-color="#f5f5f5" />
		<p class="description"><?php esc_html_e( 'Background color when hovering over table rows. Default: #f5f5f5 (light gray).', 'tv-world-channels' ); ?></p>
		<?php
	}
	
	/**
	 * Render table header background color field
	 */
	public function render_table_header_bg_color_field() {
		$value = $this->get_setting( 'table_header_bg_color', '#0073aa' );
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[table_header_bg_color]" value="<?php echo esc_attr( $value ); ?>" class="tv-color-picker" data-default-color="#0073aa" />
		<p class="description"><?php esc_html_e( 'Background color for table headers. Default: #0073aa (WordPress blue).', 'tv-world-channels' ); ?></p>
		<?php
	}
	
	/**
	 * Render sidebar background color field
	 */
	public function render_sidebar_bg_color_field() {
		$value = $this->get_setting( 'sidebar_bg_color', '#f8f9fa' );
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[sidebar_bg_color]" value="<?php echo esc_attr( $value ); ?>" class="tv-color-picker" data-default-color="#f8f9fa" />
		<p class="description"><?php esc_html_e( 'Background color for the country sidebar. Default: #f8f9fa (light gray).', 'tv-world-channels' ); ?></p>
		<?php
	}
	
	/**
	 * Render sidebar active item background color field
	 */
	public function render_sidebar_active_bg_color_field() {
		$value = $this->get_setting( 'sidebar_active_bg_color', '#0073aa' );
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[sidebar_active_bg_color]" value="<?php echo esc_attr( $value ); ?>" class="tv-color-picker" data-default-color="#0073aa" />
		<p class="description"><?php esc_html_e( 'Background color for the active country item in sidebar. Default: #0073aa (WordPress blue).', 'tv-world-channels' ); ?></p>
		<?php
	}
	
	/**
	 * Render button background color field
	 */
	public function render_button_bg_color_field() {
		$value = $this->get_setting( 'button_bg_color', '#0073aa' );
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[button_bg_color]" value="<?php echo esc_attr( $value ); ?>" class="tv-color-picker" data-default-color="#0073aa" />
		<p class="description"><?php esc_html_e( 'Background color for pagination and action buttons. Default: #0073aa (WordPress blue).', 'tv-world-channels' ); ?></p>
		<?php
	}
	
	/**
	 * Render button text color field
	 */
	public function render_button_text_color_field() {
		$value = $this->get_setting( 'button_text_color', '#ffffff' );
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[button_text_color]" value="<?php echo esc_attr( $value ); ?>" class="tv-color-picker" data-default-color="#ffffff" />
		<p class="description"><?php esc_html_e( 'Text color for buttons. Default: #ffffff (white).', 'tv-world-channels' ); ?></p>
		<?php
	}
	
	/**
	 * Render button hover background color field
	 */
	public function render_button_hover_bg_color_field() {
		$value = $this->get_setting( 'button_hover_bg_color', '#005a87' );
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[button_hover_bg_color]" value="<?php echo esc_attr( $value ); ?>" class="tv-color-picker" data-default-color="#005a87" />
		<p class="description"><?php esc_html_e( 'Background color when hovering over buttons. Default: #005a87 (darker blue).', 'tv-world-channels' ); ?></p>
		<?php
	}
	
	/**
	 * Render button hover text color field
	 */
	public function render_button_hover_text_color_field() {
		$value = $this->get_setting( 'button_hover_text_color', '#ffffff' );
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[button_hover_text_color]" value="<?php echo esc_attr( $value ); ?>" class="tv-color-picker" data-default-color="#ffffff" />
		<p class="description"><?php esc_html_e( 'Text color when hovering over buttons. Default: #ffffff (white).', 'tv-world-channels' ); ?></p>
		<?php
	}
	
	/**
	 * Render button border radius field
	 */
	public function render_button_border_radius_field() {
		$value = $this->get_setting( 'button_border_radius', 4 );
		?>
		<input type="number" name="<?php echo esc_attr( $this->option_name ); ?>[button_border_radius]" value="<?php echo esc_attr( $value ); ?>" min="0" max="50" step="1" class="small-text" /> px
		<p class="description"><?php esc_html_e( 'Border radius for buttons (rounded corners). Default: 4px.', 'tv-world-channels' ); ?></p>
		<?php
	}
	
	/**
	 * Render table box shadow field
	 */
	public function render_table_box_shadow_field() {
		$value = $this->get_setting( 'table_box_shadow', '0 2px 4px rgba(0,0,0,0.1)' );
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[table_box_shadow]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="0 2px 4px rgba(0,0,0,0.1)" />
		<p class="description"><?php esc_html_e( 'Box shadow for table container. Use CSS box-shadow syntax. Default: 0 2px 4px rgba(0,0,0,0.1). Leave empty to remove shadow.', 'tv-world-channels' ); ?></p>
		<?php
	}
	
	/**
	 * Render table border color field
	 */
	public function render_table_border_color_field() {
		$value = $this->get_setting( 'table_border_color', '#dee2e6' );
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[table_border_color]" value="<?php echo esc_attr( $value ); ?>" class="tv-color-picker" data-default-color="#dee2e6" />
		<p class="description"><?php esc_html_e( 'Border color for table and sidebar. Default: #dee2e6 (light gray).', 'tv-world-channels' ); ?></p>
		<?php
	}
	
	/**
	 * Render category background color field
	 */
	public function render_category_bg_color_field() {
		$value = $this->get_setting( 'category_bg_color', '#e9ecef' );
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[category_bg_color]" value="<?php echo esc_attr( $value ); ?>" class="tv-color-picker" data-default-color="#e9ecef" />
		<p class="description"><?php esc_html_e( 'Background color for category cells in the table. Default: #e9ecef (light gray).', 'tv-world-channels' ); ?></p>
		<?php
	}
	
	/**
	 * Render pagination text color field
	 */
	public function render_pagination_text_color_field() {
		$value = $this->get_setting( 'pagination_text_color', '#333333' );
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[pagination_text_color]" value="<?php echo esc_attr( $value ); ?>" class="tv-color-picker" data-default-color="#333333" />
		<p class="description"><?php esc_html_e( 'Text color for pagination info and controls. Default: #333333 (dark gray).', 'tv-world-channels' ); ?></p>
		<?php
	}
	
	/**
	 * Render pagination font family field
	 */
	public function render_pagination_font_family_field() {
		$value = $this->get_setting( 'pagination_font_family', '' );
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[pagination_font_family]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="Arial, sans-serif" />
		<p class="description"><?php esc_html_e( 'Font family for pagination text. Use CSS font-family syntax (e.g., "Arial, sans-serif" or "Roboto"). Leave empty for default.', 'tv-world-channels' ); ?></p>
		<?php
	}
	
	/**
	 * Render pagination font size field
	 */
	public function render_pagination_font_size_field() {
		$value = $this->get_setting( 'pagination_font_size', 14 );
		?>
		<input type="number" name="<?php echo esc_attr( $this->option_name ); ?>[pagination_font_size]" value="<?php echo esc_attr( $value ); ?>" min="10" max="24" step="1" class="small-text" /> px
		<p class="description"><?php esc_html_e( 'Font size for pagination text. Default: 14px.', 'tv-world-channels' ); ?></p>
		<?php
	}
	
	// ============================================
	// Field Renderers - Maintenance Settings
	// ============================================
	
	/**
	 * Render cleanup on deactivation field
	 */
	public function render_cleanup_on_deactivation_field() {
		$value = $this->get_setting( 'cleanup_on_deactivation', 0 );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[cleanup_on_deactivation]" value="1" <?php checked( $value, 1 ); ?> />
			<?php esc_html_e( 'Delete all plugin data (settings and cached API data) when plugin is deactivated', 'tv-world-channels' ); ?>
		</label>
		<p class="description">
			<strong><?php esc_html_e( 'Warning:', 'tv-world-channels' ); ?></strong>
			<?php esc_html_e( 'If enabled, all plugin settings and cached data will be permanently deleted when you deactivate the plugin.', 'tv-world-channels' ); ?>
		</p>
		<?php
	}
	
	// ============================================
	// Help Tab Content
	// ============================================
	
	/**
	 * Render help tab content
	 */
	private function render_help_tab() {
		?>
		<div class="tv-help-content">
			<h2><?php esc_html_e( 'TV World Channels — Usage Guide', 'tv-world-channels' ); ?></h2>
			
			<div class="tv-help-section">
				<h3><?php esc_html_e( 'Overview', 'tv-world-channels' ); ?></h3>
				<p>
					<?php esc_html_e( 'TV World Channels is a WordPress plugin that displays TV channels from around the world using data from the tv-org API. The plugin provides three powerful shortcodes to display channels in different formats: sliders, tables, and interactive selectors.', 'tv-world-channels' ); ?>
				</p>
			</div>
			
			<div class="tv-help-section">
				<h3><?php esc_html_e( 'Shortcodes', 'tv-world-channels' ); ?></h3>
				
				<h4><?php esc_html_e( '1. Channel Slider', 'tv-world-channels' ); ?></h4>
				<code>[tv country="france" logos="on" names="hide" sort="popular"]</code>
				<p>
					<strong><?php esc_html_e( 'Parameters:', 'tv-world-channels' ); ?></strong>
				</p>
				<ul>
					<li><code>country</code> - <?php esc_html_e( 'Country name or ISO code (e.g., "france", "spain", "US"). Default: "france"', 'tv-world-channels' ); ?></li>
					<li><code>logos</code> - <?php esc_html_e( 'Show or hide channel logos. Options: "on" or "off". Default: "on"', 'tv-world-channels' ); ?></li>
					<li><code>names</code> - <?php esc_html_e( 'Show or hide channel names. Options: "show" or "hide". Default: "show"', 'tv-world-channels' ); ?></li>
					<li><code>sort</code> - <?php esc_html_e( 'Sort channels. Options: "popular" (popular channels first), "name" (alphabetical), or "default" (no sorting). Default: "default"', 'tv-world-channels' ); ?></li>
				</ul>
				<p>
					<strong><?php esc_html_e( 'Examples:', 'tv-world-channels' ); ?></strong>
				</p>
				<ul>
					<li><code>[tv country="spain"]</code> - <?php esc_html_e( 'Spanish channels with logos and names', 'tv-world-channels' ); ?></li>
					<li><code>[tv country="brazil" logos="on" names="hide"]</code> - <?php esc_html_e( 'Brazilian channels with logos only', 'tv-world-channels' ); ?></li>
					<li><code>[tv country="US" logos="off" names="show"]</code> - <?php esc_html_e( 'US channels with names only', 'tv-world-channels' ); ?></li>
					<li><code>[tv country="france" logos="on" names="hide" sort="popular"]</code> - <?php esc_html_e( 'French channels sorted by popularity (popular channels first)', 'tv-world-channels' ); ?></li>
					<li><code>[tv country="germany" sort="name"]</code> - <?php esc_html_e( 'German channels sorted alphabetically by name', 'tv-world-channels' ); ?></li>
				</ul>
				
				<h4><?php esc_html_e( '2. Channels Directory Table', 'tv-world-channels' ); ?></h4>
				<code>[tv_table]</code>
				<p>
					<?php esc_html_e( 'Displays a comprehensive table of all available TV channels with search, filtering, sorting, and pagination features.', 'tv-world-channels' ); ?>
				</p>
				<p>
					<strong><?php esc_html_e( 'Parameters:', 'tv-world-channels' ); ?></strong>
				</p>
				<ul>
					<li><code>logos</code> - <?php esc_html_e( 'Show or hide channel logos in table. Options: "on" or "off". Default: "on"', 'tv-world-channels' ); ?></li>
				</ul>
				<p>
					<strong><?php esc_html_e( 'Features:', 'tv-world-channels' ); ?></strong>
				</p>
				<ul>
					<li><?php esc_html_e( 'Search box for filtering channels', 'tv-world-channels' ); ?></li>
					<li><?php esc_html_e( 'Country dropdown filter', 'tv-world-channels' ); ?></li>
					<li><?php esc_html_e( 'Column sorting (click column headers)', 'tv-world-channels' ); ?></li>
					<li><?php esc_html_e( 'Pagination controls', 'tv-world-channels' ); ?></li>
					<li><?php esc_html_e( 'Responsive design with horizontal scroll', 'tv-world-channels' ); ?></li>
				</ul>
				
				<h4><?php esc_html_e( '3. Global Interactive Slider', 'tv-world-channels' ); ?></h4>
				<code>[tv_global_slider]</code>
				<p>
					<?php esc_html_e( 'Displays a country selector dropdown and dynamically loads channels for the selected country using AJAX.', 'tv-world-channels' ); ?>
				</p>
				<p>
					<strong><?php esc_html_e( 'Parameters:', 'tv-world-channels' ); ?></strong>
				</p>
				<ul>
					<li><code>logos</code> - <?php esc_html_e( 'Show or hide channel logos. Options: "on" or "off". Default: "on"', 'tv-world-channels' ); ?></li>
					<li><code>names</code> - <?php esc_html_e( 'Show or hide channel names. Options: "show" or "hide". Default: "show"', 'tv-world-channels' ); ?></li>
					<li><code>default_country</code> - <?php esc_html_e( 'Default country to display on page load. Default: "france"', 'tv-world-channels' ); ?></li>
				</ul>
				<p>
					<strong><?php esc_html_e( 'Example:', 'tv-world-channels' ); ?></strong>
				</p>
				<ul>
					<li><code>[tv_global_slider logos="on" names="show" default_country="brazil"]</code></li>
				</ul>
			</div>
			
			<div class="tv-help-section">
				<h3><?php esc_html_e( 'Customizing Styles', 'tv-world-channels' ); ?></h3>
				<p>
					<?php esc_html_e( 'You can override the plugin styles by adding custom CSS to your theme. Here are some examples:', 'tv-world-channels' ); ?>
				</p>
				<pre><code>/* Change slider logo size */
.tv-slider-logo {
	width: 150px !important;
	height: 100px !important;
}

/* Change slider item spacing */
.tv-slider {
	gap: 30px;
}

/* Customize table appearance */
#tv-channels-table th {
	background-color: #your-color;
}

/* Hide slider scrollbar */
.tv-slider::-webkit-scrollbar {
	display: none;
}</code></pre>
			</div>
			
			<div class="tv-help-section">
				<h3><?php esc_html_e( 'Troubleshooting', 'tv-world-channels' ); ?></h3>
				
				<h4><?php esc_html_e( 'API Cache Issues', 'tv-world-channels' ); ?></h4>
				<p>
					<?php esc_html_e( 'If channels are not updating or showing outdated information:', 'tv-world-channels' ); ?>
				</p>
				<ul>
					<li><?php esc_html_e( 'Go to Settings → TV World Channels → Quick Actions', 'tv-world-channels' ); ?></li>
					<li><?php esc_html_e( 'Click "Delete Cached API Data" to clear the cache', 'tv-world-channels' ); ?></li>
					<li><?php esc_html_e( 'Data will be automatically re-fetched on the next page load', 'tv-world-channels' ); ?></li>
				</ul>
				
				<h4><?php esc_html_e( 'Slow Loading', 'tv-world-channels' ); ?></h4>
				<p>
					<?php esc_html_e( 'If pages are loading slowly:', 'tv-world-channels' ); ?>
				</p>
				<ul>
					<li><?php esc_html_e( 'The plugin caches API data for 12 hours to improve performance', 'tv-world-channels' ); ?></li>
					<li><?php esc_html_e( 'First load may be slower as data is fetched from the API', 'tv-world-channels' ); ?></li>
					<li><?php esc_html_e( 'Consider using a caching plugin for your WordPress site', 'tv-world-channels' ); ?></li>
					<li><?php esc_html_e( 'Reduce the number of channels displayed per page in table settings', 'tv-world-channels' ); ?></li>
				</ul>
				
				<h4><?php esc_html_e( 'No Channels Displayed', 'tv-world-channels' ); ?></h4>
				<ul>
					<li><?php esc_html_e( 'Check that the country code is correct (use ISO 2-letter codes or country names)', 'tv-world-channels' ); ?></li>
					<li><?php esc_html_e( 'Verify your site can access external APIs (check firewall settings)', 'tv-world-channels' ); ?></li>
					<li><?php esc_html_e( 'Clear the API cache and reload the page', 'tv-world-channels' ); ?></li>
				</ul>
			</div>
			
			<div class="tv-help-section">
				<h3><?php esc_html_e( 'API Source', 'tv-world-channels' ); ?></h3>
				<p>
					<?php esc_html_e( 'This plugin uses data from the tv-org API:', 'tv-world-channels' ); ?>
					<a href="https://tv-org.github.io/api/" target="_blank" rel="noopener noreferrer">https://tv-org.github.io/api/</a>
				</p>
				<p>
					<?php esc_html_e( 'The API provides free access to TV channel information, logos, and metadata from around the world.', 'tv-world-channels' ); ?>
				</p>
			</div>
		</div>
		<?php
	}
}

