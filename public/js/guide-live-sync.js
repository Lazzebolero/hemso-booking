/**
 * Silent dashboard refresh only — never reload tour/form pages.
 * Activated only when <meta name="guide-live-sync" content="dashboard"> is present.
 */
(function () {
    const MIN_HIDDEN_MS = 5 * 60 * 1000;
    const RELOAD_COOLDOWN_MS = 10 * 60 * 1000;

    let hiddenAt = null;
    let lastReloadAt = 0;

    function isDashboardSyncEnabled() {
        const meta = document.querySelector('meta[name="guide-live-sync"]');
        return meta && meta.getAttribute('content') === 'dashboard';
    }

    function currentToursVersion() {
        const meta = document.querySelector('meta[name="guide-tours-version"]');
        const value = meta ? String(meta.getAttribute('content') || '').trim() : '';
        return value || null;
    }

    function pulseUrl() {
        const meta = document.querySelector('meta[name="app-pulse-url"]');
        return meta ? meta.getAttribute('content') : '/app/pulse';
    }

    async function fetchPulse() {
        try {
            const response = await fetch(pulseUrl(), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                cache: 'no-store',
            });

            if (!response.ok) {
                return null;
            }

            return await response.json();
        } catch {
            return null;
        }
    }

    function silentReload() {
        const now = Date.now();

        if (!navigator.onLine || now - lastReloadAt < RELOAD_COOLDOWN_MS) {
            return;
        }

        lastReloadAt = now;

        if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
            navigator.serviceWorker.controller.postMessage({
                type: 'invalidate-html-cache',
                url: window.location.href,
            });
        }

        window.location.reload();
    }

    async function maybeRefreshAfterResume() {
        if (!isDashboardSyncEnabled() || !navigator.onLine) {
            return;
        }

        const pageVersion = currentToursVersion();
        const data = await fetchPulse();

        if (!data || !data.tours_version) {
            return;
        }

        const serverVersion = String(data.tours_version);

        if (pageVersion && serverVersion !== pageVersion) {
            silentReload();
        }
    }

    function onVisibilityChange() {
        if (!isDashboardSyncEnabled()) {
            return;
        }

        if (document.visibilityState === 'hidden') {
            hiddenAt = Date.now();
            return;
        }

        const wasHiddenAt = hiddenAt;
        hiddenAt = null;

        if (!wasHiddenAt) {
            return;
        }

        if (Date.now() - wasHiddenAt >= MIN_HIDDEN_MS) {
            void maybeRefreshAfterResume();
        }
    }

    function boot() {
        if (!isDashboardSyncEnabled()) {
            return;
        }

        document.addEventListener('visibilitychange', onVisibilityChange);

        window.addEventListener('online', function () {
            if (!hiddenAt) {
                void maybeRefreshAfterResume();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
