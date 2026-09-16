/**
 * Optimistic UI for guide tour start/complete when offline or after reload.
 * Copied to public/js/guide-tour-optimistic-ui.js via scripts/sync-guide-tour-ui.cjs
 */
(function () {
    const DB_NAME = 'hemso-pwa';
    const DB_VERSION = 1;
    const STORE_NAME = 'request_queue';

    const STATUS_BADGES = {
        planned: { label: 'Planerad', className: 'badge-soft badge-soft-warning' },
        started: { label: 'P\u00e5g\u00e5r', className: 'badge-soft badge-soft-success' },
        completed: { label: 'Avslutad', className: 'badge-soft badge-soft-secondary' },
    };

    function storageKey(tourId) {
        return 'hemso-guide-tour-pending:' + String(tourId);
    }

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

    async function listQueueItems() {
        try {
            const db = await openDb();

            return await new Promise((resolve, reject) => {
                const store = db.transaction(STORE_NAME, 'readonly').objectStore(STORE_NAME);
                const req = store.getAll();
                req.onsuccess = () => resolve(req.result || []);
                req.onerror = () => reject(req.error);
            });
        } catch {
            return [];
        }
    }

    function csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');

        return meta ? meta.getAttribute('content') : '';
    }

    function resolveMethod(form) {
        const method = (form.getAttribute('method') || 'POST').toUpperCase();
        const override = form.querySelector('input[name="_method"]');

        if (override && override.value) {
            return String(override.value).toUpperCase();
        }

        return method;
    }

    function serializeForm(form) {
        const fd = new FormData(form);

        if (!fd.has('_token') && csrfToken()) {
            fd.set('_token', csrfToken());
        }

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
            if (value instanceof File) {
                continue;
            }

            params.append(key, String(value));
        }

        return params.toString();
    }

    function getRoot() {
        return document.querySelector('[data-guide-tour-root]');
    }

    function parseContext(root) {
        return {
            tourId: root.getAttribute('data-tour-id') || '',
            status: root.getAttribute('data-tour-status') || 'planned',
            startUrl: root.getAttribute('data-start-url') || '',
            completeUrl: root.getAttribute('data-complete-url') || '',
            serverStartedAt: root.getAttribute('data-server-started-at') || '',
        };
    }

    function normalizePath(url) {
        try {
            return new URL(url, window.location.origin).pathname;
        } catch {
            return String(url || '');
        }
    }

    function resolveAction(url, ctx) {
        const path = normalizePath(url);

        if (ctx.startUrl && path === normalizePath(ctx.startUrl)) {
            return 'start';
        }

        if (ctx.completeUrl && path === normalizePath(ctx.completeUrl)) {
            return 'complete';
        }

        return null;
    }

    function formatTime(value) {
        const date = value instanceof Date ? value : new Date(value);

        if (Number.isNaN(date.getTime())) {
            return '-';
        }

        return date.toLocaleTimeString('sv-SE', {
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    function readStoredPending(ctx) {
        try {
            const raw = sessionStorage.getItem(storageKey(ctx.tourId));

            if (!raw) {
                return null;
            }

            const data = JSON.parse(raw);

            if (!data || typeof data !== 'object' || !data.action) {
                return null;
            }

            return data;
        } catch {
            return null;
        }
    }

    function writeStoredPending(ctx, payload) {
        sessionStorage.setItem(storageKey(ctx.tourId), JSON.stringify(payload));
    }

    function clearStoredPending(ctx) {
        sessionStorage.removeItem(storageKey(ctx.tourId));
    }

    async function resolveQueuedAction(ctx) {
        const items = await listQueueItems();
        let action = null;

        for (const item of items) {
            const match = resolveAction(item.url, ctx);

            if (match) {
                action = match;
            }
        }

        return action;
    }

    function setStepActive(root, step, active) {
        root.querySelectorAll('[data-guide-tour-step="' + step + '"]').forEach(function (node) {
            node.classList.toggle('tour-step-active', active);
        });
    }

    function setSyncPending(root, pending) {
        const badge = root.querySelector('[data-guide-tour-sync-pending]');

        if (!badge) {
            return;
        }

        if (pending) {
            badge.removeAttribute('hidden');
        } else {
            badge.setAttribute('hidden', 'hidden');
        }
    }

    function setStatusBadge(root, status) {
        const badge = root.querySelector('[data-guide-tour-status-badge]');
        const config = STATUS_BADGES[status];

        if (!badge || !config) {
            return;
        }

        badge.className = config.className;
        badge.textContent = config.label;
    }

    function setTimeLabel(root, selector, value) {
        const node = root.querySelector(selector);

        if (node) {
            node.textContent = value || '-';
        }
    }

    function readStartedAtLabel(root) {
        const node = root.querySelector('[data-guide-tour-started-at]');
        const text = node ? String(node.textContent || '').trim() : '';

        return text && text !== '-' ? text : '';
    }

    function renderPrimaryAction(root, status, pending) {
        const host = root.querySelector('[data-guide-tour-primary-action]');

        if (!host) {
            return;
        }

        if (status === 'completed') {
            const template = root.querySelector('[data-guide-tour-template="completed"]');

            if (template) {
                host.innerHTML = template.innerHTML;
            } else {
                host.innerHTML = '<div class="alert alert-success mb-0"><i class="bi bi-check-circle me-2"></i>Tur avslutad.</div>';
            }

            return;
        }

        if (status === 'started') {
            const template = root.querySelector('[data-guide-tour-template="complete"]');

            if (template) {
                host.innerHTML = template.innerHTML;
            }

            if (pending) {
                host.querySelectorAll('[data-guide-tour-action-form] button[type="submit"]').forEach(function (button) {
                    button.disabled = false;
                });
            }

            return;
        }

        const template = root.querySelector('[data-guide-tour-template="start"]');

        if (template) {
            host.innerHTML = template.innerHTML;
        }

        if (pending) {
            host.querySelectorAll('[data-guide-tour-action-form] button[type="submit"]').forEach(function (button) {
                button.disabled = true;
            });
        }
    }

    function applyTourState(root, state) {
        const status = state.status;
        const startedAt = state.startedAt ? formatTime(state.startedAt) : readStartedAtLabel(root);
        const endedAt = state.endedAt ? formatTime(state.endedAt) : null;

        root.setAttribute('data-tour-status', status);
        setStatusBadge(root, status);
        setSyncPending(root, !!state.pending);

        setStepActive(root, 'planned', ['planned', 'started', 'completed'].indexOf(status) !== -1);
        setStepActive(root, 'started', status === 'started' || status === 'completed');
        setStepActive(root, 'completed', status === 'completed');

        if (status === 'started' || status === 'completed') {
            setTimeLabel(root, '[data-guide-tour-started-at]', startedAt || formatTime(new Date()));
        }

        if (status === 'completed') {
            setTimeLabel(root, '[data-guide-tour-ended-at]', endedAt || formatTime(new Date()));
        }

        renderPrimaryAction(root, status, state.pending);
    }

    function applyServerTourState(root, payload, pending) {
        const status = payload.status || root.getAttribute('data-tour-status') || 'planned';

        root.setAttribute('data-tour-status', status);
        setStatusBadge(root, status);
        setSyncPending(root, pending);

        setStepActive(root, 'planned', ['planned', 'started', 'completed'].indexOf(status) !== -1);
        setStepActive(root, 'started', status === 'started' || status === 'completed');
        setStepActive(root, 'completed', status === 'completed');

        if (payload.started_at) {
            setTimeLabel(root, '[data-guide-tour-started-at]', payload.started_at);
        }

        if (payload.ended_at) {
            setTimeLabel(root, '[data-guide-tour-ended-at]', payload.ended_at);
        }

        renderPrimaryAction(root, status, pending);

        if (status === 'started') {
            rememberOngoingTourUrl();
            warmDashboardCache();
        }

        if (status === 'completed') {
            clearOngoingTourUrl();
            warmDashboardCache();
        }
    }

    function buildStateFromAction(root, ctx, action, clientAt, pending) {
        const timestamp = clientAt || new Date().toISOString();
        let startedAt = timestamp;

        if (action === 'complete') {
            startedAt = readStartedAtLabel(root) || ctx.serverStartedAt || timestamp;
        }

        if (action === 'start') {
            return {
                status: 'started',
                startedAt: timestamp,
                pending: pending,
            };
        }

        return {
            status: 'completed',
            startedAt: startedAt,
            endedAt: timestamp,
            pending: pending,
        };
    }

    function applyPendingAction(root, ctx, action, clientAt, pending) {
        const startedAt = action === 'complete'
            ? (readStartedAtLabel(root) || ctx.serverStartedAt || clientAt)
            : clientAt;

        writeStoredPending(ctx, {
            action: action,
            clientAt: clientAt,
            startedAt: startedAt,
        });

        applyTourState(root, buildStateFromAction(root, ctx, action, clientAt, pending));
    }

    function setFormSubmitting(form, submitting) {
        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (button) {
            button.disabled = submitting;
        });
    }

    function showFlashMessage(message, type) {
        if (window.hemsoOfflineQueue && typeof window.hemsoOfflineQueue.showFlash === 'function') {
            window.hemsoOfflineQueue.showFlash(message, type);

            return;
        }
    }

    function redirectAfterComplete(payload) {
        if (!payload || payload.status !== 'completed' || !payload.redirect_url) {
            return false;
        }

        window.location.assign(payload.redirect_url);

        return true;
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

    function warmHtmlCache(url) {
        if (!url || !navigator.onLine) {
            return;
        }

        if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
            navigator.serviceWorker.controller.postMessage({
                type: 'warm-html-cache',
                url: url,
            });

            return;
        }

        fetch(url, { credentials: 'same-origin' }).catch(function () {});
    }

    function warmDashboardCache() {
        const meta = document.querySelector('meta[name="guide-dashboard-url"]');

        if (!meta) {
            return;
        }

        const url = meta.getAttribute('content');

        if (!url) {
            return;
        }

        warmHtmlCache(url);
    }

    function warmCurrentTourPageCache() {
        if (!document.querySelector('[data-guide-tour-root]')) {
            return;
        }

        warmHtmlCache(window.location.href);
    }

    function rememberOngoingTourUrl() {
        try {
            sessionStorage.setItem('hemso-guide-ongoing-tour-url', window.location.href);
        } catch {
            // ignore
        }
    }

    function clearOngoingTourUrl() {
        try {
            sessionStorage.removeItem('hemso-guide-ongoing-tour-url');
        } catch {
            // ignore
        }
    }

    function isAuthFailure(status) {
        if (window.hemsoOfflineQueue && typeof window.hemsoOfflineQueue.isAuthFailure === 'function') {
            return window.hemsoOfflineQueue.isAuthFailure(status);
        }

        return status === 401 || status === 419 || status === 403;
    }

    async function restorePendingState() {
        const root = getRoot();

        if (!root) {
            return;
        }

        const ctx = parseContext(root);
        const queuedAction = await resolveQueuedAction(ctx);
        const stored = readStoredPending(ctx);
        const action = queuedAction || (stored ? stored.action : null);

        if (!action) {
            if (stored && !queuedAction) {
                clearStoredPending(ctx);
            }

            return;
        }

        const clientAt = stored && stored.clientAt ? stored.clientAt : new Date().toISOString();
        const pending = !!queuedAction;

        if (queuedAction) {
            writeStoredPending(ctx, {
                action: queuedAction,
                clientAt: clientAt,
                startedAt: stored && stored.startedAt ? stored.startedAt : (ctx.serverStartedAt || null),
            });
        }

        applyTourState(root, buildStateFromAction(root, ctx, action, clientAt, pending));
    }

    function handleOfflineQueued(event) {
        const root = getRoot();

        if (!root || !event || !event.detail) {
            return;
        }

        const ctx = parseContext(root);
        const action = resolveAction(event.detail.url, ctx);

        if (!action) {
            return;
        }

        const clientAt = event.detail.fields && event.detail.fields.client_occurred_at
            ? event.detail.fields.client_occurred_at
            : new Date().toISOString();

        applyPendingAction(root, ctx, action, clientAt, true);
    }

    function handleQueueFlushed(event) {
        const detail = event && event.detail ? event.detail : null;

        if (detail && applyFlushResult(detail)) {
            return;
        }

        const root = getRoot();

        if (!root) {
            return;
        }

        clearStoredPending(parseContext(root));
        setSyncPending(root, false);
    }

    function applyFlushResult(detail) {
        const root = getRoot();

        if (!root || !detail || !detail.hadTourActionOnCurrentPage) {
            return false;
        }

        const ctx = parseContext(root);

        clearStoredPending(ctx);
        setSyncPending(root, false);

        if (detail.lastTourPayload && detail.lastTourPayload.status) {
            if (redirectAfterComplete(detail.lastTourPayload)) {
                return true;
            }

            applyServerTourState(root, detail.lastTourPayload, false);
            showFlashMessage(
                detail.lastTourPayload.message || 'Offline-\u00e5tg\u00e4rden \u00e4r synkad.',
                'success'
            );
            void invalidateCurrentPageCache();

            return true;
        }

        void restorePendingState();

        showFlashMessage('Offline-\u00e5tg\u00e4rden \u00e4r skickad.', 'success');
        void invalidateCurrentPageCache();

        return true;
    }

    async function handleOnlineTourSubmit(event) {
        const form = event.target;

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        if (!form.hasAttribute('data-guide-tour-action-form')) {
            return;
        }

        if (!navigator.onLine) {
            return;
        }

        event.preventDefault();

        const root = getRoot();

        if (!root) {
            return;
        }

        const ctx = parseContext(root);
        const action = resolveAction(form.getAttribute('action') || '', ctx);

        if (!action) {
            return;
        }

        const clientAt = new Date().toISOString();

        applyPendingAction(root, ctx, action, clientAt, true);
        setFormSubmitting(form, true);

        try {
            const response = await fetch(form.getAttribute('action') || window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: serializeForm(form),
                credentials: 'same-origin',
            });

            if (isAuthFailure(response.status)) {
                showFlashMessage(
                    'Inloggningen har g\u00e5tt ut. Logga in igen och f\u00f6rs\u00f6k p\u00e5 nytt.',
                    'error'
                );
                clearStoredPending(ctx);
                window.location.reload();

                return;
            }

            if (!response.ok) {
                throw new Error('request failed');
            }

            const payload = await response.json();

            clearStoredPending(ctx);

            if (redirectAfterComplete(payload)) {
                return;
            }

            applyServerTourState(root, payload, false);
            await invalidateCurrentPageCache();
            showFlashMessage(payload.message || 'Tur uppdaterad.', 'success');
        } catch {
            if (window.hemsoOfflineQueue && typeof window.hemsoOfflineQueue.enqueueForm === 'function') {
                await window.hemsoOfflineQueue.enqueueForm(form);
            } else {
                showFlashMessage(
                    'Kunde inte skicka direkt. \u00c5tg\u00e4rden sparas och skickas n\u00e4r n\u00e4tet \u00e4r stabilt.',
                    'warning'
                );
            }
        } finally {
            setFormSubmitting(form, false);
        }
    }

    function installOnlineFormInterceptors() {
        document.addEventListener('submit', function (event) {
            void handleOnlineTourSubmit(event);
        }, true);
    }

    function boot() {
        void restorePendingState();
        installOnlineFormInterceptors();

        const root = getRoot();
        if (root && root.getAttribute('data-tour-status') === 'started') {
            rememberOngoingTourUrl();
        }

        warmCurrentTourPageCache();

        window.addEventListener('offline-queued', handleOfflineQueued);
        window.addEventListener('offline-queue-flushed', handleQueueFlushed);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    window.hemsoGuideTourUi = {
        applyFlushResult: applyFlushResult,
    };
})();
