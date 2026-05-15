/**
 * data/darkmode.js
 * Safety-net / fallback — the real logic is inline in header.php
 * This file only re-attaches the handler if it somehow wasn't set up.
 */
(function() {
	'use strict';

	function setup() {
		// If body doesn't have the class but html does, sync them
		if (document.documentElement.classList.contains('dark-mode') && !document.body.classList.contains('dark-mode')) {
			document.body.classList.add('dark-mode');
		}

		var btn = document.getElementById('darkModeToggle');
		if (!btn) return;

		// Only attach if not already attached (check with a data attribute)
		if (btn.getAttribute('data-dm-init') === 'true') return;
		btn.setAttribute('data-dm-init', 'true');

		btn.addEventListener('click', function() {
			var isDarkNow = document.body.classList.contains('dark-mode');
			if (isDarkNow) {
				document.body.classList.remove('dark-mode');
				document.documentElement.classList.remove('dark-mode');
				localStorage.setItem('darkModeEnabled', 'false');
			} else {
				document.body.classList.add('dark-mode');
				document.documentElement.classList.add('dark-mode');
				localStorage.setItem('darkModeEnabled', 'true');
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', setup);
	} else {
		setup();
	}
})();
