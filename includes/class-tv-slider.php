<?php
/**
 * Slider Shortcode Class
 *
 * Handles [tv] shortcode for displaying channel sliders
 *
 * @package TV_World_Channels
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TV_Slider Class
 */
class TV_Slider {
	
	/**
	 * Instance of this class
	 *
	 * @var TV_Slider
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
	 * Get instance of this class
	 *
	 * @return TV_Slider
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
		add_shortcode( 'tv', array( $this, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
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
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'tv' ) ) {
			return true;
		}
		
		// Check widgets
		if ( is_active_widget( false, false, 'text' ) ) {
			$widgets = get_option( 'widget_text' );
			if ( is_array( $widgets ) ) {
				foreach ( $widgets as $widget ) {
					if ( isset( $widget['text'] ) && has_shortcode( $widget['text'], 'tv' ) ) {
						return true;
					}
				}
			}
		}
		
		// Always return true to be safe (shortcode might be in template)
		return true;
	}
	
	/**
	 * Add preload for critical CSS
	 */
	public function add_preload_css() {
		echo '<link rel="preload" href="' . esc_url( TV_PLUGIN_URL . 'assets/css/slider.css' ) . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">';
		echo '<noscript><link rel="stylesheet" href="' . esc_url( TV_PLUGIN_URL . 'assets/css/slider.css' ) . '"></noscript>';
	}
	
	/**
	 * Add defer attribute to slider script
	 *
	 * @param string $tag Script tag
	 * @param string $handle Script handle
	 * @return string Modified script tag
	 */
	public function defer_slider_script( $tag, $handle ) {
		if ( 'tv-slider-js' === $handle ) {
			return str_replace( ' src', ' defer src', $tag );
		}
		return $tag;
	}
	
	/**
	 * Enqueue slider assets
	 */
	public function enqueue_assets() {
		// Only enqueue if shortcode is used
		if ( ! $this->is_shortcode_used() ) {
			return;
		}
		
		// Enqueue CSS with media query for better loading
		wp_enqueue_style(
			'tv-slider-css',
			TV_PLUGIN_URL . 'assets/css/slider.css',
			array(),
			TV_PLUGIN_VERSION,
			'all'
		);
		
		// Add preload for critical CSS
		add_action( 'wp_head', array( $this, 'add_preload_css' ), 1 );
		
		// Get settings
		$settings = $this->settings->get_settings();
		
		// Enqueue slider JS only if controls are enabled (CSS animation doesn't need JS)
		$show_controls = isset( $settings['show_slider_controls'] ) && 1 === $settings['show_slider_controls'];
		if ( $show_controls ) {
			wp_enqueue_script(
				'tv-slider-js',
				TV_PLUGIN_URL . 'assets/js/slider.js',
				array( 'jquery' ),
				TV_PLUGIN_VERSION,
				true
			);
			
			// Localize script with settings
			wp_localize_script( 'tv-slider-js', 'tvSliderSettings', array(
				'showControls' => true,
			) );
		}
		
		// Add inline styles for dynamic settings
		$logo_background = isset( $settings['logo_background'] ) ? sanitize_text_field( $settings['logo_background'] ) : '';
		$logo_padding = isset( $settings['logo_padding'] ) ? absint( $settings['logo_padding'] ) : 10;
		$logo_margin = isset( $settings['logo_margin'] ) ? absint( $settings['logo_margin'] ) : 0;
		$logo_gap = isset( $settings['logo_gap'] ) ? absint( $settings['logo_gap'] ) : 20;
		$hide_scrollbar = isset( $settings['hide_scrollbar'] ) && 1 === $settings['hide_scrollbar'];
		
		$custom_css = '';
		
		// Logo width
		$logo_width = isset( $settings['logo_width'] ) ? absint( $settings['logo_width'] ) : 120;
		$item_min_width = $logo_width + ( $logo_padding * 2 ) + ( $logo_margin * 2 ) + 20; // Add padding for item padding
		
		$custom_css .= '.tv-slider-logo { width: ' . absint( $logo_width ) . 'px !important; min-width: ' . absint( $logo_width ) . 'px !important; max-width: ' . absint( $logo_width ) . 'px !important; }';
		$custom_css .= '.tv-slider-logo img { max-width: ' . absint( $logo_width ) . 'px !important; width: auto !important; height: auto !important; }';
		$custom_css .= '.tv-slider-item { min-width: ' . absint( $item_min_width ) . 'px !important; }';
		
		// Logo background
		if ( ! empty( $logo_background ) ) {
			$custom_css .= '.tv-slider-logo { background: ' . esc_attr( $logo_background ) . ' !important; }';
		} else {
			$custom_css .= '.tv-slider-logo { background: transparent !important; }';
		}
		
		// Logo padding
		$custom_css .= '.tv-slider-logo { padding: ' . absint( $logo_padding ) . 'px !important; }';
		
		// Logo margin
		if ( $logo_margin > 0 ) {
			$custom_css .= '.tv-slider-logo { margin: ' . absint( $logo_margin ) . 'px !important; }';
		} else {
			$custom_css .= '.tv-slider-logo { margin: 0 !important; }';
		}
		
		// Gap between logos
		$custom_css .= '.tv-slider { gap: ' . absint( $logo_gap ) . 'px !important; }';
		
		// Always hide scrollbar (CSS animation doesn't need it)
		$custom_css .= '.tv-slider { scrollbar-width: none !important; -ms-overflow-style: none !important; overflow: hidden !important; }';
		$custom_css .= '.tv-slider::-webkit-scrollbar { display: none !important; width: 0 !important; height: 0 !important; }';
		
		if ( ! empty( $custom_css ) ) {
			wp_add_inline_style( 'tv-slider-css', $custom_css );
		}
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
			'country' => 'france',
			'category' => '',
			'logos' => 'on',
			'names' => 'show',
			'sort' => 'default',
		), $atts, 'tv' );
		
		// Sanitize attributes
		$country = sanitize_text_field( $atts['country'] );
		$category = sanitize_text_field( $atts['category'] );
		$show_logos = ( 'on' === strtolower( $atts['logos'] ) );
		$show_names = ( 'show' === strtolower( $atts['names'] ) );
		$sort = sanitize_text_field( $atts['sort'] );
		
		// Get country code (handle both ISO code and country name)
		$country_code = $this->normalize_country_code( $country );
		
		if ( false === $country_code ) {
			return '<p>' . esc_html__( 'Invalid country specified.', 'tv-world-channels' ) . '</p>';
		}
		
		// Get channels for country
		$channels = $this->api->get_channels_by_country( $country_code );
		
		if ( empty( $channels ) ) {
			return '<p>' . esc_html__( 'No channels found for this country.', 'tv-world-channels' ) . '</p>';
		}
		
		// Filter by category if specified
		if ( ! empty( $category ) ) {
			$channels = $this->filter_channels_by_category( $channels, $category );
			if ( empty( $channels ) ) {
				return '<p>' . esc_html__( 'No channels found for this category.', 'tv-world-channels' ) . '</p>';
			}
		}
		
		// Sort channels if requested
		if ( 'popular' === strtolower( $sort ) ) {
			$channels = $this->sort_channels_by_popularity( $channels, $country_code );
		} elseif ( 'name' === strtolower( $sort ) ) {
			$channels = $this->sort_channels_by_name( $channels );
		}
		
		// Generate slider HTML with country name for SEO
		$country_name = $this->api->get_country_name( $country_code );
		if ( false === $country_name ) {
			$country_name = strtoupper( $country_code );
		}
		
		return $this->generate_slider_html( $channels, $show_logos, $show_names, $country_name, $category );
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
	
	/**
	 * Sort channels by popularity
	 *
	 * @param array $channels Array of channel data
	 * @param string $country_code Country code
	 * @return array Sorted channels
	 */
	private function sort_channels_by_popularity( $channels, $country_code ) {
		// Define popular channel patterns/keywords by country
		$popular_patterns = $this->get_popular_channel_patterns( $country_code );
		
		// Separate popular and regular channels
		$popular_channels = array();
		$regular_channels = array();
		
		foreach ( $channels as $channel ) {
			$channel_name = isset( $channel['name'] ) ? strtolower( $channel['name'] ) : '';
			$channel_id = isset( $channel['id'] ) ? strtolower( $channel['id'] ) : '';
			$is_popular = false;
			
			// Check if channel matches popular patterns
			foreach ( $popular_patterns as $pattern ) {
				if ( false !== strpos( $channel_name, strtolower( $pattern ) ) || 
					 false !== strpos( $channel_id, strtolower( $pattern ) ) ) {
					$is_popular = true;
					break;
				}
			}
			
			// Also check for major categories that indicate popularity
			if ( ! $is_popular && isset( $channel['categories'] ) && is_array( $channel['categories'] ) ) {
				$popular_categories = array( 'general', 'news', 'sports', 'entertainment' );
				foreach ( $channel['categories'] as $category ) {
					if ( in_array( strtolower( $category ), $popular_categories, true ) ) {
						$is_popular = true;
						break;
					}
				}
			}
			
			if ( $is_popular ) {
				$popular_channels[] = $channel;
			} else {
				$regular_channels[] = $channel;
			}
		}
		
		// Sort popular channels by name, then regular channels by name
		usort( $popular_channels, array( $this, 'compare_channel_names' ) );
		usort( $regular_channels, array( $this, 'compare_channel_names' ) );
		
		// Combine: popular first, then regular
		return array_merge( $popular_channels, $regular_channels );
	}
	
	/**
	 * Sort channels by name alphabetically
	 *
	 * @param array $channels Array of channel data
	 * @return array Sorted channels
	 */
	private function sort_channels_by_name( $channels ) {
		usort( $channels, array( $this, 'compare_channel_names' ) );
		return $channels;
	}
	
	/**
	 * Compare channel names for sorting
	 *
	 * @param array $a First channel
	 * @param array $b Second channel
	 * @return int Comparison result
	 */
	private function compare_channel_names( $a, $b ) {
		$name_a = isset( $a['name'] ) ? $a['name'] : '';
		$name_b = isset( $b['name'] ) ? $b['name'] : '';
		return strcasecmp( $name_a, $name_b );
	}
	
	/**
	 * Get popular channel patterns by country
	 *
	 * @param string $country_code Country code
	 * @return array Array of popular channel patterns
	 */
	private function get_popular_channel_patterns( $country_code ) {
		$country_code = strtolower( $country_code );
		
		// Define popular channels by country (common major networks)
		$popular_by_country = array(
			'fr' => array( 'tf1', 'france 2', 'france 3', 'canal+', 'm6', 'france 5', 'arte', 'c8', 'w9', 'tmc', 'bfm', 'lci', 'france info', 'france 24' ),
			'us' => array( 'abc', 'nbc', 'cbs', 'fox', 'cnn', 'espn', 'disney', 'hbo', 'hulu', 'netflix', 'discovery', 'national geographic', 'history', 'comedy central' ),
			'gb' => array( 'bbc', 'itv', 'channel 4', 'channel 5', 'sky', 'sky news', 'sky sports', 'discovery', 'national geographic' ),
			'es' => array( 'tve', 'antena 3', 'telecinco', 'la sexta', 'cuatro', 'canal+', 'mega', 'atresmedia' ),
			'de' => array( 'ard', 'zdf', 'rtl', 'sat.1', 'prosieben', 'kabel eins', 'vox', 'n24', 'welt' ),
			'it' => array( 'rai', 'mediaset', 'canale 5', 'italia 1', 'rete 4', 'la7', 'sky', 'sky tg24' ),
			'br' => array( 'globo', 'record', 'sbt', 'band', 'rede tv', 'cnn brasil', 'bandnews', 'sportv' ),
			'mx' => array( 'televisa', 'tv azteca', 'canal 11', 'imagen tv', 'multimedios', 'adn 40' ),
			'ar' => array( 'telefe', 'el trece', 'america', 'tn', 'cnn argentina', 'fox sports' ),
			'ca' => array( 'cbc', 'ctv', 'global', 'citytv', 'tvo', 'télé-québec', 'radio-canada' ),
			'au' => array( 'abc', 'seven', 'nine', 'ten', 'sbs', 'sky news', 'fox sports' ),
			'in' => array( 'dd national', 'star plus', 'sony', 'zee', 'colors', 'sun tv', 'asianet' ),
			'jp' => array( 'nhk', 'fuji tv', 'tbs', 'tv asahi', 'tv tokyo', 'nippon tv' ),
			'kr' => array( 'kbs', 'mbc', 'sbs', 'jtbc', 'tvn', 'channel a' ),
			'cn' => array( 'cctv', 'hunan tv', 'zhejiang tv', 'jiangsu tv', 'dongfang tv' ),
			'ru' => array( 'pervyi kanal', 'rossiya 1', 'ntv', 'tnt', 'ren tv', 'sts' ),
			'pl' => array( 'tvp', 'polsat', 'tvn', 'tvp info', 'polsat news' ),
			'nl' => array( 'npo', 'rtl', 'sbs', 'net5', 'veronica' ),
			'be' => array( 'rtbf', 'vtm', 'een', 'canvas', 'la une' ),
			'ch' => array( 'srf', 'rts', 'rsi', 'swissinfo' ),
			'pt' => array( 'rtp', 'sic', 'tvi', 'canais', 'porto canal' ),
			'gr' => array( 'ert', 'mega', 'ant1', 'alpha', 'star' ),
			'tr' => array( 'trt', 'show tv', 'atv', 'star tv', 'fox turkiye', 'cnn turk' ),
			'ae' => array( 'dubai tv', 'abu dhabi tv', 'mbc', 'al jazeera', 'sky news arabia' ),
			'sa' => array( 'mbc', 'rotana', 'al arabiya', 'al jazeera', 'saudi tv' ),
			'eg' => array( 'mbc', 'rotana', 'al jazeera', 'cbc', 'dmc' ),
		);
		
		// Return patterns for the country, or empty array if not found
		return isset( $popular_by_country[ $country_code ] ) ? $popular_by_country[ $country_code ] : array();
	}
	
	/**
	 * Filter channels by category
	 *
	 * @param array $channels Array of channel data
	 * @param string $category Category name to filter by
	 * @return array Filtered channels
	 */
	private function filter_channels_by_category( $channels, $category ) {
		$category = strtolower( trim( $category ) );
		$filtered = array();
		
		foreach ( $channels as $channel ) {
			if ( isset( $channel['categories'] ) && is_array( $channel['categories'] ) ) {
				foreach ( $channel['categories'] as $channel_category ) {
					if ( strtolower( trim( $channel_category ) ) === $category ) {
						$filtered[] = $channel;
						break;
					}
				}
			}
		}
		
		return $filtered;
	}
	
	/**
	 * Generate slider HTML
	 *
	 * @param array $channels Array of channel data
	 * @param bool $show_logos Whether to show logos
	 * @param bool $show_names Whether to show names
	 * @param string $country_name Country name for SEO heading
	 * @param string $category Category name for SEO heading
	 * @return string HTML output
	 */
	public function generate_slider_html( $channels, $show_logos = true, $show_names = true, $country_name = '', $category = '' ) {
		// Get settings
		$settings = $this->settings->get_settings();
		$slider_speed = isset( $settings['slider_speed'] ) ? absint( $settings['slider_speed'] ) : 3000;
		$item_count = count( $channels );
		
		// Calculate animation duration based on speed setting
		// speed is milliseconds per item, so for all items: speed * item_count
		// Convert to seconds for CSS animation
		$animation_duration = ( $slider_speed * $item_count ) / 1000;
		
		$output = '<div class="tv-slider-container">';
		
		// Add SEO heading for country/category
		if ( ! empty( $country_name ) || ! empty( $category ) ) {
			$heading_text = '';
			if ( ! empty( $category ) && ! empty( $country_name ) ) {
				$heading_text = ucfirst( $category ) . ' TV Channels in ' . esc_html( $country_name );
			} elseif ( ! empty( $country_name ) ) {
				$heading_text = 'TV Channels in ' . esc_html( $country_name );
			} elseif ( ! empty( $category ) ) {
				$heading_text = ucfirst( $category ) . ' TV Channels';
			}
			
			if ( ! empty( $heading_text ) ) {
				$output .= '<h3 class="tv-slider-heading">' . esc_html( $heading_text ) . '</h3>';
			}
		}
		
		$output .= '<div class="tv-slider">';
		$output .= '<div class="tv-slider-wrapper" style="animation-duration: ' . esc_attr( $animation_duration ) . 's;">';
		
		// Duplicate items for seamless loop
		$items_html = '';
		foreach ( $channels as $channel ) {
			$channel_id = isset( $channel['id'] ) ? $channel['id'] : '';
			$channel_name = isset( $channel['name'] ) ? $channel['name'] : '';
			
			if ( empty( $channel_id ) ) {
				continue;
			}
			
			$items_html .= '<div class="tv-slider-item">';
			
			// Logo
			if ( $show_logos ) {
				$logo_url = $this->api->get_channel_logo( $channel_id );
				
				if ( $logo_url ) {
					$items_html .= '<div class="tv-slider-logo">';
					// Use lazy loading and add data-src for better performance
					$items_html .= '<img src="' . esc_url( $logo_url ) . '" alt="' . esc_attr( $channel_name ) . '" loading="lazy" decoding="async" />';
					$items_html .= '</div>';
				}
			}
			
			// Name with h3 for SEO
			if ( $show_names && ! empty( $channel_name ) ) {
				$items_html .= '<h3 class="tv-slider-name">' . esc_html( $channel_name ) . '</h3>';
			}
			
			$items_html .= '</div>';
		}
		
		// Add items twice for seamless loop
		$output .= $items_html;
		$output .= $items_html;
		
		$output .= '</div>'; // .tv-slider-wrapper
		$output .= '</div>'; // .tv-slider
		$output .= '</div>'; // .tv-slider-container
		
		return $output;
	}
}

