/**
 * Admin JavaScript
 *
 * Handles color picker, navigation, and other admin functionality
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
		
		// Settings page navigation
		initSettingsNavigation();
	});
	
	/**
	 * Initialize settings page navigation
	 */
	function initSettingsNavigation() {
		var $navLinks = $('.tv-nav-link');
		var $sections = $('.tv-settings-section');
		
		if ($navLinks.length === 0 || $sections.length === 0) {
			return;
		}
		
		// Handle navigation link clicks
		$navLinks.on('click', function(e) {
			e.preventDefault();
			
			var targetSection = $(this).attr('href');
			var $target = $(targetSection);
			
			if ($target.length) {
				// Update active state
				$navLinks.removeClass('active');
				$(this).addClass('active');
				
				// Smooth scroll to section
				$('html, body').animate({
					scrollTop: $target.offset().top - 100
				}, 500, function() {
					// Highlight section briefly
					$target.css('background-color', '#f0f6fc');
					setTimeout(function() {
						$target.css('background-color', '');
					}, 1000);
				});
				
				// Update URL hash without scrolling
				if (history.pushState) {
					history.pushState(null, null, targetSection);
				}
			}
		});
		
		// Update active nav link on scroll
		$(window).on('scroll', function() {
			updateActiveNavLink();
		});
		
		// Update active nav link on page load
		updateActiveNavLink();
		
		// Handle initial hash in URL
		if (window.location.hash) {
			var $targetSection = $(window.location.hash);
			if ($targetSection.length) {
				setTimeout(function() {
					$('html, body').scrollTop($targetSection.offset().top - 100);
					$navLinks.filter('[href="' + window.location.hash + '"]').addClass('active');
				}, 100);
			}
		}
	}
	
	/**
	 * Update active navigation link based on scroll position
	 */
	function updateActiveNavLink() {
		var $sections = $('.tv-settings-section');
		var $navLinks = $('.tv-nav-link');
		var scrollTop = $(window).scrollTop();
		var windowHeight = $(window).height();
		var currentSection = '';
		
		$sections.each(function() {
			var $section = $(this);
			var sectionTop = $section.offset().top - 150;
			var sectionBottom = sectionTop + $section.outerHeight();
			
			if (scrollTop >= sectionTop && scrollTop < sectionBottom) {
				currentSection = '#' + $section.attr('id');
				return false; // Break loop
			}
		});
		
		if (currentSection) {
			$navLinks.removeClass('active');
			$navLinks.filter('[href="' + currentSection + '"]').addClass('active');
		}
	}
	
})(jQuery);

