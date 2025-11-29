<?php
/**
 * Table Shortcode Class
 *
 * Handles [tv_table] shortcode for displaying channels table
 *
 * @package TV_World_Channels
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TV_Table Class
 */
class TV_Table {
	
	/**
	 * Instance of this class
	 *
	 * @var TV_Table
	 */
	private static $instance = null;
	
	/**
	 * API instance
	 *
	 * @var TV_API
	 */
	private $api;
	
	/**
	 * Settings instance
	 *
	 * @var TV_Settings
	 */
	private $settings;
	
	/**
	 * Show logos in table (set via PHP variable)
	 *
	 * @var bool
	 */
	private $show_logos = true;
	
	/**
	 * Get instance of this class
	 *
	 * @return TV_Table
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
		$this->api = TV_API::get_instance();
		$this->settings = TV_Settings::get_instance();
		$this->init();
	}
	
	/**
	 * Initialize hooks
	 */
	private function init() {
		add_shortcode( 'tv_table', array( $this, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		
		// AJAX handlers for lazy loading
		add_action( 'wp_ajax_tv_table_data', array( $this, 'ajax_get_table_data' ) );
		add_action( 'wp_ajax_nopriv_tv_table_data', array( $this, 'ajax_get_table_data' ) );
	}
	
	/**
	 * Enqueue table assets
	 */
	public function enqueue_assets() {
		// Only enqueue if shortcode is used
		if ( ! $this->is_shortcode_used() ) {
			return;
		}
		
		// DataTables CSS
		wp_enqueue_style(
			'datatables-css',
			'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css',
			array(),
			'1.13.7'
		);
		
		// Plugin table CSS
		wp_enqueue_style(
			'tv-table-css',
			TV_PLUGIN_URL . 'assets/css/table.css',
			array(),
			TV_PLUGIN_VERSION
		);
		
		// Add custom inline styles based on settings
		$this->add_custom_table_styles();
		
		// DataTables JS (requires jQuery)
		wp_enqueue_script(
			'datatables-js',
			'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
			array( 'jquery' ),
			'1.13.7',
			true
		);
		
		// Plugin table JS
		wp_enqueue_script(
			'tv-table-js',
			TV_PLUGIN_URL . 'assets/js/table.js',
			array( 'jquery', 'datatables-js' ),
			TV_PLUGIN_VERSION,
			true
		);
		
		// Add defer attributes
		add_filter( 'script_loader_tag', array( $this, 'defer_table_scripts' ), 10, 2 );
	}
	
	/**
	 * Check if shortcode is used on current page
	 *
	 * @return bool
	 */
	private function is_shortcode_used() {
		global $post;
		
		if ( is_admin() ) {
			return false;
		}
		
		// Check if shortcode exists in post content
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'tv_table' ) ) {
			return true;
		}
		
		// Check widgets
		if ( is_active_widget( false, false, 'text' ) ) {
			$widgets = get_option( 'widget_text' );
			if ( is_array( $widgets ) ) {
				foreach ( $widgets as $widget ) {
					if ( isset( $widget['text'] ) && has_shortcode( $widget['text'], 'tv_table' ) ) {
						return true;
					}
				}
			}
		}
		
		// Always return true to be safe (shortcode might be in template)
		return true;
	}
	
	/**
	 * Add defer attribute to table scripts
	 *
	 * @param string $tag Script tag
	 * @param string $handle Script handle
	 * @return string Modified script tag
	 */
	public function defer_table_scripts( $tag, $handle ) {
		// Don't defer tv-table-js as it needs to run after data is available
		if ( 'datatables-js' === $handle ) {
			return str_replace( ' src', ' defer src', $tag );
		}
		
		return $tag;
	}
	
	/**
	 * Render shortcode
	 *
	 * @param array $atts Shortcode attributes
	 * @return string Shortcode output
	 */
	public function render_shortcode( $atts ) {
		// Parse attributes
		$atts = shortcode_atts( array(
			'country' => '',
			'category' => '',
			'sort' => 'default',
			'logos' => 'on',
		), $atts, 'tv_table' );
		
		// Get settings
		$settings = $this->settings->get_settings();
		
		// Use admin setting as default, but allow shortcode to override
		// If shortcode explicitly sets logos="off", respect that
		if ( 'off' === strtolower( $atts['logos'] ) ) {
			$this->show_logos = false;
		} else {
			// Use admin setting if shortcode is "on" or not specified
			$this->show_logos = isset( $settings['show_logos_in_table'] ) && 1 === $settings['show_logos_in_table'];
		}
		
		// Sanitize shortcode attributes
		$country = sanitize_text_field( $atts['country'] );
		$category = sanitize_text_field( $atts['category'] );
		$sort = sanitize_text_field( $atts['sort'] );
		
		$rows_per_page = isset( $settings['rows_per_page'] ) ? absint( $settings['rows_per_page'] ) : 25;
		$enable_country_flags = isset( $settings['enable_country_flags'] ) && 1 === $settings['enable_country_flags'];
		
		// Store instance variables for localization
		$show_logos_instance = $this->show_logos;
		
		// Localize script with actual shortcode settings
		// Use wp_footer to ensure script is enqueued first
		add_action( 'wp_footer', function() use ( $rows_per_page, $enable_country_flags, $show_logos_instance, $country, $category, $sort ) {
			if ( wp_script_is( 'tv-table-js', 'enqueued' ) ) {
				// Standard localization
				wp_localize_script( 'tv-table-js', 'tvTableData', array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce' => wp_create_nonce( 'tv_table_nonce' ),
					'showLogos' => $show_logos_instance,
					'rowsPerPage' => $rows_per_page,
					'enableCountryFlags' => $enable_country_flags,
					'country' => $country,
					'category' => $category,
					'sort' => $sort,
				) );
				
				// Inline script as backup (runs before main script)
				$script_data = array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce' => wp_create_nonce( 'tv_table_nonce' ),
					'showLogos' => $show_logos_instance,
					'rowsPerPage' => $rows_per_page,
					'enableCountryFlags' => $enable_country_flags,
					'country' => $country,
					'category' => $category,
					'sort' => $sort,
				);
				wp_add_inline_script( 'tv-table-js', 'var tvTableData = ' . wp_json_encode( $script_data ) . ';', 'before' );
			}
		}, 5 );
		
		// Add custom styles after show_logos is determined
		$this->add_custom_table_styles();
		
		// Generate table HTML with country/category info
		return $this->generate_table_html( $country, $category );
	}
	
	/**
	 * Prepare table data for JavaScript
	 *
	 * @param array $channels Array of channel data
	 * @param bool $show_logos Whether to include logos
	 * @return array Prepared data
	 */
	private function prepare_table_data( $channels, $show_logos = true ) {
		$data = array();
		
		if ( ! is_array( $channels ) || empty( $channels ) ) {
			return $data;
		}
		
		foreach ( $channels as $channel ) {
			if ( ! is_array( $channel ) ) {
				continue;
			}
			
			$channel_id = isset( $channel['id'] ) ? $channel['id'] : '';
			$channel_name = isset( $channel['name'] ) ? $channel['name'] : '';
			$country_code = isset( $channel['country'] ) ? $channel['country'] : '';
			$categories = isset( $channel['categories'] ) && is_array( $channel['categories'] ) 
				? implode( ', ', $channel['categories'] ) 
				: '';
			$website = isset( $channel['website'] ) ? $channel['website'] : '';
			
			// Skip if missing essential data (at least name is required)
			if ( empty( $channel_name ) ) {
				continue;
			}
			
			// Get country name
			$country_name = '';
			if ( ! empty( $country_code ) ) {
				$country_name = $this->api->get_country_name( $country_code );
				if ( false === $country_name ) {
					$country_name = strtoupper( $country_code );
				}
			}
			
			// Get logo URL (only if show_logos is true and we have a channel ID)
			$logo_url = '';
			if ( $show_logos && ! empty( $channel_id ) ) {
				$logo_url = $this->api->get_channel_logo( $channel_id );
				$logo_url = $logo_url ? $logo_url : '';
			}
			
			$data[] = array(
				'country' => $country_name,
				'countryCode' => ! empty( $country_code ) ? strtoupper( $country_code ) : '',
				'name' => $channel_name,
				'category' => $categories,
				'website' => $website,
				'logo' => $logo_url,
			);
		}
		
		return $data;
	}
	
	/**
	 * Generate table HTML
	 *
	 * @param string $country Country code/name
	 * @param string $category Category name
	 * @return string HTML output
	 */
	private function generate_table_html( $country = '', $category = '' ) {
		// Get settings for data attributes
		$settings = $this->settings->get_settings();
		$rows_per_page = isset( $settings['rows_per_page'] ) ? absint( $settings['rows_per_page'] ) : 25;
		$enable_country_flags = isset( $settings['enable_country_flags'] ) && 1 === $settings['enable_country_flags'];
		
		$output = '<div class="tv-table-wrapper" data-ajax-url="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '" data-nonce="' . esc_attr( wp_create_nonce( 'tv_table_nonce' ) ) . '" data-show-logos="' . ( $this->show_logos ? '1' : '0' ) . '" data-rows-per-page="' . esc_attr( $rows_per_page ) . '" data-enable-flags="' . ( $enable_country_flags ? '1' : '0' ) . '">';
		
		// Sidebar with country list
		$output .= '<div class="tv-table-sidebar">';
		
		// Add h3 heading for SEO if country is specified
		if ( ! empty( $country ) ) {
			$country_name = $this->api->get_country_name( strtoupper( $country ) );
			if ( false === $country_name ) {
				$country_name = ucfirst( $country );
			}
			$heading_text = ! empty( $category ) 
				? ucfirst( $category ) . ' TV Channels in ' . esc_html( $country_name )
				: 'TV Channels in ' . esc_html( $country_name );
			$output .= '<h3 class="tv-table-country-heading">' . esc_html( $heading_text ) . '</h3>';
		}
		
		$output .= '<ul class="tv-country-list">';
		$output .= '<li class="tv-country-item">';
		$output .= '<a href="#" class="tv-country-link global active" data-country="">' . esc_html__( 'Global', 'tv-world-channels' ) . '</a>';
		$output .= '</li>';
		$output .= '<!-- Country list will be populated by JavaScript -->';
		$output .= '</ul>';
		$output .= '</div>';
		
		// Main content area
		$output .= '<div class="tv-table-main">';
		
		// Search control
		$output .= '<div class="tv-table-controls">';
		$output .= '<div class="tv-table-search">';
		$output .= '<label for="tv-table-search-input">' . esc_html__( 'Search Channels:', 'tv-world-channels' ) . '</label>';
		$output .= '<input type="text" id="tv-table-search-input" placeholder="' . esc_attr__( 'Search channels...', 'tv-world-channels' ) . '" />';
		$output .= '</div>';
		$output .= '</div>';
		
		// Table
		$output .= '<div class="tv-table-container">';
		$output .= '<div class="tv-table-loading">' . esc_html__( 'Loading channels...', 'tv-world-channels' ) . '</div>';
		$output .= '<table id="tv-channels-table" class="display" style="width:100%; display:none;">';
		$output .= '<thead>';
		$output .= '<tr>';
		$output .= '<th>' . esc_html__( 'Country', 'tv-world-channels' ) . '</th>';
		
		if ( $this->show_logos ) {
			$output .= '<th>' . esc_html__( 'Logo', 'tv-world-channels' ) . '</th>';
		}
		
		$output .= '<th>' . esc_html__( 'Channel Name', 'tv-world-channels' ) . '</th>';
		$output .= '<th>' . esc_html__( 'Category', 'tv-world-channels' ) . '</th>';
		$output .= '<th>' . esc_html__( 'Website', 'tv-world-channels' ) . '</th>';
		$output .= '</tr>';
		$output .= '</thead>';
		$output .= '<tbody>';
		$output .= '<!-- Data will be populated by JavaScript -->';
		$output .= '</tbody>';
		$output .= '</table>';
		$output .= '</div>';
		
		$output .= '</div>'; // .tv-table-main
		$output .= '</div>'; // .tv-table-wrapper
		
		return $output;
	}
	
	/**
	 * AJAX handler to get table data
	 */
	public function ajax_get_table_data() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tv_table_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'tv-world-channels' ) ) );
		}
		
		// Disable caching for this request
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		
		// Set no-cache headers
		nocache_headers();
		
		// Get filter parameters from POST
		$country = isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : '';
		$category = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '';
		$sort = isset( $_POST['sort'] ) ? sanitize_text_field( wp_unslash( $_POST['sort'] ) ) : 'default';
		
		// Get channels based on country filter
		if ( ! empty( $country ) ) {
			// Normalize country code
			$country_code = $this->normalize_country_code( $country );
			if ( false !== $country_code ) {
				$channels = $this->api->get_channels_by_country( $country_code );
			} else {
				$channels = array();
			}
		} else {
			// Get all channels (excluding NSFW)
			$all_channels = $this->api->get_channels();
			
			if ( false === $all_channels ) {
				wp_send_json_error( array( 
					'message' => __( 'Unable to load channels data from API.', 'tv-world-channels' ),
					'debug' => 'API returned false'
				) );
			}
			
			if ( ! is_array( $all_channels ) ) {
				wp_send_json_error( array( 
					'message' => __( 'Invalid data format received from API.', 'tv-world-channels' ),
					'debug' => 'Data is not an array'
				) );
			}
			
			// Filter out NSFW channels
			$channels = array_filter( $all_channels, function( $channel ) {
				return ! isset( $channel['is_nsfw'] ) || false === $channel['is_nsfw'];
			} );
		}
		
		// Filter by category if specified
		if ( ! empty( $category ) ) {
			$channels = $this->filter_channels_by_category( $channels, $category );
		}
		
		// Re-index array after filtering
		$channels = array_values( $channels );
		
		if ( empty( $channels ) ) {
			wp_send_json_error( array( 
				'message' => __( 'No channels found after filtering.', 'tv-world-channels' ),
				'debug' => 'All channels filtered out'
			) );
		}
		
		// Sort channels if requested
		if ( 'popular' === strtolower( $sort ) ) {
			$channels = $this->sort_channels_by_popularity( $channels, ! empty( $country ) ? $country : '' );
		} elseif ( 'name' === strtolower( $sort ) ) {
			$channels = $this->sort_channels_by_name( $channels );
		}
		
		// Get countries list
		$countries = $this->api->get_countries_list();
		
		// Get settings for show_logos
		$settings = $this->settings->get_settings();
		$show_logos = isset( $settings['show_logos_in_table'] ) && 1 === $settings['show_logos_in_table'];
		
		// Prepare data for JavaScript
		$table_data = $this->prepare_table_data( $channels, $show_logos );
		
		if ( empty( $table_data ) ) {
			wp_send_json_error( array( 
				'message' => __( 'No valid channel data to display.', 'tv-world-channels' ),
				'debug' => 'prepare_table_data returned empty array'
			) );
		}
		
		wp_send_json_success( array(
			'data' => $table_data,
			'countries' => $countries,
			'debug' => array(
				'total_channels' => count( $all_channels ),
				'filtered_channels' => count( $channels ),
				'table_data_count' => count( $table_data ),
			),
		) );
	}
	
	/**
	 * Add custom inline styles based on settings
	 */
	public function add_custom_table_styles() {
		$settings = $this->settings->get_settings();
		
		$css = '';
		
		// Table background color
		if ( ! empty( $settings['table_bg_color'] ) ) {
			$css .= '.tv-table-container { background-color: ' . esc_attr( $settings['table_bg_color'] ) . ' !important; }';
		}
		
		// Table row background color
		if ( ! empty( $settings['table_row_bg_color'] ) ) {
			$css .= '#tv-channels-table tbody tr { background-color: ' . esc_attr( $settings['table_row_bg_color'] ) . ' !important; }';
		}
		
		// Table row hover background color
		if ( ! empty( $settings['table_row_hover_bg_color'] ) ) {
			$css .= '#tv-channels-table tbody tr:hover { background-color: ' . esc_attr( $settings['table_row_hover_bg_color'] ) . ' !important; }';
		}
		
		// Table header background color
		if ( ! empty( $settings['table_header_bg_color'] ) ) {
			$css .= '#tv-channels-table thead th { background-color: ' . esc_attr( $settings['table_header_bg_color'] ) . ' !important; color: #ffffff !important; }';
		}
		
		// Sidebar background color
		if ( ! empty( $settings['sidebar_bg_color'] ) ) {
			$css .= '.tv-table-sidebar { background-color: ' . esc_attr( $settings['sidebar_bg_color'] ) . ' !important; }';
		}
		
		// Sidebar active item background color
		if ( ! empty( $settings['sidebar_active_bg_color'] ) ) {
			$css .= '.tv-country-link.active { background-color: ' . esc_attr( $settings['sidebar_active_bg_color'] ) . ' !important; color: #ffffff !important; }';
		}
		
		// Button background color
		if ( ! empty( $settings['button_bg_color'] ) ) {
			$css .= '.dataTables_wrapper .dataTables_paginate .paginate_button, .dataTables_wrapper .dataTables_length select, .dataTables_wrapper .dataTables_filter input { background-color: ' . esc_attr( $settings['button_bg_color'] ) . ' !important; }';
		}
		
		// Button text color
		if ( ! empty( $settings['button_text_color'] ) ) {
			$css .= '.dataTables_wrapper .dataTables_paginate .paginate_button, .dataTables_wrapper .dataTables_length select, .dataTables_wrapper .dataTables_filter input { color: ' . esc_attr( $settings['button_text_color'] ) . ' !important; }';
		}
		
		// Button hover background color
		if ( ! empty( $settings['button_hover_bg_color'] ) ) {
			$css .= '.dataTables_wrapper .dataTables_paginate .paginate_button:hover, .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover { background-color: ' . esc_attr( $settings['button_hover_bg_color'] ) . ' !important; }';
		}
		
		// Button hover text color
		if ( ! empty( $settings['button_hover_text_color'] ) ) {
			$css .= '.dataTables_wrapper .dataTables_paginate .paginate_button:hover, .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover { color: ' . esc_attr( $settings['button_hover_text_color'] ) . ' !important; }';
		}
		
		// Button border radius
		if ( isset( $settings['button_border_radius'] ) ) {
			$border_radius = absint( $settings['button_border_radius'] );
			$css .= '.dataTables_wrapper .dataTables_paginate .paginate_button, .dataTables_wrapper .dataTables_length select, .dataTables_wrapper .dataTables_filter input { border-radius: ' . $border_radius . 'px !important; }';
		}
		
		// Table box shadow
		if ( ! empty( $settings['table_box_shadow'] ) ) {
			$css .= '.tv-table-container, .tv-table-sidebar { box-shadow: ' . esc_attr( $settings['table_box_shadow'] ) . ' !important; }';
		}
		
		// Table border color
		if ( ! empty( $settings['table_border_color'] ) ) {
			$css .= '.tv-table-container, .tv-table-sidebar { border-color: ' . esc_attr( $settings['table_border_color'] ) . ' !important; }';
		}
		
		// Category background color (2nd to last column - before Website)
		if ( ! empty( $settings['category_bg_color'] ) ) {
			$css .= '#tv-channels-table tbody tr td:nth-last-child(2) { background-color: ' . esc_attr( $settings['category_bg_color'] ) . ' !important; }';
		}
		
		// Pagination text color
		if ( ! empty( $settings['pagination_text_color'] ) ) {
			$css .= '.dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_length label, .dataTables_wrapper .dataTables_filter label { color: ' . esc_attr( $settings['pagination_text_color'] ) . ' !important; }';
		}
		
		// Pagination font family
		if ( ! empty( $settings['pagination_font_family'] ) ) {
			$font_family = esc_attr( $settings['pagination_font_family'] );
			$css .= '.dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_paginate { font-family: ' . $font_family . ' !important; }';
		}
		
		// Pagination font size
		if ( isset( $settings['pagination_font_size'] ) ) {
			$font_size = absint( $settings['pagination_font_size'] );
			$css .= '.dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_paginate { font-size: ' . $font_size . 'px !important; }';
		}
		
		// Apply styles
		if ( ! empty( $css ) ) {
			wp_add_inline_style( 'tv-table-css', $css );
		}
	}
}

