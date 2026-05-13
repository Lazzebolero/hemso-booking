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
    const banner = document.querySelector('.pwa-offline-banner');
    if (!banner) {
        return;
    }

    banner.dataset.pendingCount = String(count);

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

    const n = Number(count || 0);
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

async function flushQueue() {
    if (!navigator.onLine) {
        return 0;
    }

    const items = await listAll();
    if (!items.length) {
        await refreshPendingCount();

        return 0;
    }

    let processed = 0;

    // Process in insertion order (IndexedDB returns key order for getAll with autoIncrement)
    for (const item of items) {
        try {
            const response = await fetch(item.url, {
                method: item.method || 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html,application/json',
                },
                body: item.body || '',
                credentials: 'same-origin',
            });

            if (!response.ok) {
                break;
            }

            await removeById(item.id);
            processed++;
        } catch {
            break;
        }
    }

    await refreshPendingCount();

    if (processed > 0) {
        window.dispatchEvent(new CustomEvent('offline-queue-flushed', { detail: { processed } }));

        window.setTimeout(() => {
            window.location.reload();
        }, 120);
    }

    return processed;
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
        params.append(key, String(value));
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

async function boot() {
    installFormInterceptors();
    await refreshPendingCount();
    await flushQueue();

    window.addEventListener('online', async () => {
        const synced = await flushQueue();

        if (synced === 0) {
            showFlash(
                'Du \u00e4r online igen. F\u00f6rs\u00f6ker skicka v\u00e4ntande \u00e5tg\u00e4rder\u2026',
                'success'
            );
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

