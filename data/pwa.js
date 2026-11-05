/*
 * Progressive Web App support for the VVZ.
 *
 * - Registers the service worker (offline app shell + offline fallback page).
 * - Shows a clear warning in the administration area while offline.
 * - Keeps the session ("logged in") untouched, but blocks live editing and
 *   sends the user to the offline page when they try to change something.
 */
(function () {
	'use strict';

	var body = document.body;
	var isAdmin = body && body.getAttribute('data-vvz-admin') === '1';
	var offlineBannerId = 'vvz-offline-banner';
	var disabledByUs = [];
	var isOffline = navigator.onLine === false;

	function adminOfflinePageUrl() {
		try {
			return new URL('offline.html', window.location.href).href;
		} catch (e) {
			return 'offline.html';
		}
	}

	function markOfflineOrigin() {
		try {
			sessionStorage.setItem('vvz-offline-from-admin', '1');
		} catch (e) {
			/* ignore */
		}
	}

	function clearOfflineOrigin() {
		try {
			sessionStorage.removeItem('vvz-offline-from-admin');
		} catch (e) {
			/* ignore */
		}
	}

	function openOfflinePage() {
		markOfflineOrigin();
		window.location.assign(adminOfflinePageUrl());
	}

	function ensureBanner() {
		var banner = document.getElementById(offlineBannerId);
		if (banner) {
			return banner;
		}

		banner = document.createElement('div');
		banner.id = offlineBannerId;
		banner.setAttribute('role', 'alert');
		banner.setAttribute('aria-live', 'assertive');
		banner.hidden = true;

		var title = document.createElement('strong');
		title.textContent = 'Offline-Modus';

		var text = document.createElement('span');
		text.className = 'vvz-offline-banner-text';
		text.textContent = 'Sie sind weiterhin angemeldet, aber die Administration ist offline. ' +
			'Änderungen können nicht live gespeichert werden. ' +
			'Wenn Sie eine Admin-Seite aufrufen oder etwas bearbeiten möchten, wird die Offline-Seite angezeigt. ' +
			'Sobald die Verbindung wieder da ist, können Sie normal weiterarbeiten.';

		var retry = document.createElement('button');
		retry.type = 'button';
		retry.textContent = 'Erneut versuchen';
		retry.addEventListener('click', function () {
			retry.disabled = true;
			retry.textContent = 'Prüfe Verbindung …';

			function backOnline() {
				retry.disabled = false;
				retry.textContent = 'Erneut versuchen';
				clearOfflineOrigin();
				window.location.reload();
			}

			function stillOffline() {
				retry.disabled = false;
				retry.textContent = 'Weiterhin offline';
			}

			if (navigator.onLine) {
				window.location.reload();
				return;
			}

			fetch('sw.js?ping=' + Date.now(), { method: 'HEAD', cache: 'no-store' })
				.then(function (response) {
					if (response && response.ok) {
						backOnline();
					} else {
						stillOffline();
					}
				})
				.catch(stillOffline);
		});

		banner.appendChild(title);
		banner.appendChild(text);
		banner.appendChild(retry);

		if (body && body.firstChild) {
			body.insertBefore(banner, body.firstChild);
		} else if (body) {
			body.appendChild(banner);
		}

		return banner;
	}

	function disableAdminControls() {
		if (!isAdmin) {
			return;
		}

		var controls = document.querySelectorAll('#main input, #main select, #main textarea, #main button, form input, form select, form textarea, form button');
		for (var i = 0; i < controls.length; i++) {
			var control = controls[i];
			if (control.closest('#' + offlineBannerId)) {
				continue;
			}
			if (control.type === 'hidden') {
				continue;
			}
			if (!control.disabled) {
				control.disabled = true;
				control.setAttribute('data-vvz-disabled-offline', '1');
				if (control.hasAttribute('title')) {
					control.setAttribute('data-vvz-original-title', control.getAttribute('title'));
				}
				control.setAttribute('title', 'Offline: Bearbeiten ist momentan nicht möglich.');
				control.classList.add('vvz-offline-lockable');
				disabledByUs.push(control);
			}
		}
	}

	function enableAdminControls() {
		for (var i = 0; i < disabledByUs.length; i++) {
			var control = disabledByUs[i];
			if (control && control.getAttribute('data-vvz-disabled-offline') === '1') {
				control.disabled = false;
				control.removeAttribute('data-vvz-disabled-offline');
				control.classList.remove('vvz-offline-lockable');
				if (control.hasAttribute('data-vvz-original-title')) {
					control.setAttribute('title', control.getAttribute('data-vvz-original-title'));
					control.removeAttribute('data-vvz-original-title');
				} else {
					control.removeAttribute('title');
				}
			}
		}
		disabledByUs = [];
	}

	function watchAdminContent() {
		if (!isAdmin || !window.MutationObserver) {
			return;
		}
		var main = document.getElementById('main');
		if (!main) {
			return;
		}
		var observer = new MutationObserver(function () {
			if (isOffline) {
				disableAdminControls();
			}
		});
		observer.observe(main, { childList: true, subtree: true });
	}

	function setAdminOnlineState() {
		isOffline = navigator.onLine === false;
		document.documentElement.classList.toggle('vvz-is-offline', isOffline);

		if (!isAdmin) {
			return;
		}

		var banner = ensureBanner();
		banner.hidden = !isOffline;

		if (isOffline) {
			markOfflineOrigin();
			disableAdminControls();
			watchAdminContent();
		} else {
			clearOfflineOrigin();
			enableAdminControls();
		}
	}

	function isAdminNavigation(link) {
		if (!link) {
			return false;
		}
		var href = link.getAttribute('href');
		if (!href || href.charAt(0) === '#' || /^javascript:/i.test(href) || link.target === '_blank') {
			return false;
		}
		var url;
		try {
			url = new URL(link.href, window.location.href);
		} catch (e) {
			return false;
		}
		if (url.origin !== window.location.origin) {
			return false;
		}
		return /(^|\/)(admin(\.php)?|pages)(\/|$)/.test(url.pathname) || /(^|\/)admin(\.php)?$/.test(url.pathname);
	}

	if (isAdmin) {
		document.addEventListener('submit', function (event) {
			if (!isOffline) {
				return;
			}
			event.preventDefault();
			event.stopImmediatePropagation();
			openOfflinePage();
		}, true);

		document.addEventListener('click', function (event) {
			if (!isOffline) {
				return;
			}
			var link = event.target && event.target.closest ? event.target.closest('a[href]') : null;
			if (!isAdminNavigation(link)) {
				return;
			}
			event.preventDefault();
			event.stopImmediatePropagation();
			openOfflinePage();
		}, true);

		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', setAdminOnlineState);
		} else {
			setAdminOnlineState();
		}

		window.addEventListener('online', setAdminOnlineState);
		window.addEventListener('offline', setAdminOnlineState);
	}

	if ('serviceWorker' in navigator) {
		window.addEventListener('load', function () {
			navigator.serviceWorker.register('sw.js', { scope: './' }).then(function (registration) {
				function cacheCurrentPage() {
					if (isAdmin || !registration.active) {
						return;
					}
					registration.active.postMessage({
						type: 'CACHE_CURRENT_PAGE',
						url: window.location.href
					});
				}

				if (navigator.serviceWorker.controller) {
					cacheCurrentPage();
				}
				navigator.serviceWorker.addEventListener('controllerchange', cacheCurrentPage);
			}).catch(function (error) {
				if (window.console && console.warn) {
					console.warn('Service Worker konnte nicht registriert werden:', error);
				}
			});
		});
	}
})();
