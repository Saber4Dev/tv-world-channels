<?php
/**
 * Optimizer Class
 *
 * Handles performance optimizations and cache compatibility
 *
 * @package TV_World_Channels
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TV_Optimizer Class
 */
class TV_Optimizer {
	
	/**
	 * Instance of this class
	 *
	 * @var TV_Optimizer
	 */
	private static $instance = null;
	
	/**
	 * Get instance of this class
	 *
	 * @return TV_Optimizer
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
	 * Initialize optimizations
	 */
	private function init() {
		// Add cache control headers for AJAX
		add_action( 'wp_ajax_tv_table_data', array( $this, 'add_cache_headers' ), 1 );
		add_action( 'wp_ajax_nopriv_tv_table_data', array( $this, 'add_cache_headers' ), 1 );
		add_action( 'wp_ajax_tv_slider', array( $this, 'add_cache_headers' ), 1 );
		add_action( 'wp_ajax_nopriv_tv_slider', array( $this, 'add_cache_headers' ), 1 );
		
		// Optimize image loading
		add_filter( 'wp_get_attachment_image_attributes', array( $this, 'optimize_image_attributes' ), 10, 3 );
		
		// Add resource hints
		add_action( 'wp_head', array( $this, 'add_resource_hints' ), 1 );
	}
	
	/**
	 * Add cache control headers for AJAX requests
	 */
	public function add_cache_headers() {
		// Set no-cache headers for dynamic AJAX content
		header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
		header( 'Pragma: no-cache' );
		header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
		
		// Disable page caching
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
	}
	
	/**
	 * Optimize image attributes
	 *
	 * @param array $attr Image attributes
	 * @param WP_Post $attachment Attachment post object
	 * @param string|array $size Image size
	 * @return array Modified attributes
	 */
	public function optimize_image_attributes( $attr, $attachment, $size ) {
		// Add loading="lazy" if not already present
		if ( ! isset( $attr['loading'] ) ) {
			$attr['loading'] = 'lazy';
		}
		
		// Add decoding="async" for better performance
		if ( ! isset( $attr['decoding'] ) ) {
			$attr['decoding'] = 'async';
		}
		
		return $attr;
	}
	
	/**
	 * Add resource hints for better performance
	 */
	public function add_resource_hints() {
		// DNS prefetch for external resources
		echo '<link rel="dns-prefetch" href="//iptv-org.github.io">' . "\n";
		echo '<link rel="dns-prefetch" href="//cdn.datatables.net">' . "\n";
		
		// Preconnect for faster loading
		echo '<link rel="preconnect" href="https://iptv-org.github.io" crossorigin>' . "\n";
	}
}

