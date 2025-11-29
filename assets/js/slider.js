/**
 * IPTV Slider JavaScript
 *
 * Handles controls (dots and navigation) for CSS-animated slider
 */

(function($) {
	'use strict';
	
	$(document).ready(function() {
		// Check if settings are available
		if (typeof tvSliderSettings === 'undefined') {
			return;
		}
		
		var settings = tvSliderSettings;
		var showControls = settings.showControls !== false;
		
		// Initialize sliders
		$('.tv-slider-container').each(function() {
			var $container = $(this);
			var $slider = $container.find('.tv-slider');
			var $wrapper = $container.find('.tv-slider-wrapper');
			
			if ($wrapper.length === 0) {
				return;
			}
			
			// Get items count (original items, not duplicated)
			var $items = $wrapper.find('.tv-slider-item');
			var itemCount = Math.floor($items.length / 2); // Divide by 2 because items are duplicated
			
			if (itemCount === 0) {
				return;
			}
			
			// Create controls if enabled
			if (showControls) {
				createControls($container, itemCount);
			}
			
			// Get controls
			var $prevBtn = $container.find('.tv-slider-prev');
			var $nextBtn = $container.find('.tv-slider-next');
			var $dots = $container.find('.tv-slider-dots');
			
			// Update dots based on animation progress
			function updateDots() {
				if (!showControls || !$dots.length) return;
				
				// Get current animation progress
				var animation = $wrapper.css('animation');
				var animationName = $wrapper.css('animation-name');
				
				// Calculate current position based on animation
				// This is approximate since CSS animations don't expose exact position
				var currentTime = Date.now();
				var animationDuration = parseFloat($wrapper.css('animation-duration')) * 1000; // Convert to ms
				
				// Simple approach: cycle through dots
				var dotIndex = Math.floor((currentTime / (animationDuration / itemCount)) % itemCount);
				$dots.find('.tv-slider-dot').removeClass('active');
				$dots.find('.tv-slider-dot').eq(dotIndex).addClass('active');
			}
			
			// Create controls (dots and navigation)
			function createControls($container, count) {
				// Add navigation arrows
				var $nav = $('<div class="tv-slider-nav">' +
					'<button class="tv-slider-prev" aria-label="Previous">‹</button>' +
					'<button class="tv-slider-next" aria-label="Next">›</button>' +
					'</div>');
				$container.append($nav);
				
				// Add dots
				var $dotsContainer = $('<div class="tv-slider-dots"></div>');
				for (var i = 0; i < count; i++) {
					$dotsContainer.append('<span class="tv-slider-dot" data-index="' + i + '"></span>');
				}
				$container.append($dotsContainer);
				
				// Update references
				$prevBtn = $container.find('.tv-slider-prev');
				$nextBtn = $container.find('.tv-slider-next');
				$dots = $container.find('.tv-slider-dots');
				
				// Navigation click handlers - pause animation
				$prevBtn.on('click', function(e) {
					e.preventDefault();
					$wrapper.css('animation-play-state', 'paused');
					// Note: CSS animations don't support jumping to specific positions easily
					// This is a limitation of CSS-only animations
				});
				
				$nextBtn.on('click', function(e) {
					e.preventDefault();
					$wrapper.css('animation-play-state', 'paused');
				});
				
				// Dot click handlers
				$dots.find('.tv-slider-dot').on('click', function() {
					$wrapper.css('animation-play-state', 'paused');
					updateDots();
				});
			}
			
			// Update dots periodically
			if (showControls) {
				setInterval(updateDots, 500);
			}
		});
	});
	
})(jQuery);
