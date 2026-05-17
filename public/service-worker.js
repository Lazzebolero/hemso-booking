const CACHE_NAME = 'hemso-pwa-v11';

/**
 * Laravel (and proxies) often set Vary on HTML. Default caches.match() then misses the
 * same URL for navigations vs background fetch — user goes offline and sees offline.html
 * even though the page was cached when online.
 */
const CACHE_MATCH_OPTS = { ignoreVary: true, ignoreSearch: true };

/**
 * When the app is deployed in a subdirectory (e.g. /sub/bokning/hemso/public/),
 * this worker lives at .../public/service-worker.js. Derive that base path so
 * /build, /js, precache, and offline fallback URLs match real requests.
 */
const BASE_PATH = (function deriveBasePath() {
    const p = self.location.pathname || '';
    const stripped = p.replace(/\/?service-worker\.js$/i, '');
    if (!stripped || stripped === '/') {
        return '';
    }
    return stripped.endsWith('/') ? stripped.slice(0, -1) : stripped;
})();

function appPath(absoluteFromRoot) {
    const path = absoluteFromRoot.startsWith('/') ? absoluteFromRoot : `/${absoluteFromRoot}`;
    if (!BASE_PATH) {
        return path;
    }
    return `${BASE_PATH}${path}`.replace(/\/{2,}/g, '/');
}

function relativeAppPath(fullPathname) {
    if (!BASE_PATH) {
        return fullPathname;
    }
    if (fullPathname === BASE_PATH || fullPathname === `${BASE_PATH}/`) {
        return '/';
    }
    if (fullPathname.startsWith(`${BASE_PATH}/`)) {
        return fullPathname.slice(BASE_PATH.length);
    }
    return fullPathname;
}

function absUrl(absoluteFromRoot) {
    return new URL(appPath(absoluteFromRoot), self.location.origin).href;
}

const CORE_ASSETS = [
    absUrl('/'),
    absUrl('/manifest.webmanifest'),
    absUrl('/offline.html'),
    absUrl('/js/offline-queue.js'),
    absUrl('/icons/pwa-icon-192.png'),
    absUrl('/icons/pwa-icon-512.png'),
];

self.addEventListener('install', event => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(CORE_ASSETS))
            .catch(() => null)
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => Promise.all(
            keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
        ))
    );
    self.clients.claim();
});

/**
 * Only cache successful responses for offline replay.
 * Never cache redirects (e.g. auth 302 to login) — they poison the cache so
 * unrelated URLs return the login document when offline.
 */
function cacheIfEligible(request, response) {
    if (!response || !response.ok) {
        return;
    }

    const copy = response.clone();
    caches.open(CACHE_NAME).then(cache => cache.put(request, copy));
}

/**
 * Avoid hanging forever on "lie-fi" / flaky TCP; fall back to cache/offline.html.
 */
function fetchWithNetworkTimeout(request, timeoutMs) {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), timeoutMs);

    return fetch(request, { signal: controller.signal })
        .catch(() => null)
        .finally(() => clearTimeout(timeoutId));
}

function matchAppHtmlCache(request) {
    return caches.match(request, CACHE_MATCH_OPTS).then(hit => {
        if (hit) {
            return hit;
        }

        return caches.match(new Request(request.url, { method: 'GET' }), CACHE_MATCH_OPTS);
    });
}

/**
 * Last-resort offline: same document may have been stored under the canonical app path.
 */
function matchQuickTourCreateOffline(appRelativePath) {
    if (!appRelativePath.includes('quick-tours')) {
        return Promise.resolve(null);
    }

    const canonical = new Request(absUrl('/quick-tours/create'), { method: 'GET' });

    return caches.match(canonical, CACHE_MATCH_OPTS);
}

self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET') {
        return;
    }

    const isSameOrigin = url.origin === self.location.origin;
    const path = relativeAppPath(url.pathname);

    const isStaticAsset = isSameOrigin && (
        path.startsWith('/build/')
        || path.startsWith('/js/')
        || path.startsWith('/icons/')
        || path.endsWith('.css')
        || path.endsWith('.js')
        || path.endsWith('.png')
        || path.endsWith('.jpg')
        || path.endsWith('.jpeg')
        || path.endsWith('.svg')
        || path.endsWith('.webp')
        || path.endsWith('.woff')
        || path.endsWith('.woff2')
    );

    if (isStaticAsset) {
        event.respondWith(
            caches.match(request).then(cached => {
                if (cached) {
                    return cached;
                }

                return fetch(request).then(response => {
                    if (response.ok) {
                        cacheIfEligible(request, response);
                    }

                    return response;
                });
            }).catch(() => caches.match(request))
        );
        return;
    }

    const wantsHtml = request.headers.get('accept')?.includes('text/html');
    const isNavigate = request.mode === 'navigate';

    // HTML / navigations: fetch first when online (fresh content); when offline, fetch fails
    // and we fall back to cache, then offline.html. Parallel cache read avoids waiting only on network.
    //
    // Use a long timeout for real navigations (e.g. POST redirect -> GET dashboard). A short
    // timeout here falsely served offline.html while still "online" when the server was slow.
    const htmlNetworkTimeoutMs = isNavigate ? 60000 : 8000;

    if (isSameOrigin && (wantsHtml || isNavigate)) {
        event.respondWith(
            Promise.all([
                matchAppHtmlCache(request),
                fetchWithNetworkTimeout(request, htmlNetworkTimeoutMs),
            ]).then(([cached, networkResponse]) => {
                // Any real HTTP response (4xx/5xx included) must win over offline.html so users
                // see Laravel errors / CSRF / auth — not a misleading "offline" shell.
                if (networkResponse) {
                    if (networkResponse.ok && networkResponse.status === 200 && !networkResponse.redirected) {
                        cacheIfEligible(request, networkResponse.clone());
                    }

                    return networkResponse;
                }

                if (cached) {
                    return cached;
                }

                return matchQuickTourCreateOffline(path).then(quickTour => {
                    if (quickTour) {
                        return quickTour;
                    }

                    return caches.match(new Request(absUrl('/offline.html')), CACHE_MATCH_OPTS).then(offline => {
                        return offline || caches.match(new Request(absUrl('/')), CACHE_MATCH_OPTS);
                    });
                });
            })
        );
        return;
    }

    event.respondWith(
        caches.match(request).then(cached => {
            if (cached) {
                return cached;
            }

            return fetch(request).then(response => {
                if (response.ok) {
                    cacheIfEligible(request, response);
                }

                return response;
            });
        })
    );
});
