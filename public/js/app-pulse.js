(function () {
    const PULSE_INTERVAL_MS = 30000;
    let lastToursVersion = null;
    let lastUrgentCount = null;
    let lastUnreadPmCount = null;
    let timer = null;
    let isRunning = false;

    function pulseUrl() {
        const meta = document.querySelector('meta[name="app-pulse-url"]');
        return meta ? meta.getAttribute('content') : '/app/pulse';
    }

    function updatePmBadge(count) {
        document.querySelectorAll('[data-pm-unread-count]').forEach(function (badge) {
            badge.textContent = count;
            badge.classList.toggle('d-none', count <= 0);
            badge.style.display = count > 0 ? 'inline-flex' : 'none';
        });

        document.dispatchEvent(new CustomEvent('app:pulse:pm', {
            detail: { unread_pm: count }
        }));
    }

    function showUrgentMessageHint(count) {
        if (lastUrgentCount === null) {
            lastUrgentCount = count;
            return;
        }

        if (count <= lastUrgentCount) {
            lastUrgentCount = count;
            return;
        }

        lastUrgentCount = count;

        const oldAlert = document.querySelector('[data-app-pulse-urgent-alert]');
        if (oldAlert) {
            oldAlert.remove();
        }

        const alert = document.createElement('div');
        alert.setAttribute('data-app-pulse-urgent-alert', '1');
        alert.className = 'alert alert-danger shadow position-fixed top-0 start-50 translate-middle-x mt-3';
        alert.style.zIndex = '3000';
        alert.style.maxWidth = '92vw';
        alert.innerHTML = '<strong>Akut systemmeddelande</strong><br>Det finns ett nytt akut meddelande. Öppna meddelanden för att läsa.';

        document.body.appendChild(alert);

        if (navigator.vibrate) {
            navigator.vibrate([200, 100, 200]);
        }

        setTimeout(function () {
            alert.remove();
        }, 12000);

        document.dispatchEvent(new CustomEvent('app:pulse:urgent', {
            detail: { urgent_messages: count }
        }));
    }

    function handleToursVersion(version) {
        if (!version) {
            return;
        }

        if (lastToursVersion === null) {
            lastToursVersion = version;
            return;
        }

        if (version === lastToursVersion) {
            return;
        }

        lastToursVersion = version;

        document.dispatchEvent(new CustomEvent('app:pulse:tours-updated', {
            detail: { tours_version: version }
        }));
    }

    async function runPulse() {
        if (isRunning || document.hidden || !navigator.onLine) {
            return;
        }

        isRunning = true;

        try {
            const response = await fetch(pulseUrl(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                cache: 'no-store'
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            window.__lastAppPulse = data;

            const urgentMessages = Number(data.urgent_messages || 0);
            const unreadPm = Number(data.unread_pm || 0);

            showUrgentMessageHint(urgentMessages);

            if (lastUnreadPmCount === null || unreadPm !== lastUnreadPmCount) {
                lastUnreadPmCount = unreadPm;
                updatePmBadge(unreadPm);
            }

            handleToursVersion(data.tours_version || null);

            document.dispatchEvent(new CustomEvent('app:pulse', {
                detail: data
            }));
        } catch (error) {
        } finally {
            isRunning = false;
        }
    }

    function start() {
        runPulse();

        if (timer) {
            clearInterval(timer);
        }

        timer = setInterval(runPulse, PULSE_INTERVAL_MS);
    }

    document.addEventListener('DOMContentLoaded', start);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            runPulse();
        }
    });
    window.addEventListener('online', runPulse);
})();
