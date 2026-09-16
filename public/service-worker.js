const CACHE_NAME = 'hemso-pwa-v24';

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

function canonicalHtmlUrl(href) {
    try {
        const url = new URL(href, self.location.origin);
        let pathname = url.pathname;

        if (pathname.length > 1 && pathname.endsWith('/')) {
            pathname = pathname.slice(0, -1);
        }

        return `${url.origin}${pathname}`;
    } catch {
        return href;
    }
}

function canonicalHtmlRequest(href) {
    return new Request(canonicalHtmlUrl(href), { method: 'GET' });
}

function warmHtmlCacheUrl(url) {
    const request = canonicalHtmlRequest(url);

    return fetch(request, { credentials: 'include' })
        .then(response => {
            if (!response || !response.ok || response.redirected) {
                return null;
            }

            const path = relativeAppPath(new URL(request.url).pathname);

            if (shouldSkipHtmlCache(path) || isLoginDocumentResponse(response, path)) {
                return null;
            }

            return caches.open(CACHE_NAME).then(cache => cache.put(request, response.clone()));
        })
        .catch(() => null);
}

self.addEventListener('message', event => {
    const data = event.data;

    if (!data || !data.type) {
        return;
    }

    if (data.type === 'invalidate-html-cache' && data.url) {
        event.waitUntil(
            caches.open(CACHE_NAME).then(cache => cache.delete(canonicalHtmlRequest(data.url), CACHE_MATCH_OPTS))
        );

        return;
    }

    if (data.type === 'warm-html-cache' && data.url) {
        event.waitUntil(warmHtmlCacheUrl(data.url));
    }
});

function isAuthPage(path) {
    return path === '/login'
        || path.startsWith('/login/')
        || path === '/register'
        || path.startsWith('/register/')
        || path.startsWith('/password')
        || path.startsWith('/forgot-password')
        || path.startsWith('/reset-password')
        || path.startsWith('/verify-email')
        || path.startsWith('/confirm-password');
}

function isRoleRoutingPage(path) {
    return path === '/dashboard'
        || path === '/select-role'
        || path.startsWith('/select-role/');
}

function isLoginDocumentResponse(response, requestPath) {
    if (!response || !response.ok) {
        return false;
    }

    try {
        const responsePath = relativeAppPath(new URL(response.url).pathname);
        if (isAuthPage(responsePath)) {
            return true;
        }
    } catch {
        return isAuthPage(requestPath);
    }

    return false;
}

function shouldSkipHtmlCache(path) {
    return isAuthPage(path) || isRoleRoutingPage(path);
}

function cacheIfEligible(request, response, requestPath) {
    if (!response || !response.ok || response.redirected) {
        return;
    }

    const path = requestPath || relativeAppPath(new URL(request.url).pathname);

    if (shouldSkipHtmlCache(path) || isLoginDocumentResponse(response, path)) {
        return;
    }

    const copy = response.clone();
    const cacheRequest = canonicalHtmlRequest(request.url);
    caches.open(CACHE_NAME).then(cache => cache.put(cacheRequest, copy));
}

function fetchWithNetworkTimeout(request, timeoutMs) {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), timeoutMs);

    return fetch(request, { signal: controller.signal })
        .catch(() => null)
        .finally(() => clearTimeout(timeoutId));
}

function matchAppHtmlCache(request) {
    const canonical = canonicalHtmlRequest(request.url);

    return caches.match(canonical, CACHE_MATCH_OPTS).then(hit => {
        if (hit) {
            return hit;
        }

        return caches.match(request, CACHE_MATCH_OPTS).then(secondHit => {
            if (secondHit) {
                return secondHit;
            }

            return caches.match(new Request(request.url, { method: 'GET' }), CACHE_MATCH_OPTS);
        });
    });
}

function isDynamicAppPage(path) {
    return path === '/dashboard'
        || path.startsWith('/admin/')
        || path.startsWith('/host/')
        || path.startsWith('/guide/');
}

function isLiveSystemMessageRequest(path) {
    return path.startsWith('/system-messages/');
}

function matchQuickTourCreateOffline(appRelativePath) {
    if (!appRelativePath.includes('quick-tours')) {
        return Promise.resolve(null);
    }

    const canonical = new Request(absUrl('/quick-tours/create'), { method: 'GET' });

    return caches.match(canonical, CACHE_MATCH_OPTS);
}

function isGuideTourShowPath(path) {
    return /^\/guide\/tours\/\d+\/?$/.test(path);
}

function isGuidePath(path) {
    return path === '/guide' || path.startsWith('/guide/');
}

function matchCachedPathSuffix(pathSuffix) {
    return caches.open(CACHE_NAME).then(cache => cache.keys().then(keys => {
        const match = keys.find((request) => {
            try {
                const pathname = new URL(request.url).pathname;

                return pathname === pathSuffix || pathname.endsWith(pathSuffix);
            } catch {
                return false;
            }
        });

        if (!match) {
            return null;
        }

        return cache.match(match, CACHE_MATCH_OPTS);
    }));
}

function matchGuideDashboardCache() {
    const candidates = [
        canonicalHtmlUrl(absUrl('/guide/dashboard')),
        canonicalHtmlUrl(new URL('/guide/dashboard', self.location.origin).href),
    ];

    return candidates.reduce(
        (promise, candidate) => promise.then(hit => {
            if (hit) {
                return hit;
            }

            return caches.match(new Request(candidate, { method: 'GET' }), CACHE_MATCH_OPTS);
        }),
        Promise.resolve(null),
    ).then(hit => hit || matchCachedPathSuffix('/guide/dashboard'));
}

function isGuideShellOfflinePath(path) {
    if (isGuidePath(path)) {
        return true;
    }

    return path === '/my-schedule' || path.startsWith('/my-schedule/')
        || path === '/messages' || path.startsWith('/messages/')
        || path.startsWith('/group-chats/')
        || path === '/time' || path.startsWith('/time/')
        || path.startsWith('/quick-tours/')
        || path.startsWith('/staff/documents')
        || path.startsWith('/visitor-dogs')
        || path.startsWith('/guide/reports');
}

function matchGuideDashboardOrOffline() {
    return matchGuideDashboardCache().then(dashboard => {
        if (dashboard) {
            return dashboard;
        }

        return offlineHtmlFallback();
    });
}

function matchGuideOfflineFallback(request, path) {
    return matchQuickTourCreateOffline(path).then(quickTour => {
        if (quickTour) {
            return quickTour;
        }

        if (isGuideTourShowPath(path) || isGuideShellOfflinePath(path)) {
            return matchAppHtmlCache(request).then(cachedPage => {
                if (cachedPage) {
                    return cachedPage;
                }

                if (isGuideTourShowPath(path)) {
                    const tourIdMatch = path.match(/^\/guide\/tours\/(\d+)\/?$/);

                    if (tourIdMatch) {
                        return matchCachedPathSuffix(`/guide/tours/${tourIdMatch[1]}`).then(cachedTour => {
                            if (cachedTour) {
                                return cachedTour;
                            }

                            return matchGuideDashboardOrOffline();
                        });
                    }
                }

                return matchGuideDashboardOrOffline();
            });
        }

        return offlineHtmlFallback();
    });
}

function offlineHtmlFallback() {
    return caches.match(new Request(absUrl('/offline.html')), CACHE_MATCH_OPTS);
}

function respondWithHtml(request, path, htmlNetworkTimeoutMs) {
    return Promise.all([
        matchAppHtmlCache(request),
        fetchWithNetworkTimeout(request, htmlNetworkTimeoutMs),
    ]).then(([cached, networkResponse]) => {
        if (networkResponse) {
            if (networkResponse.ok && networkResponse.status === 200 && !networkResponse.redirected && !shouldSkipHtmlCache(path) && !isLoginDocumentResponse(networkResponse, path)) {
                cacheIfEligible(request, networkResponse.clone(), path);
            }

            return networkResponse;
        }

        if (cached) {
            return cached;
        }

        return matchGuideOfflineFallback(request, path);
    });
}

self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET') {
        return;
    }

    const isSameOrigin = url.origin === self.location.origin;
    const path = relativeAppPath(url.pathname);
    const wantsHtml = request.headers.get('accept')?.includes('text/html');
    const isNavigate = request.mode === 'navigate';

    const isStaticAsset = isSameOrigin && !isNavigate && !wantsHtml && (
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
                        cacheIfEligible(request, response, path);
                    }

                    return response;
                });
            }).catch(() => caches.match(request))
        );
        return;
    }

    const htmlNetworkTimeoutMs = isNavigate ? 60000 : 8000;

    if (isSameOrigin && isLiveSystemMessageRequest(path)) {
        event.respondWith(
            fetch(request).catch(() => caches.match(request))
        );

        return;
    }

    if (isSameOrigin && (wantsHtml || isNavigate)) {
        if (isDynamicAppPage(path)) {
            event.respondWith(respondWithHtml(request, path, htmlNetworkTimeoutMs));
            return;
        }

        event.respondWith(respondWithHtml(request, path, htmlNetworkTimeoutMs));
        return;
    }

    event.respondWith(
        caches.match(request).then(cached => {
            if (cached) {
                return cached;
            }

            return fetch(request).then(response => {
                if (response.ok) {
                    cacheIfEligible(request, response, path);
                }

                return response;
            });
        })
    );
});
