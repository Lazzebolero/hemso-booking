/**
 * Hemso site status bar - weather + next ferry.
 *
 * WordPress embed:
 * <div id="hemso-site-status"></div>
 * <script src="https://bokning.hemsofastning.se/js/site-status-bar.js" defer></script>
 */
(function () {
    'use strict';

    var SCRIPT = document.currentScript;
    var ENDPOINT = (function () {
        if (SCRIPT && SCRIPT.getAttribute('data-endpoint')) {
            return SCRIPT.getAttribute('data-endpoint');
        }
        if (SCRIPT && SCRIPT.src) {
            try {
                var url = new URL(SCRIPT.src);
                return url.origin + '/public/site-status.json';
            } catch (e) {
                // fall through
            }
        }
        return '/public/site-status.json';
    })();

    var TARGET_ID = (SCRIPT && SCRIPT.getAttribute('data-target')) || 'hemso-site-status';

    var LABEL_WEATHER = 'V\u00e4der';
    var LABEL_FERRY = 'N\u00e4sta f\u00e4rja';
    var FALLBACK_WEATHER = 'V\u00e4derdata saknas just nu';
    var FALLBACK_FERRY = 'F\u00e4rjedata saknas just nu';
    var LOADING = 'H\u00e4mtar status\u2026';

    function ensureStyles() {
        if (document.getElementById('hemso-site-status-style')) {
            return;
        }

        var style = document.createElement('style');
        style.id = 'hemso-site-status-style';
        style.textContent = [
            '#hemso-site-status,.hemso-site-status{box-sizing:border-box;width:100%;}',
            '.hemso-site-status-bar{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;',
            'min-height:3.25rem;padding:.65rem 1rem;border:1px solid rgba(15,23,42,.1);border-radius:12px;',
            'background:linear-gradient(180deg,#fbfdff 0%,#f8fafc 100%);color:#0f172a;',
            'font:500 0.95rem/1.35 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;}',
            '.hemso-site-status-item{display:flex;align-items:center;justify-content:center;gap:.55rem;min-width:0;flex:1 1 220px;text-align:center;}',
            '.hemso-site-status-item + .hemso-site-status-item{border-left:1px solid rgba(15,23,42,.12);padding-left:1rem;}',
            '.hemso-site-status-label{font-size:.72rem;letter-spacing:.04em;text-transform:uppercase;color:#64748b;font-weight:700;}',
            '.hemso-site-status-value{font-weight:650;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}',
            '.hemso-site-status-muted{color:#64748b;font-weight:500;}',
            '@media (max-width:640px){',
            '.hemso-site-status-bar{padding:.7rem .85rem;}',
            '.hemso-site-status-item + .hemso-site-status-item{border-left:0;border-top:1px solid rgba(15,23,42,.1);padding-left:0;padding-top:.55rem;flex-basis:100%;}',
            '.hemso-site-status-value{white-space:normal;}',
            '}'
        ].join('');
        document.head.appendChild(style);
    }

    function text(value, fallback) {
        if (typeof value === 'string' && value.trim() !== '') {
            return value;
        }
        return fallback;
    }

    function render(target, payload) {
        ensureStyles();

        var weather = (payload && payload.weather) || {};
        var ferry = (payload && payload.ferry) || {};

        target.innerHTML = [
            '<div class="hemso-site-status-bar" role="status" aria-live="polite">',
            '  <div class="hemso-site-status-item">',
            '    <div>',
            '      <div class="hemso-site-status-label">' + LABEL_WEATHER + '</div>',
            '      <div class="hemso-site-status-value">' + escapeHtml(text(weather.text, FALLBACK_WEATHER)) + '</div>',
            '    </div>',
            '  </div>',
            '  <div class="hemso-site-status-item">',
            '    <div>',
            '      <div class="hemso-site-status-label">' + LABEL_FERRY + '</div>',
            '      <div class="hemso-site-status-value">' + escapeHtml(text(ferry.text, FALLBACK_FERRY)) + '</div>',
            '    </div>',
            '  </div>',
            '</div>'
        ].join('');
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function load() {
        var target = document.getElementById(TARGET_ID);
        if (!target) {
            return;
        }

        target.classList.add('hemso-site-status');
        target.innerHTML = '<div class="hemso-site-status-bar"><div class="hemso-site-status-item"><div class="hemso-site-status-value hemso-site-status-muted">' + LOADING + '</div></div></div>';
        ensureStyles();

        fetch(ENDPOINT, {
            credentials: 'omit',
            headers: { 'Accept': 'application/json' }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('status ' + response.status);
                }
                return response.json();
            })
            .then(function (json) {
                render(target, (json && json.data) || {});
            })
            .catch(function () {
                render(target, {
                    weather: { text: FALLBACK_WEATHER },
                    ferry: { text: FALLBACK_FERRY }
                });
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', load);
    } else {
        load();
    }
})();
