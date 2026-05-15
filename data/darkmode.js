/**
 * Dark Mode Toggle with localStorage persistence
 * Files: data/style_darkmode.css, data/styles_darkmode.css, data/admin_darkmode.css
 * Automatically restores saved preference on page load.
 */
(function () {
	'use strict';

	var STORAGE_KEY = 'darkModeEnabled';

	/**
	 * Apply dark mode class to both <html> and <body>
	 */
	function enableDarkMode() {
		document.documentElement.classList.add('dark-mode');
		if (document.body) {
			document.body.classList.add('dark-mode');
		}
	}

	/**
	 * Remove dark mode class from both <html> and <body>
	 */
	function disableDarkMode() {
		document.documentElement.classList.remove('dark-mode');
		if (document.body) {
			document.body.classList.remove('dark-mode');
		}
	}

	/**
	 * Check if dark mode should be active based on stored preference or system setting
	 */
	function shouldBeDark() {
		var stored = localStorage.getItem(STORAGE_KEY);
		if (stored === 'true') return true;
		if (stored === 'false') return false;
		// No preference saved — use system preference
		return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
	}

	/**
	 * Apply the correct mode immediately (called early to prevent FOUC)
	 */
	function applyStoredPreference() {
		if (shouldBeDark()) {
			enableDarkMode();
		} else {
			disableDarkMode();
		}
	}

	// Apply immediately — this runs before body is fully parsed
	applyStoredPreference();

	/**
	 * Initialize the toggle button event listener
	 */
	function initToggle() {
		// Ensure body matches html state
		if (document.documentElement.classList.contains('dark-mode')) {
			document.body.classList.add('dark-mode');
		}

		var btn = document.getElementById('darkModeToggle');
		if (!btn) return;

		btn.addEventListener('click', function () {
			var isDark = document.body.classList.contains('dark-mode');

			if (isDark) {
				disableDarkMode();
				localStorage.setItem(STORAGE_KEY, 'false');
			} else {
				enableDarkMode();
				localStorage.setItem(STORAGE_KEY, 'true');
			}
		});
	}

	/**
	 * Listen for system preference changes (only if user hasn't manually chosen)
	 */
	if (window.matchMedia) {
		try {
			window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
				var stored = localStorage.getItem(STORAGE_KEY);
				// Only auto-switch if user hasn't manually set a preference
				if (stored === null) {
					if (e.matches) {
						enableDarkMode();
					} else {
						disableDarkMode();
					}
				}
			});
		} catch (e) {
			// Fallback for older browsers that don't support addEventListener on matchMedia
			try {
				window.matchMedia('(prefers-color-scheme: dark)').addListener(function (e) {
					var stored = localStorage.getItem(STORAGE_KEY);
					if (stored === null) {
						if (e.matches) {
							enableDarkMode();
						} else {
							disableDarkMode();
						}
					}
				});
			} catch (e2) {
				// Browser doesn't support matchMedia listeners at all
			}
		}
	}

	// Initialize toggle when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initToggle);
	} else {
		initToggle();
	}

})();
