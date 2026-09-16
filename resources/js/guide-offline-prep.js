/**
 * Keeps guide shell pages and the active tour page cached for offline use.
 */
(function () {
    const CACHE_META = 'meta[name="guide-pwa-cache-name"]';
    const SHELL_META = 'meta[name="guide-offline-shell-urls"]';
    const TOUR_URLS_META = 'meta[name="guide-offline-tour-urls"]';
    const ONGOING_TOUR_META = 'meta[name="guide-offline-ongoing-tour-url"]';
    const WARM_TOUR_ID_META = 'meta[name="guide-warm-tour-id"]';
    const DASHBOARD_META = 'meta[name="guide-dashboard-url"]';
    const ONGOING_STORAGE_KEY = 'hemso-guide-ongoing-tour-url';
    const DEFAULT_CACHE_NAME = 'hemso-pwa-v22';
    const SHELL_GAP_MS = 45 * 1000;
    const SHELL_INTERVAL_MS = 4 * 60 * 1000;
    const TOUR_RETRY_MS = 2000;
    const TOUR_RETRY_COUNT = 8;

    let lastShellWarm = 0;

    function cacheName() {
        const meta = document.querySelector(CACHE_META);

        return meta && meta.getAttribute('content')
            ? meta.getAttribute('content')
            : DEFAULT_CACHE_NAME;
    }

    function canonicalPageUrl(url) {
        try {
            const parsed = new URL(url, window.location.origin);
            let path = parsed.pathname;

            if (path.length > 1 && path.endsWith('/')) {
                path = path.slice(0, -1);
            }

            return parsed.origin + path;
        } catch {
            return url;
        }
    }

    function appPathPrefix() {
        const dash = document.querySelector(DASHBOARD_META);

        if (!dash) {
            return '';
        }

        try {
            const pathname = new URL(dash.getAttribute('content') || '', window.location.origin).pathname;
            const marker = '/guide/dashboard';
            const index = pathname.indexOf(marker);

            return index >= 0 ? pathname.slice(0, index) : '';
        } catch {
            return '';
        }
    }

    function buildTourShowUrl(tourId) {
        const id = String(tourId || '').trim();

        if (!id) {
            return '';
        }

        return window.location.origin + appPathPrefix() + '/guide/tours/' + id;
    }

    function tourShowUrlFromAction(actionUrl) {
        const match = String(actionUrl || '').match(/\/guide\/tours\/(\d+)\/(?:start|complete)\/?$/);

        return match ? buildTourShowUrl(match[1]) : '';
    }

    function uniqueUrls(urls) {
        return urls.filter(function (url, index, list) {
            return url && list.indexOf(url) === index;
        });
    }

    function dashboardUrl() {
        const meta = document.querySelector(DASHBOARD_META);

        return meta ? (meta.getAttribute('content') || '') : '';
    }

    async function warmHtmlUrl(url) {
        if (!url || !navigator.onLine) {
            return false;
        }

        const canonical = canonicalPageUrl(url);
        let stored = false;

        if ('caches' in window) {
            try {
                const response = await fetch(canonical, {
                    credentials: 'include',
                    cache: 'no-store',
                    headers: {
                        Accept: 'text/html,application/xhtml+xml',
                    },
                });

                if (response.ok && !response.redirected) {
                    const cache = await caches.open(cacheName());
                    await cache.put(canonicalPageUrl(response.url), response.clone());
                    stored = true;
                }
            } catch {
                // ignore
            }
        }

        if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
            navigator.serviceWorker.controller.postMessage({
                type: 'warm-html-cache',
                url: canonical,
            });
        }

        return stored;
    }

    function whenServiceWorkerReady(callback) {
        if (!('serviceWorker' in navigator)) {
            callback();

            return;
        }

        navigator.serviceWorker.ready.then(callback).catch(function () {
            callback();
        });
    }

    function rememberOngoingTourUrl(url) {
        if (!url) {
            return;
        }

        try {
            sessionStorage.setItem(ONGOING_STORAGE_KEY, canonicalPageUrl(url));
        } catch {
            // ignore
        }
    }

    function collectShellWarmUrls() {
        const urls = [];
        const meta = document.querySelector(SHELL_META);

        if (meta) {
            try {
                const parsed = JSON.parse(meta.getAttribute('content') || '[]');
                if (Array.isArray(parsed)) {
                    parsed.forEach(function (url) {
                        if (url) {
                            urls.push(url);
                        }
                    });
                }
            } catch {
                // ignore
            }
        }

        document.querySelectorAll('.guide-mobile-action[href]').forEach(function (anchor) {
            const href = anchor.getAttribute('href') || '';

            if (!href || href.charAt(0) === '#') {
                return;
            }

            urls.push(anchor.href);
        });

        return uniqueUrls(urls);
    }

    function collectTourWarmUrls() {
        const urls = [];

        const warmIdMeta = document.querySelector(WARM_TOUR_ID_META);
        if (warmIdMeta) {
            const warmUrl = buildTourShowUrl(warmIdMeta.getAttribute('content') || '');
            if (warmUrl) {
                urls.push(warmUrl);
            }
        }

        const ongoingMeta = document.querySelector(ONGOING_TOUR_META);
        if (ongoingMeta) {
            const ongoingUrl = ongoingMeta.getAttribute('content') || '';
            if (ongoingUrl) {
                urls.push(ongoingUrl);
            }
        }

        document.querySelectorAll('a[href*="/guide/tours/"]').forEach(function (anchor) {
            const href = anchor.getAttribute('href') || '';

            if (/\/guide\/tours\/\d+(\/)?(\?.*)?$/.test(href.split('#')[0])) {
                urls.push(anchor.href);
            }
        });

        const meta = document.querySelector(TOUR_URLS_META);
        if (meta) {
            try {
                const parsed = JSON.parse(meta.getAttribute('content') || '[]');
                if (Array.isArray(parsed)) {
                    parsed.forEach(function (url) {
                        if (url) {
                            urls.push(url);
                        }
                    });
                }
            } catch {
                // ignore
            }
        }

        try {
            const ongoingUrl = sessionStorage.getItem(ONGOING_STORAGE_KEY);
            if (ongoingUrl) {
                urls.push(ongoingUrl);
            }
        } catch {
            // ignore
        }

        const path = window.location.pathname || '';
        if (/\/guide\/tours\/\d+\/?$/.test(path)) {
            urls.push(window.location.href);
        }

        return uniqueUrls(urls);
    }

    async function warmTourUrlsNow(urls) {
        const targets = uniqueUrls(urls);
        let storedAny = false;

        for (const url of targets) {
            rememberOngoingTourUrl(url);
            const stored = await warmHtmlUrl(url);
            storedAny = storedAny || stored;
        }

        return storedAny;
    }

    function warmTourUrlsWithRetry(urls, attemptsLeft) {
        const targets = uniqueUrls(urls);

        if (!targets.length || !navigator.onLine) {
            return;
        }

        void warmTourUrlsNow(targets).then(function (storedAny) {
            if (storedAny || attemptsLeft <= 0) {
                return;
            }

            whenServiceWorkerReady(function () {
                window.setTimeout(function () {
                    warmTourUrlsWithRetry(targets, attemptsLeft - 1);
                }, TOUR_RETRY_MS);
            });
        });
    }

    function warmGuideShellCache(force) {
        if (!navigator.onLine) {
            return;
        }

        const now = Date.now();
        if (!force && now - lastShellWarm < SHELL_GAP_MS) {
            return;
        }

        lastShellWarm = now;

        collectShellWarmUrls().forEach(function (url) {
            void warmHtmlUrl(url);
        });
    }

    function warmPriorityOfflineTargets() {
        warmTourUrlsWithRetry(collectTourWarmUrls(), TOUR_RETRY_COUNT);
        warmGuideShellCache(true);
    }

    function handleTourActionForm(form) {
        const action = form.getAttribute('action') || '';
        const tourUrl = tourShowUrlFromAction(action);

        if (!tourUrl) {
            return;
        }

        rememberOngoingTourUrl(tourUrl);

        if (navigator.onLine) {
            warmTourUrlsWithRetry([tourUrl], TOUR_RETRY_COUNT);
        }
    }

    async function tourPageIsCached(url) {
        if (!('caches' in window)) {
            return false;
        }

        try {
            const cache = await caches.open(cacheName());
            const hit = await cache.match(canonicalPageUrl(url), { ignoreVary: true });

            return !!hit;
        } catch {
            return false;
        }
    }

    function installWarmHooks() {
        document.addEventListener('click', function (event) {
            const anchor = event.target.closest('a[href]');

            if (!anchor) {
                return;
            }

            const href = anchor.getAttribute('href') || '';

            if (href.charAt(0) !== '#') {
                void warmHtmlUrl(anchor.href);
            }

            if (/\/guide\/tours\/\d+(\/)?(\?.*)?$/.test(href.split('#')[0])) {
                rememberOngoingTourUrl(anchor.href);

                if (!navigator.onLine) {
                    event.preventDefault();

                    void tourPageIsCached(anchor.href).then(function (cached) {
                        if (cached) {
                            window.location.assign(anchor.href);

                            return;
                        }

                        const dash = dashboardUrl();
                        if (dash) {
                            window.location.assign(dash);

                            return;
                        }

                        window.location.assign(anchor.href);
                    });
                }
            }
        }, true);

        document.addEventListener('submit', function (event) {
            const form = event.target;

            if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-offline-queue')) {
                return;
            }

            handleTourActionForm(form);
        }, true);

        window.addEventListener('offline-queued', function (event) {
            const actionUrl = event.detail && event.detail.url ? event.detail.url : '';
            const tourUrl = tourShowUrlFromAction(actionUrl);

            if (tourUrl) {
                rememberOngoingTourUrl(tourUrl);
            }
        });
    }

    function boot() {
        installWarmHooks();
        warmPriorityOfflineTargets();

        window.addEventListener('load', warmPriorityOfflineTargets);

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.addEventListener('controllerchange', warmPriorityOfflineTargets);
        }

        window.setInterval(function () {
            warmTourUrlsWithRetry(collectTourWarmUrls(), 2);
            warmGuideShellCache(false);
        }, SHELL_INTERVAL_MS);

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') {
                warmPriorityOfflineTargets();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
