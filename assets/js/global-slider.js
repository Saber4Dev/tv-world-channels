/**
 * IPTV Global Slider JavaScript
 *
 * Handles country selection and AJAX slider loading
 */

(function($) {
	'use strict';
	
	$(document).ready(function() {
		var countrySelect = $('#tv-global-country-select');
		var sliderContainer = $('#tv-global-slider-container');
		var loadingIndicator = $('.tv-global-slider-loading');
		
		if (countrySelect.length === 0 || sliderContainer.length === 0) {
			return;
		}
		
		// Get settings from select element
		var showLogos = countrySelect.data('logos') || 'on';
		var showNames = countrySelect.data('names') || 'show';
		
		// Handle country change
		countrySelect.on('change', function() {
			var selectedCountry = $(this).val();
			
			if (!selectedCountry) {
				return;
			}
			
			// Show loading indicator
			sliderContainer.hide();
			loadingIndicator.show();
			
			// Make AJAX request
			$.ajax({
				url: tvGlobalSlider.ajaxUrl,
				type: 'POST',
				data: {
					action: 'tv_slider',
					nonce: tvGlobalSlider.nonce,
					country: selectedCountry,
					logos: showLogos,
					names: showNames
				},
				success: function(response) {
					loadingIndicator.hide();
					
					if (response.success && response.data && response.data.html) {
						sliderContainer.html(response.data.html).fadeIn(300);
					} else {
						var errorMsg = response.data && response.data.message 
							? response.data.message 
							: 'Failed to load channels. Please try again.';
						sliderContainer.html('<p style="text-align:center;color:#d63638;padding:20px;">' + escapeHtml(errorMsg) + '</p>').fadeIn(300);
					}
				},
				error: function(xhr, status, error) {
					loadingIndicator.hide();
					var errorMsg = 'An error occurred while loading channels. Please try again.';
					sliderContainer.html('<p style="text-align:center;color:#d63638;padding:20px;">' + escapeHtml(errorMsg) + '</p>').fadeIn(300);
					console.error('IPTV Global Slider AJAX Error:', error);
				}
			});
		});
		
		// Helper function to escape HTML
		function escapeHtml(text) {
			if (!text) return '';
			var map = {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;'
			};
			return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
		}
	});
	
})(jQuery);

