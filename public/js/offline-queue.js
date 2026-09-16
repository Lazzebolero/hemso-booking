/**
 * Offline POST queue for forms with data-offline-queue.
 * Copied to public/js/offline-queue.js when you run: npm run build
 * (scripts/sync-offline-queue.cjs). User-visible strings use \\u escapes (ASCII-safe).
 */
const DB_NAME = 'hemso-pwa';
const DB_VERSION = 1;
const STORE_NAME = 'request_queue';

function openDb() {
    return new Promise((resolve, reject) => {
        const req = indexedDB.open(DB_NAME, DB_VERSION);

        req.onupgradeneeded = () => {
            const db = req.result;
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
            }
        };

        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
}

function txStore(db, mode) {
    return db.transaction(STORE_NAME, mode).objectStore(STORE_NAME);
}

async function enqueue(item) {
    const items = await listAll();
    const key = queueDedupeKey(item);

    for (const existing of items) {
        if (queueDedupeKey(existing) === key) {
            await removeById(existing.id);
        }
    }

    const db = await openDb();
    return new Promise((resolve, reject) => {
        const store = txStore(db, 'readwrite');
        const req = store.add(item);
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
}

async function listAll() {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const store = txStore(db, 'readonly');
        const req = store.getAll();
        req.onsuccess = () => resolve(req.result || []);
        req.onerror = () => reject(req.error);
    });
}

async function removeById(id) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const store = txStore(db, 'readwrite');
        const req = store.delete(id);
        req.onsuccess = () => resolve();
        req.onerror = () => reject(req.error);
    });
}

function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

function pulseUrl() {
    const meta = document.querySelector('meta[name="app-pulse-url"]');
    return meta ? meta.getAttribute('content') : '';
}

function absoluteRequestUrl(url) {
    try {
        return new URL(url, window.location.origin).href;
    } catch {
        return String(url || '');
    }
}

async function refreshSessionContext() {
    const url = pulseUrl();

    if (!url || !navigator.onLine) {
        return false;
    }

    try {
        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            cache: 'no-store',
        });

        if (response.status === 401 || response.status === 419) {
            return false;
        }

        if (!response.ok) {
            return false;
        }

        const data = await response.json();
        const meta = document.querySelector('meta[name="csrf-token"]');

        if (meta && data && data.csrf_token) {
            meta.setAttribute('content', data.csrf_token);
        }

        return true;
    } catch {
        return false;
    }
}

function prepareFlushRequest(item) {
    const params = new URLSearchParams(item.body || '');
    const token = csrfToken();

    if (token) {
        params.set('_token', token);
    }

    let method = String(item.method || 'POST').toUpperCase();

    if (method !== 'GET' && method !== 'POST') {
        params.set('_method', method);
        method = 'POST';
    }

    return {
        url: absoluteRequestUrl(item.url),
        method,
        body: params.toString(),
    };
}

async function sendQueuedItem(item) {
    const prepared = prepareFlushRequest(item);

    return fetch(prepared.url, {
        method: prepared.method,
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json, text/html, */*',
        },
        body: prepared.body,
        credentials: 'same-origin',
        redirect: 'follow',
    });
}

function showFlash(message, type = 'warning') {
    const existing = document.getElementById('pwa-offline-flash');
    if (existing) {
        existing.remove();
    }

    const box = document.createElement('div');
    box.id = 'pwa-offline-flash';
    box.setAttribute('role', 'status');
    box.style.cssText = `
        position: fixed;
        left: 16px;
        right: 16px;
        bottom: 16px;
        z-index: 9999;
        border-radius: 16px;
        padding: 12px 14px;
        font-weight: 800;
        box-shadow: 0 18px 40px rgba(15,23,42,0.16);
        border: 1px solid rgba(0,0,0,0.08);
        max-width: 720px;
        margin: 0 auto;
    `;

    if (type === 'success') {
        box.style.background = '#f0fdf4';
        box.style.borderColor = '#bbf7d0';
        box.style.color = '#047857';
        box.textContent = message;
    } else if (type === 'error') {
        box.style.background = '#fef2f2';
        box.style.borderColor = '#fecaca';
        box.style.color = '#b91c1c';
        box.textContent = message;
    } else {
        box.style.background = '#fff7ed';
        box.style.borderColor = '#fdba74';
        box.style.color = '#9a3412';
        box.textContent = message;
    }

    document.body.appendChild(box);

    window.setTimeout(() => {
        const current = document.getElementById('pwa-offline-flash');
        if (current) {
            current.remove();
        }
    }, 6500);
}

function updateOfflineBannerCount(count) {
    const n = Number(count || 0);

    document.body.classList.toggle('has-offline-pending', n > 0);

    const banner = document.querySelector('[data-offline-banner]') || document.querySelector('.pwa-offline-banner');
    const offlineText = document.querySelector('[data-offline-banner-offline-text]');
    const pendingText = document.querySelector('[data-offline-banner-pending-text]');
    const pendingLabel = document.querySelector('[data-offline-banner-pending-label]');
    const syncButton = document.querySelector('[data-offline-sync-now]');
    const skipButton = document.querySelector('[data-offline-skip-pending]');

    if (pendingLabel) {
        pendingLabel.textContent = n === 1
            ? '1 offline-\u00e5tg\u00e4rd v\u00e4ntar p\u00e5 att skickas.'
            : n + ' offline-\u00e5tg\u00e4rder v\u00e4ntar p\u00e5 att skickas.';
    }

    if (offlineText && pendingText) {
        if (navigator.onLine && n > 0) {
            offlineText.classList.add('d-none');
            pendingText.classList.remove('d-none');
        } else if (!navigator.onLine) {
            offlineText.classList.remove('d-none');
            pendingText.classList.toggle('d-none', n === 0);
        } else {
            offlineText.classList.remove('d-none');
            pendingText.classList.add('d-none');
        }
    }

    if (syncButton) {
        syncButton.classList.toggle('d-none', !(navigator.onLine && n > 0));
        syncButton.disabled = false;
    }

    if (skipButton) {
        skipButton.classList.toggle('d-none', !(navigator.onLine && n > 0));
        skipButton.disabled = false;
    }

    if (!banner) {
        return;
    }

    banner.dataset.pendingCount = String(n);

    let badge = banner.querySelector('[data-offline-pending-count]');
    if (!badge) {
        badge = document.createElement('span');
        badge.setAttribute('data-offline-pending-count', '1');
        badge.style.cssText = `
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.25rem;
            height: 1.25rem;
            padding: 0 0.35rem;
            border-radius: 999px;
            margin-left: 0.5rem;
            background: #9a3412;
            color: #fff;
            font-size: 0.72rem;
            font-weight: 900;
            line-height: 1;
        `;
        banner.appendChild(badge);
    }

    badge.textContent = String(n);
    badge.style.display = n > 0 ? 'inline-flex' : 'none';
}

async function refreshPendingCount() {
    try {
        const items = await listAll();
        updateOfflineBannerCount(items.length);
    } catch {
        updateOfflineBannerCount(0);
    }
}

async function invalidateCurrentPageCache() {
    if (!('serviceWorker' in navigator) || !navigator.serviceWorker.controller) {
        return;
    }

    navigator.serviceWorker.controller.postMessage({
        type: 'invalidate-html-cache',
        url: window.location.href,
    });
}

function isAuthFailure(status) {
    return status === 401 || status === 419 || status === 403;
}

async function parseJsonResponse(response) {
    const contentType = response.headers.get('content-type') || '';

    if (!contentType.includes('application/json')) {
        return null;
    }

    try {
        return await response.json();
    } catch {
        return null;
    }
}

function queueDedupeKey(item) {
    return normalizePath(item.url);
}

async function dedupeQueueBeforeFlush(items) {
    const keepByKey = new Map();

    for (const item of items) {
        keepByKey.set(queueDedupeKey(item), item);
    }

    for (const item of items) {
        const keeper = keepByKey.get(queueDedupeKey(item));

        if (keeper && keeper.id !== item.id) {
            await removeById(item.id);
        }
    }

    return [...keepByKey.values()].sort((left, right) => left.id - right.id);
}

async function skipPendingQueueItems() {
    const items = await listAll();

    for (const item of items) {
        await removeById(item.id);
    }

    await refreshPendingCount();
    showFlash('V\u00e4ntande offline-\u00e5tg\u00e4rder hoppades \u00f6ver.', 'warning');
}

function normalizePath(url) {
    try {
        return new URL(url, window.location.origin).pathname;
    } catch {
        return String(url || '');
    }
}

function isGuideTourActionUrl(url) {
    const path = normalizePath(url);

    if (/\/guide\/tours\/\d+\/(start|complete)\/?$/.test(path)) {
        return true;
    }

    const root = document.querySelector('[data-guide-tour-root]');

    if (!root) {
        return false;
    }

    const startUrl = root.getAttribute('data-start-url') || '';
    const completeUrl = root.getAttribute('data-complete-url') || '';

    return path === normalizePath(startUrl) || path === normalizePath(completeUrl);
}

function classifyFlushResponse(response) {
    if (isAuthFailure(response.status)) {
        return 'auth';
    }

    if (response.status >= 200 && response.status < 300) {
        return 'ok';
    }

    if (response.status === 422) {
        return 'validation';
    }

    return 'failed';
}

async function flushResponseMessage(response) {
    const payload = await parseJsonResponse(response);

    if (payload && payload.message) {
        return payload.message;
    }

    if (payload && payload.errors) {
        const firstError = Object.values(payload.errors).flat()[0];

        if (firstError) {
            return String(firstError);
        }
    }

    return null;
}

let periodicFlushTimer = null;
let flushInProgress = false;

function schedulePeriodicFlush() {
    if (periodicFlushTimer) {
        return;
    }

    periodicFlushTimer = window.setInterval(() => {
        if (!navigator.onLine) {
            return;
        }

        void flushQueue();
    }, 10000);
}

async function ensurePeriodicFlushScheduled() {
    try {
        const items = await listAll();

        if (items.length > 0 && navigator.onLine) {
            schedulePeriodicFlush();
        } else if (periodicFlushTimer) {
            window.clearInterval(periodicFlushTimer);
            periodicFlushTimer = null;
        }
    } catch {
        // ignore
    }
}

async function flushQueue(manual = false) {
    if (!navigator.onLine) {
        if (manual) {
            showFlash('Du \u00e4r offline. Synk sker n\u00e4r n\u00e4tet \u00e4r tillbaka.', 'warning');
        }

        return 0;
    }

    if (flushInProgress) {
        return 0;
    }

    const items = await listAll();
    if (!items.length) {
        await refreshPendingCount();

        return 0;
    }

    const queueItems = await dedupeQueueBeforeFlush(items);
    if (!queueItems.length) {
        await refreshPendingCount();

        return 0;
    }

    flushInProgress = true;

    try {
        await refreshSessionContext();

        let processed = 0;
        let failed = 0;
        let lastTourPayload = null;
        let hadTourActionOnCurrentPage = false;
        let authBlocked = false;

        for (const item of queueItems) {
            try {
                let response = await sendQueuedItem(item);

                if (isAuthFailure(response.status)) {
                    const refreshed = await refreshSessionContext();

                    if (refreshed) {
                        response = await sendQueuedItem(item);
                    }
                }

                if (isAuthFailure(response.status)) {
                    authBlocked = true;
                    showFlash(
                        'Inloggningen har g\u00e5tt ut. Logga in igen \u2013 v\u00e4ntande offline-\u00e5tg\u00e4rder finns kvar.',
                        'error'
                    );
                    break;
                }

                const outcome = classifyFlushResponse(response);

                if (outcome === 'validation') {
                    const message = await flushResponseMessage(response);
                    showFlash(
                        message || 'En sparad offline-\u00e4ndring kunde inte synkas och hoppades \u00f6ver.',
                        'error'
                    );
                    await removeById(item.id);
                    continue;
                }

                if (outcome !== 'ok') {
                    if (response.status === 404 || response.status === 405 || response.status === 409) {
                        await removeById(item.id);
                        showFlash('En f\u00f6r\u00e5ldrad offline-\u00e5tg\u00e4rd hoppades \u00f6ver.', 'warning');
                        continue;
                    }

                    failed++;
                    const message = await flushResponseMessage(response);
                    showFlash(
                        message || ('Synk misslyckades (HTTP ' + response.status + '). F\u00f6rs\u00f6ker igen\u2026'),
                        'error'
                    );
                    continue;
                }

                if (isGuideTourActionUrl(item.url)) {
                    hadTourActionOnCurrentPage = true;

                    const payload = await parseJsonResponse(response);

                    if (payload && payload.status) {
                        lastTourPayload = payload;
                    }
                }

                await removeById(item.id);
                processed++;
            } catch {
                failed++;
            }
        }

        await refreshPendingCount();
        await ensurePeriodicFlushScheduled();

        const remaining = await listAll();

        if (processed > 0) {
            const detail = {
                processed,
                lastTourPayload,
                hadTourActionOnCurrentPage,
            };

            window.dispatchEvent(new CustomEvent('offline-queue-flushed', { detail }));

            let skipReload = false;

            if (window.hemsoGuideTourUi && typeof window.hemsoGuideTourUi.applyFlushResult === 'function') {
                skipReload = window.hemsoGuideTourUi.applyFlushResult(detail);
            }

            if (skipReload) {
                showFlash(
                    processed === 1
                        ? 'Offline-\u00e5tg\u00e4rden \u00e4r synkad.'
                        : processed + ' offline-\u00e5tg\u00e4rder \u00e4r synkade.',
                    'success'
                );

                await invalidateCurrentPageCache();
            } else {
                showFlash(
                    processed === 1
                        ? 'Offline-\u00e5tg\u00e4rden skickades. Uppdaterar sidan\u2026'
                        : processed + ' offline-\u00e5tg\u00e4rder skickades. Uppdaterar sidan\u2026',
                    'success'
                );

                await invalidateCurrentPageCache();

                window.setTimeout(() => {
                    window.location.reload();
                }, 250);
            }
        } else if (manual && !authBlocked) {
            showFlash(
                remaining.length > 0
                    ? ('Kunde inte synka ' + remaining.length + ' v\u00e4ntande offline-\u00e5tg\u00e4rder \u00e4nnu. F\u00f6rs\u00f6ker igen automatiskt.')
                    : 'Inget att synka just nu.',
                remaining.length > 0 ? 'warning' : 'success'
            );
        } else if (failed > 0 && remaining.length > 0) {
            showFlash(
                remaining.length + ' offline-\u00e5tg\u00e4rder v\u00e4ntar fortfarande p\u00e5 synk.',
                'warning'
            );
        }

        return processed;
    } finally {
        flushInProgress = false;
    }
}

function serializeForm(form) {
    const fd = new FormData(form);

    // Ensure CSRF exists if present on the page (Blade usually includes it)
    if (!fd.has('_token') && csrfToken()) {
        fd.set('_token', csrfToken());
    }

    // Capture client-side timestamp for queued actions (best-effort).
    // Server may choose to ignore it; it is not a security boundary.
    if (!fd.has('client_occurred_at')) {
        fd.set('client_occurred_at', new Date().toISOString());
    }

    if (!fd.has('client_tz')) {
        try {
            fd.set('client_tz', Intl.DateTimeFormat().resolvedOptions().timeZone || '');
        } catch {
            fd.set('client_tz', '');
        }
    }

    const params = new URLSearchParams();
    for (const [key, value] of fd.entries()) {
        // We only support simple forms (text/number/select). Ignore files.
        if (value instanceof File) {
            continue;
        }

        let stringValue = String(value);

        if (stringValue === '' && (key.endsWith('_count') || key === 'actual_on_site_count')) {
            stringValue = '0';
        }

        params.append(key, stringValue);
    }

    return params.toString();
}

function resolveMethod(form) {
    const method = (form.getAttribute('method') || 'POST').toUpperCase();
    const override = form.querySelector('input[name="_method"]');
    if (override && override.value) {
        return String(override.value).toUpperCase();
    }
    return method;
}

async function handleOfflineQueuedSubmit(form) {
    const action = form.getAttribute('action') || window.location.href;
    const method = resolveMethod(form);
    const body = serializeForm(form);

    const id = await enqueue({
        url: action,
        method: method === 'GET' ? 'POST' : method,
        body,
        created_at: Date.now(),
    });

    await refreshPendingCount();
    await ensurePeriodicFlushScheduled();
    showFlash(
        'Offline: \u00e5tg\u00e4rden \u00e4r sparad och skickas n\u00e4r du f\u00e5r t\u00e4ckning.',
        'warning'
    );

    try {
        const fd = new FormData(form);
        const fields = {};
        for (const [key, value] of fd.entries()) {
            if (value instanceof File) {
                continue;
            }
            fields[key] = String(value);
        }

        window.dispatchEvent(new CustomEvent('offline-queued', {
            detail: {
                id,
                url: action,
                method,
                fields,
            },
        }));
    } catch {
    }

    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((btn) => {
        btn.disabled = true;
    });
}

function installFormInterceptors() {
    document.addEventListener(
        'submit',
        (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            if (!form.hasAttribute('data-offline-queue')) {
                return;
            }

            if (navigator.onLine) {
                return;
            }

            event.preventDefault();
            void handleOfflineQueuedSubmit(form);
        },
        true
    );
}

function installSyncButton() {
    document.addEventListener('click', (event) => {
        const syncButton = event.target.closest('[data-offline-sync-now]');

        if (syncButton) {
            syncButton.disabled = true;
            void flushQueue(true).finally(() => {
                void refreshPendingCount();
            });

            return;
        }

        const skipButton = event.target.closest('[data-offline-skip-pending]');

        if (!skipButton) {
            return;
        }

        skipButton.disabled = true;
        void skipPendingQueueItems();
    });
}

async function boot() {
    installFormInterceptors();
    installSyncButton();
    await refreshPendingCount();
    await flushQueue();

    const handleConnectivityChange = () => {
        void refreshPendingCount();
        if (navigator.onLine) {
            void flushQueue();
        }
    };

    window.addEventListener('online', () => {
        document.dispatchEvent(new CustomEvent('app:pulse:online'));
        handleConnectivityChange();
    });
    window.addEventListener('offline', () => {
        void refreshPendingCount();
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible' && navigator.onLine) {
            void refreshPendingCount();
            void flushQueue();
        }
    });

    window.addEventListener('focus', () => {
        if (navigator.onLine) {
            void refreshPendingCount();
            void flushQueue();
        }
    });

    document.addEventListener('app:pulse', () => {
        if (navigator.onLine) {
            void flushQueue();
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

window.hemsoOfflineQueue = {
    enqueueForm: handleOfflineQueuedSubmit,
    showFlash,
    refreshPendingCount,
    flushQueue,
    skipPendingQueueItems,
    isAuthFailure,
};
