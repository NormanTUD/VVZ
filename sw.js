/*
 * VVZ Service Worker
 *
 * Strategy:
 * - Public pages and static assets are cached so the VVZ can be shown offline.
 * - Administration pages and /pages/ include files are never cached; when they
 *   cannot be reached, the dedicated offline page is returned instead.
 * - Only GET requests are handled. Logins and edit requests are never cached.
 */

var VERSION = 'vvz-pwa-v1';
var STATIC_CACHE = VERSION + '-static';
var PAGES_CACHE = VERSION + '-pages';
var OFFLINE_URL = new URL('./offline.html', self.location.href).href;

var PRECACHE_URLS = [
	'./offline.html',
	'./manifest.webmanifest',
	'./data/pwa.js',
	'./data/pwa.css',
	'./css/all.css',
	'./css/v4-shims.css',
	'./data/jquery-ui.css',
	'./data/style.css',
	'./data/admin.css',
	'./data/foundation.min.css',
	'./data/bootstrap-tour-standalone.css',
	'./data/jquery-ui-timepicker-addon.css',
	'./data/toastr.min.css',
	'./data/darkmode_toggle.css',
	'./data/custom.php',
	'./data/jquery-3.6.1.min.js',
	'./data/jquery-ui.js',
	'./data/jquery-ui-timepicker-addon.js',
	'./data/bootstrap-tour-standalone.js',
	'./data/toastr.js',
	'./data/msgs.php',
	'./data/mainscript.php',
	'./js/all.js',
	'./favicon.ico',
	'./icon.svg',
	'./default_logo.png',
	'./pwa-icon-192.png',
	'./pwa-icon-512.png'
];

var STATIC_DESTINATIONS = {
	style: true,
	script: true,
	image: true,
	font: true,
	manifest: true
};

function isAdminRequest(url) {
	return /(^|\/)(admin(\.php)?|pages)(\/|$)/.test(url.pathname);
}

function isStaticRequest(request, url) {
	if (STATIC_DESTINATIONS[request.destination]) {
		return true;
	}
	return /\.(?:css|js|mjs|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|eot|pdf)$/i.test(url.pathname);
}

function offlineResponse() {
	return caches.match(OFFLINE_URL).then(function (response) {
		if (response) {
			return response;
		}
		return new Response(
			'<!DOCTYPE html><html lang="de"><head><meta charset="utf-8">' +
			'<meta name="viewport" content="width=device-width, initial-scale=1">' +
			'<title>Offline – Vorlesungsverzeichnis</title></head>' +
			'<body style="font-family:Arial,sans-serif;padding:2rem;line-height:1.5">' +
			'<h1>Offline</h1><p>Das Vorlesungsverzeichnis ist gerade nicht erreichbar. ' +
			'Bitte prüfen Sie Ihre Verbindung und versuchen Sie es erneut.</p>' +
			'<p><button onclick="location.reload()">Erneut versuchen</button></p>' +
			'</body></html>',
			{ headers: { 'Content-Type': 'text/html; charset=utf-8' } }
		);
	});
}

function cacheResponse(cacheName, request, response) {
	if (!response || !response.ok || response.type !== 'basic') {
		return Promise.resolve();
	}
	var copy = response.clone();
	return caches.open(cacheName).then(function (cache) {
		return cache.put(request, copy);
	}).catch(function () {
		/* Caching is best effort and must never break the response. */
	});
}

function networkFirst(request, event) {
	return fetch(request).then(function (response) {
		event.waitUntil(cacheResponse(PAGES_CACHE, request, response));
		return response;
	}).catch(function () {
		return caches.match(request).then(function (cached) {
			if (cached) {
				return cached;
			}
			if (request.mode === 'navigate') {
				return offlineResponse();
			}
			return new Response(JSON.stringify({ offline: true, error: 'Offline' }), {
				status: 503,
				headers: { 'Content-Type': 'application/json; charset=utf-8' }
			});
		});
	});
}

function cacheFirst(request, event) {
	return caches.match(request).then(function (cached) {
		if (cached) {
			event.waitUntil(
				fetch(request).then(function (response) {
					return cacheResponse(STATIC_CACHE, request, response);
				}).catch(function () {
					/* Keep the cached version while offline. */
				})
			);
			return cached;
		}
		return fetch(request).then(function (response) {
			event.waitUntil(cacheResponse(STATIC_CACHE, request, response));
			return response;
		}).catch(function () {
			return new Response('', { status: 503, statusText: 'Offline' });
		});
	});
}

self.addEventListener('install', function (event) {
	event.waitUntil(
		caches.open(STATIC_CACHE).then(function (cache) {
			return Promise.all(PRECACHE_URLS.map(function (url) {
				return fetch(new Request(url, { cache: 'reload' })).then(function (response) {
					if (response && response.ok) {
						return cache.put(url, response);
					}
				}).catch(function () {
					/* A single missing asset must not break the installation. */
				});
			}));
		}).then(function () {
			return self.skipWaiting();
		})
	);
});

self.addEventListener('activate', function (event) {
	event.waitUntil(
		caches.keys().then(function (keys) {
			return Promise.all(keys.map(function (key) {
				if (key !== STATIC_CACHE && key !== PAGES_CACHE) {
					return caches.delete(key);
				}
			}));
		}).then(function () {
			return self.clients.claim();
		})
	);
});

self.addEventListener('message', function (event) {
	var data = event.data || {};
	if (data.type !== 'CACHE_CURRENT_PAGE' || !data.url) {
		return;
	}

	var url;
	try {
		url = new URL(data.url);
	} catch (e) {
		return;
	}

	if (url.origin !== self.location.origin || isAdminRequest(url)) {
		return;
	}

	event.waitUntil(
		fetch(url.href, { credentials: 'same-origin' }).then(function (response) {
			return cacheResponse(PAGES_CACHE, url.href, response);
		}).catch(function () {
			/* Offline: nothing to cache. */
		})
	);
});

self.addEventListener('fetch', function (event) {
	var request = event.request;
	if (request.method !== 'GET') {
		return;
	}

	var url;
	try {
		url = new URL(request.url);
	} catch (e) {
		return;
	}

	if (url.origin !== self.location.origin) {
		return;
	}

	if (isAdminRequest(url)) {
		if (request.mode === 'navigate') {
			event.respondWith(
				fetch(request).catch(function () {
					return offlineResponse();
				})
			);
		}
		return;
	}

	if (request.mode === 'navigate') {
		event.respondWith(networkFirst(request, event));
		return;
	}

	if (isStaticRequest(request, url)) {
		event.respondWith(cacheFirst(request, event));
		return;
	}

	event.respondWith(networkFirst(request, event));
});
