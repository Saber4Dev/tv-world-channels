<?php
/**
 * API Handler Class
 *
 * Handles fetching and caching data from iptv-org API
 *
 * @package TV_World_Channels
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TV_API Class
 */
class TV_API {
	
	/**
	 * Instance of this class
	 *
	 * @var TV_API
	 */
	private static $instance = null;
	
	/**
	 * API base URL
	 *
	 * @var string
	 */
	private $api_base = 'https://iptv-org.github.io/api/';
	
	/**
	 * Cache duration in seconds (12 hours)
	 *
	 * @var int
	 */
	private $cache_duration = 43200;
	
	/**
	 * Get instance of this class
	 *
	 * @return TV_API
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
		// Constructor intentionally empty
	}
	
	/**
	 * Fetch channels data
	 *
	 * @return array|false Array of channels or false on error
	 */
	public function get_channels() {
		$transient_key = 'tv_channels_data';
		
		// Try object cache first (for persistent caching)
		$channels = wp_cache_get( $transient_key, 'tv_data' );
		
		if ( false === $channels ) {
			// Fallback to transients
			$channels = get_transient( $transient_key );
			
			if ( false === $channels ) {
				// Use background fetch to avoid blocking
				$channels = $this->fetch_remote_data( 'channels.json' );
				
				if ( false !== $channels && is_array( $channels ) ) {
					// Store only essential data to reduce memory
					$channels = $this->optimize_channels_data( $channels );
					
					// Store in both transient and object cache
					set_transient( $transient_key, $channels, $this->cache_duration );
					wp_cache_set( $transient_key, $channels, 'tv_data', $this->cache_duration );
				}
			} else {
				// Store in object cache for faster access
				wp_cache_set( $transient_key, $channels, 'tv_data', $this->cache_duration );
			}
		}
		
		return $channels;
	}
	
	/**
	 * Optimize channels data by keeping only necessary fields
	 *
	 * @param array $channels Full channels data
	 * @return array Optimized channels data
	 */
	private function optimize_channels_data( $channels ) {
		$optimized = array();
		$essential_fields = array( 'id', 'name', 'country', 'categories', 'website', 'is_nsfw' );
		
		foreach ( $channels as $channel ) {
			if ( ! is_array( $channel ) ) {
				continue;
			}
			
			$optimized_channel = array();
			foreach ( $essential_fields as $field ) {
				if ( isset( $channel[ $field ] ) ) {
					$optimized_channel[ $field ] = $channel[ $field ];
				}
			}
			
			if ( ! empty( $optimized_channel ) ) {
				$optimized[] = $optimized_channel;
			}
		}
		
		return $optimized;
	}
	
	/**
	 * Fetch logos data
	 *
	 * @return array|false Array of logos or false on error
	 */
	public function get_logos() {
		$transient_key = 'tv_logos_data';
		
		// Try object cache first
		$logos = wp_cache_get( $transient_key, 'tv_data' );
		
		if ( false === $logos ) {
			// Fallback to transients
			$logos = get_transient( $transient_key );
			
			if ( false === $logos ) {
				$logos = $this->fetch_remote_data( 'logos.json' );
				
				if ( false !== $logos ) {
					set_transient( $transient_key, $logos, $this->cache_duration );
					wp_cache_set( $transient_key, $logos, 'tv_data', $this->cache_duration );
				}
			} else {
				wp_cache_set( $transient_key, $logos, 'tv_data', $this->cache_duration );
			}
		}
		
		return $logos;
	}
	
	/**
	 * Fetch countries data
	 *
	 * @return array|false Array of countries or false on error
	 */
	public function get_countries() {
		$transient_key = 'tv_countries_data';
		
		// Try object cache first
		$countries = wp_cache_get( $transient_key, 'tv_data' );
		
		if ( false === $countries ) {
			// Fallback to transients
			$countries = get_transient( $transient_key );
			
			if ( false === $countries ) {
				$countries = $this->fetch_remote_data( 'countries.json' );
				
				if ( false !== $countries ) {
					set_transient( $transient_key, $countries, $this->cache_duration );
					wp_cache_set( $transient_key, $countries, 'tv_data', $this->cache_duration );
				}
			} else {
				wp_cache_set( $transient_key, $countries, 'tv_data', $this->cache_duration );
			}
		}
		
		return $countries;
	}
	
	/**
	 * Fetch remote data from API
	 *
	 * @param string $endpoint API endpoint
	 * @return array|false Decoded JSON data or false on error
	 */
	private function fetch_remote_data( $endpoint ) {
		$url = $this->api_base . $endpoint;
		
		$response = wp_remote_get( $url, array(
			'timeout' => 30,
			'sslverify' => true,
		) );
		
		if ( is_wp_error( $response ) ) {
			return false;
		}
		
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		
		// Validate JSON
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return false;
		}
		
		return $data;
	}
	
	/**
	 * Get channels filtered by country
	 *
	 * @param string $country_code ISO alpha-2 country code
	 * @return array Filtered channels
	 */
	public function get_channels_by_country( $country_code ) {
		$country_code = strtoupper( $country_code );
		$transient_key = 'tv_channels_by_country_' . sanitize_key( $country_code );
		$filtered_channels = get_transient( $transient_key );
		
		if ( false === $filtered_channels ) {
			$all_channels = $this->get_channels();
			
			if ( false === $all_channels ) {
				return array();
			}
			
			$filtered_channels = array();
			$country_code_lower = strtolower( $country_code );
			
			foreach ( $all_channels as $channel ) {
				// Filter by country and exclude NSFW
				if ( isset( $channel['country'] ) && 
					 strtolower( $channel['country'] ) === $country_code_lower &&
					 ( ! isset( $channel['is_nsfw'] ) || false === $channel['is_nsfw'] ) ) {
					$filtered_channels[] = $channel;
				}
			}
			
			// Cache filtered results for 12 hours
			if ( ! empty( $filtered_channels ) ) {
				set_transient( $transient_key, $filtered_channels, $this->cache_duration );
			}
		}
		
		return $filtered_channels;
	}
	
	/**
	 * Get logo URL for a channel
	 *
	 * @param string $channel_id Channel ID
	 * @return string|false Logo URL or false if not found
	 */
	public function get_channel_logo( $channel_id ) {
		// Use transient cache for individual logo lookups
		$cache_key = 'tv_logo_' . md5( $channel_id );
		$logo_url = get_transient( $cache_key );
		
		if ( false !== $logo_url ) {
			return $logo_url;
		}
		
		$logos = $this->get_logos();
		
		if ( false === $logos ) {
			return false;
		}
		
		$channel_logos = array();
		
		// Find all logos for this channel (excluding picons)
		foreach ( $logos as $logo ) {
			if ( isset( $logo['channel'] ) && $logo['channel'] === $channel_id ) {
				if ( ! isset( $logo['tag'] ) || 'picons' !== $logo['tag'] ) {
					$channel_logos[] = $logo;
				}
			}
		}
		
		if ( empty( $channel_logos ) ) {
			// Cache negative result for 1 hour
			set_transient( $cache_key, false, 3600 );
			return false;
		}
		
		// Sort by resolution (prefer highest)
		usort( $channel_logos, function( $a, $b ) {
			$res_a = isset( $a['resolution'] ) ? intval( $a['resolution'] ) : 0;
			$res_b = isset( $b['resolution'] ) ? intval( $b['resolution'] ) : 0;
			return $res_b - $res_a;
		} );
		
		// Return URL of highest resolution logo
		$best_logo = $channel_logos[0];
		$logo_url = isset( $best_logo['url'] ) ? $best_logo['url'] : false;
		
		// Cache the result for 12 hours
		if ( false !== $logo_url ) {
			set_transient( $cache_key, $logo_url, $this->cache_duration );
		}
		
		return $logo_url;
	}
	
	/**
	 * Get country name by ISO code
	 *
	 * @param string $iso_code ISO alpha-2 country code
	 * @return string|false Country name or false if not found
	 */
	public function get_country_name( $iso_code ) {
		$countries = $this->get_countries();
		
		if ( false === $countries ) {
			return false;
		}
		
		foreach ( $countries as $country ) {
			if ( isset( $country['code'] ) && strtolower( $country['code'] ) === strtolower( $iso_code ) ) {
				return isset( $country['name'] ) ? $country['name'] : false;
			}
		}
		
		return false;
	}
	
	/**
	 * Get all countries as array for dropdown
	 *
	 * @return array Array of countries [code => name]
	 */
	public function get_countries_list() {
		$countries = $this->get_countries();
		
		if ( false === $countries ) {
			return array();
		}
		
		$list = array();
		
		foreach ( $countries as $country ) {
			if ( isset( $country['code'] ) && isset( $country['name'] ) ) {
				$list[ $country['code'] ] = $country['name'];
			}
		}
		
		// Sort by country name
		asort( $list );
		
		return $list;
	}
	
	/**
	 * Clear all cached data
	 *
	 * @return void
	 */
	public function clear_cache() {
		// Clear transients
		delete_transient( 'tv_channels_data' );
		delete_transient( 'tv_logos_data' );
		delete_transient( 'tv_countries_data' );
		
		// Clear object cache
		wp_cache_delete( 'tv_channels_data', 'tv_data' );
		wp_cache_delete( 'tv_logos_data', 'tv_data' );
		wp_cache_delete( 'tv_countries_data', 'tv_data' );
		
		// Clear country-specific caches
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_tv_channels_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_tv_channels_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_tv_logo_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_tv_logo_%'" );
		
		// Trigger cache cleared action for other plugins
		do_action( 'tv_cache_cleared' );
	}
}

