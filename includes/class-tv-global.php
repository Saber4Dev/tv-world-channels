<?php
/**
 * Global Slider Class
 *
 * Handles [tv_global_slider] shortcode with country dropdown and AJAX
 *
 * @package TV_World_Channels
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TV_Global_Slider Class
 */
class TV_Global_Slider {
	
	/**
	 * Instance of this class
	 *
	 * @var TV_Global_Slider
	 */
	private static $instance = null;
	
	/**
	 * API instance
	 *
	 * @var TV_API
	 */
	private $api;
	
	/**
	 * Slider instance
	 *
	 * @var TV_Slider
	 */
	private $slider;
	
	/**
	 * Get instance of this class
	 *
	 * @return TV_Global_Slider
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
		$this->slider = TV_Slider::get_instance();
		$this->init();
	}
	
	/**
	 * Initialize hooks
	 */
	private function init() {
		add_shortcode( 'tv_global_slider', array( $this, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		
		// AJAX handlers
		add_action( 'wp_ajax_tv_slider', array( $this, 'ajax_get_slider' ) );
		add_action( 'wp_ajax_nopriv_tv_slider', array( $this, 'ajax_get_slider' ) );
	}
	
	/**
	 * Enqueue global slider assets
	 */
	public function enqueue_assets() {
		wp_enqueue_script(
			'tv-global-slider-js',
			TV_PLUGIN_URL . 'assets/js/global-slider.js',
			array( 'jquery' ),
			TV_PLUGIN_VERSION,
			true
		);
		
		// Localize script
		wp_localize_script( 'tv-global-slider-js', 'tvGlobalSlider', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'tv_slider_nonce' ),
		) );
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
			'logos' => 'on',
			'names' => 'show',
			'default_country' => 'france',
		), $atts, 'tv_global_slider' );
		
		// Sanitize attributes
		$show_logos = ( 'on' === strtolower( $atts['logos'] ) );
		$show_names = ( 'show' === strtolower( $atts['names'] ) );
		$default_country = sanitize_text_field( $atts['default_country'] );
		
		// Get countries list
		$countries = $this->api->get_countries_list();
		
		// Normalize default country
		$default_country_code = $this->normalize_country_code( $default_country );
		
		// Get initial channels
		$channels = array();
		if ( false !== $default_country_code ) {
			$channels = $this->api->get_channels_by_country( $default_country_code );
		}
		
		// Generate initial slider HTML
		$initial_slider = '';
		if ( ! empty( $channels ) ) {
			$initial_slider = $this->slider->generate_slider_html( $channels, $show_logos, $show_names );
		}
		
		// Build output
		$output = '<div class="tv-global-slider-wrapper">';
		
		// Country dropdown
		$output .= '<div class="tv-global-slider-controls">';
		$output .= '<label for="tv-global-country-select">' . esc_html__( 'Select Country:', 'tv-world-channels' ) . '</label>';
		$output .= '<select id="tv-global-country-select" data-logos="' . esc_attr( $atts['logos'] ) . '" data-names="' . esc_attr( $atts['names'] ) . '">';
		
		foreach ( $countries as $code => $name ) {
			$selected = ( strtolower( $code ) === strtolower( $default_country_code ) ) ? ' selected' : '';
			$output .= '<option value="' . esc_attr( strtolower( $code ) ) . '"' . $selected . '>' . esc_html( $name ) . '</option>';
		}
		
		$output .= '</select>';
		$output .= '</div>';
		
		// Slider container
		$output .= '<div class="tv-global-slider-container" id="tv-global-slider-container">';
		$output .= $initial_slider;
		$output .= '</div>';
		
		// Loading indicator
		$output .= '<div class="tv-global-slider-loading" style="display:none;">';
		$output .= esc_html__( 'Loading channels...', 'tv-world-channels' );
		$output .= '</div>';
		
		$output .= '</div>';
		
		return $output;
	}
	
	/**
	 * AJAX handler to get slider HTML
	 */
	public function ajax_get_slider() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tv_slider_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'tv-world-channels' ) ) );
		}
		
		// Get country code
		$country = isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : '';
		$show_logos = isset( $_POST['logos'] ) ? ( 'on' === sanitize_text_field( wp_unslash( $_POST['logos'] ) ) ) : true;
		$show_names = isset( $_POST['names'] ) ? ( 'show' === sanitize_text_field( wp_unslash( $_POST['names'] ) ) ) : true;
		
		if ( empty( $country ) ) {
			wp_send_json_error( array( 'message' => __( 'Country not specified.', 'tv-world-channels' ) ) );
		}
		
		// Normalize country code
		$country_code = $this->normalize_country_code( $country );
		
		if ( false === $country_code ) {
			wp_send_json_error( array( 'message' => __( 'Invalid country specified.', 'tv-world-channels' ) ) );
		}
		
		// Get channels
		$channels = $this->api->get_channels_by_country( $country_code );
		
		if ( empty( $channels ) ) {
			wp_send_json_error( array( 'message' => __( 'No channels found for this country.', 'tv-world-channels' ) ) );
		}
		
		// Generate slider HTML
		$slider_html = $this->slider->generate_slider_html( $channels, $show_logos, $show_names );
		
		wp_send_json_success( array( 'html' => $slider_html ) );
	}
	
	/**
	 * Normalize country code
	 *
	 * @param string $country Country name or ISO code
	 * @return string|false ISO code or false
	 */
	private function normalize_country_code( $country ) {
		$country = strtolower( trim( $country ) );
		
		// If it's already a 2-letter code, return it
		if ( strlen( $country ) === 2 ) {
			return strtoupper( $country );
		}
		
		// Try to find by country name
		$countries = $this->api->get_countries_list();
		
		foreach ( $countries as $code => $name ) {
			if ( strtolower( $name ) === $country || strtolower( $code ) === $country ) {
				return strtoupper( $code );
			}
		}
		
		// If not found, assume it's a 2-letter code anyway
		return strtoupper( $country );
	}
}

