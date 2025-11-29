/**
 * TV Table JavaScript
 *
 * Handles TV player-style layout with AJAX data loading
 */

(function($) {
	'use strict';
	
	$(document).ready(function() {
		// Get data from either tvTableData (localized) or data attributes (fallback)
		var $wrapper = $('.tv-table-wrapper');
		
		if ($wrapper.length === 0) {
			console.error('TV Table: Table wrapper not found.');
			return;
		}
		
		// Get settings from data attributes or localized script
		var showLogos = true;
		var rowsPerPage = 25;
		var enableCountryFlags = false;
		var ajaxUrl = '';
		var nonce = '';
		
		if (typeof tvTableData !== 'undefined') {
			// Use localized data
			showLogos = tvTableData.showLogos !== false;
			rowsPerPage = tvTableData.rowsPerPage || 25;
			enableCountryFlags = tvTableData.enableCountryFlags !== false;
			ajaxUrl = tvTableData.ajaxUrl;
			nonce = tvTableData.nonce;
		} else {
			// Fallback to data attributes
			showLogos = $wrapper.data('show-logos') === 1 || $wrapper.data('show-logos') === '1';
			rowsPerPage = parseInt($wrapper.data('rows-per-page')) || 25;
			enableCountryFlags = $wrapper.data('enable-flags') === 1 || $wrapper.data('enable-flags') === '1';
			ajaxUrl = $wrapper.data('ajax-url') || '';
			nonce = $wrapper.data('nonce') || '';
			
			if (!ajaxUrl || !nonce) {
				console.error('TV Table: Missing AJAX URL or nonce. Please refresh the page.');
				$('.tv-table-loading').html('<div class="tv-table-empty">Error: Missing configuration. Please refresh the page.</div>');
				return;
			}
		}
		
		var currentCountry = ''; // Empty = Global
		var table = null;
		var tableData = [];
		var countries = {};
		var dataLoaded = false;
		
		// Load data via AJAX
		function loadTableData() {
			if (dataLoaded) {
				return;
			}
			
			$('.tv-table-loading').show();
			
			// Get filter parameters from tvTableData
			var country = typeof tvTableData !== 'undefined' && tvTableData.country ? tvTableData.country : '';
			var category = typeof tvTableData !== 'undefined' && tvTableData.category ? tvTableData.category : '';
			var sort = typeof tvTableData !== 'undefined' && tvTableData.sort ? tvTableData.sort : 'default';
			
			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				data: {
					action: 'tv_table_data',
					nonce: nonce,
					country: country,
					category: category,
					sort: sort
				},
				timeout: 30000, // 30 second timeout
				success: function(response) {
					if (response.success && response.data) {
						tableData = response.data.data || [];
						countries = response.data.countries || {};
						
						if (tableData.length === 0) {
							$('.tv-table-loading').html('<div class="tv-table-empty">No channels found. Please try refreshing the page.</div>');
							return;
						}
						
						buildCountryList();
						filterAndDisplayChannels();
						dataLoaded = true;
					} else {
						var errorMsg = response.data && response.data.message ? response.data.message : 'Failed to load data';
						$('.tv-table-loading').html('<div class="tv-table-empty">' + escapeHtml(errorMsg) + '</div>');
					}
				},
				error: function(xhr, status, error) {
					console.error('TV Table AJAX Error:', status, error);
					console.error('Response:', xhr.responseText);
					console.error('Status Code:', xhr.status);
					var errorMsg = 'Error loading channels data. ';
					if (status === 'timeout') {
						errorMsg += 'Request timed out. Please try again.';
					} else if (xhr.status === 0) {
						errorMsg += 'Network error. Please check your connection.';
					} else if (xhr.status === 403) {
						errorMsg += 'Access denied. Please refresh the page.';
					} else if (xhr.status === 500) {
						errorMsg += 'Server error. Please try again later.';
					} else {
						errorMsg += 'Status: ' + status + ' (Code: ' + xhr.status + '). Please refresh the page.';
					}
					$('.tv-table-loading').html('<div class="tv-table-empty">' + errorMsg + '</div>');
				},
				complete: function() {
					$('.tv-table-loading').hide();
				}
			});
		}
		
		// Build country list for sidebar
		function buildCountryList() {
			var $countryList = $('.tv-country-list');
			var uniqueCountries = {};
			
			// Get unique countries from data
			tableData.forEach(function(row) {
				if (row.country && row.countryCode) {
					uniqueCountries[row.countryCode] = row.country;
				}
			});
			
			// Sort countries by name
			var sortedCountries = Object.keys(uniqueCountries).sort(function(a, b) {
				return uniqueCountries[a].localeCompare(uniqueCountries[b]);
			});
			
			// Add countries to sidebar (after Global)
			sortedCountries.forEach(function(code) {
				var countryName = uniqueCountries[code];
				var flagEmoji = enableCountryFlags ? getCountryFlag(code) + ' ' : '';
				var $item = $('<li class="tv-country-item"></li>');
				var $link = $('<a href="#" class="tv-country-link" data-country="' + escapeHtml(code) + '">' + flagEmoji + escapeHtml(countryName) + '</a>');
				
				$link.on('click', function(e) {
					e.preventDefault();
					selectCountry(code);
				});
				
				$item.append($link);
				$countryList.append($item);
			});
		}
		
		// Select country
		function selectCountry(countryCode) {
			currentCountry = countryCode || '';
			
			// Update active state
			$('.tv-country-link').removeClass('active');
			if (countryCode) {
				$('.tv-country-link[data-country="' + escapeHtml(countryCode) + '"]').addClass('active');
			} else {
				$('.tv-country-link.global').addClass('active');
			}
			
			// Filter and reload table
			filterAndDisplayChannels();
		}
		
		// Filter channels by country
		function filterAndDisplayChannels() {
			var filteredData = tableData;
			
			// Filter by country if not Global
			if (currentCountry) {
				filteredData = tableData.filter(function(row) {
					return row.countryCode === currentCountry;
				});
			}
			
			if (filteredData.length === 0) {
				$('.tv-table-loading').show().html('<div class="tv-table-empty">No channels found for this country.</div>');
				$('#tv-channels-table').hide();
				return;
			}
			
			// Prepare table rows
			var tableRows = prepareTableRows(filteredData);
			
			// Destroy existing table if it exists
			if (table && $.fn.DataTable.isDataTable('#tv-channels-table')) {
				table.destroy();
				$('#tv-channels-table tbody').empty();
			}
			
			// Hide loading, show table
			$('.tv-table-loading').hide();
			$('#tv-channels-table').show();
			
			// Initialize DataTable
			table = $('#tv-channels-table').DataTable({
				data: tableRows,
				columns: getColumns(),
				pageLength: rowsPerPage,
				lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
				order: [[showLogos ? 2 : 1, 'asc']], // Sort by channel name
				responsive: true,
				scrollX: true,
				deferRender: true, // Defer rendering for performance
				scrollY: '600px',
				scrollCollapse: true,
				language: {
					search: '',
					searchPlaceholder: 'Search channels...',
					lengthMenu: 'Show _MENU_ channels',
					info: 'Showing _START_ to _END_ of _TOTAL_ channels',
					infoEmpty: 'No channels to show',
					infoFiltered: '(filtered from _MAX_ total channels)',
					zeroRecords: 'No matching channels found',
					paginate: {
						first: 'First',
						last: 'Last',
						next: 'Next',
						previous: 'Previous'
					}
				}
			});
			
			// Update search
			var searchInput = $('#tv-table-search-input');
			searchInput.off('keyup').on('keyup', function() {
				table.search(this.value).draw();
			});
		}
		
		// Prepare table rows
		function prepareTableRows(data) {
			var rows = [];
			
			data.forEach(function(row) {
				var rowData = [];
				
				// Country (with optional flag)
				var countryHtml = '';
				if (enableCountryFlags && row.countryCode) {
					var flagEmoji = getCountryFlag(row.countryCode);
					countryHtml = flagEmoji + ' ' + escapeHtml(row.country || '');
				} else {
					countryHtml = escapeHtml(row.country || '');
				}
				rowData.push(countryHtml);
				
				// Logo (if enabled)
				if (showLogos) {
					var logoHtml = '';
					if (row.logo) {
						logoHtml = '<div class="tv-table-logo"><img src="' + escapeHtml(row.logo) + '" alt="' + escapeHtml(row.name || '') + '" loading="lazy" decoding="async" /></div>';
					} else {
						logoHtml = '<div class="tv-table-logo">—</div>';
					}
					rowData.push(logoHtml);
				}
				
				// Channel name
				rowData.push(escapeHtml(row.name || ''));
				
				// Category
				rowData.push(escapeHtml(row.category || ''));
				
				// Website
				var websiteHtml = '';
				if (row.website) {
					websiteHtml = '<div class="tv-table-website"><a href="' + escapeHtml(row.website) + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(row.website) + '</a></div>';
				} else {
					websiteHtml = '—';
				}
				rowData.push(websiteHtml);
				
				rows.push(rowData);
			});
			
			return rows;
		}
		
		// Get column definitions
		function getColumns() {
			var cols = [
				{ title: 'Country', orderable: true, searchable: true },
			];
			
			if (showLogos) {
				cols.push({ title: 'Logo', orderable: false, searchable: false });
			}
			
			cols.push(
				{ title: 'Channel Name', orderable: true, searchable: true },
				{ title: 'Category', orderable: true, searchable: true },
				{ title: 'Website', orderable: false, searchable: true }
			);
			
			return cols;
		}
		
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
		
		// Helper function to get country flag emoji
		function getCountryFlag(countryCode) {
			if (!countryCode || countryCode.length !== 2) {
				return '';
			}
			
			try {
				// Convert country code to flag emoji
				var codePoints = countryCode.toUpperCase().split('').map(function(char) {
					return 127397 + char.charCodeAt(0);
				});
				
				return String.fromCodePoint.apply(String, codePoints);
			} catch (e) {
				return '';
			}
		}
		
		// Initialize - load data via AJAX
		loadTableData();
		
		// Global link click handler
		$(document).on('click', '.tv-country-link.global', function(e) {
			e.preventDefault();
			selectCountry('');
		});
	});
	
})(jQuery);
