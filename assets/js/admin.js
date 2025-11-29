/**
 * Admin JavaScript
 *
 * Handles color picker and other admin functionality
 */

(function($) {
	'use strict';
	
	$(document).ready(function() {
		// Initialize color picker
		if ($.fn.wpColorPicker) {
			$('.tv-color-picker').wpColorPicker({
				change: function(event, ui) {
					// Color picker change handler
				},
				clear: function() {
					// Clear color handler
				}
			});
		}
	});
	
})(jQuery);

