@if(auth()->check() && Route::has('system-messages.live-panel'))
@verbatim
<script>
    (function () {
        const tag = 'd' + 'iv';

        const livePanelUrl = @endverbatim @json(route('system-messages.live-panel')) @verbatim;
        const forcePopupUrl = @endverbatim @json(route('system-messages.force-popup-panel')) @verbatim;
        const readBaseUrl = @endverbatim @json(url('/system-messages')) @verbatim;
        const readUrl = (id) => readBaseUrl + '/' + id + '/read';
        const acknowledgeUrl = (id) => readBaseUrl + '/' + id + '/acknowledge';

        let lastUnreadCount = @endverbatim {{ (int) ($unreadSystemMessagesCount ?? 0) }} @verbatim;
        let popupShownThisPage = false;

        async function refreshSystemMessagePanel(isInitialLoad) {
            try {
                const response = await fetch(livePanelUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();
                const currentUnread = Number(data.unread_count ?? 0);

                updateBadge(currentUnread);

                const importantUnread = Array.isArray(data.important_unread) ? data.important_unread : [];
                const unread = Array.isArray(data.unread) ? data.unread : [];

                if (!popupShownThisPage) {
                    const forced = importantUnread.find((item) => Number(item.priority) === 3 && item.requires_ack)
                        || importantUnread[0]
                        || unread[0];

                    if (forced) {
                        showForcedSystemModal(forced);
                    } else if (isInitialLoad && currentUnread > 0 && unread[0]?.title) {
                        showLiveSystemToast(unread[0].title, unread[0].body || '', !!unread[0].requires_ack);
                    } else if (!isInitialLoad && currentUnread > lastUnreadCount && unread[0]?.title) {
                        showLiveSystemToast(unread[0].title, unread[0].body || '', !!unread[0].requires_ack);
                    }
                }

                lastUnreadCount = currentUnread;
            } catch (error) {
                console.error('Systemmeddelanden kunde inte uppdateras', error);
            }
        }

        async function loadForcedPopups() {
            try {
                const response = await fetch(forcePopupUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();
                const messages = data.messages || [];

                if (!messages.length || popupShownThisPage) {
                    return;
                }

                showForcedSystemModal(messages[0]);
            } catch (error) {
                console.error('Popup för systemmeddelanden kunde inte laddas', error);
            }
        }

        function updateBadge(count) {
            const badge = document.querySelector('[data-system-message-count]')
                || document.querySelector('.topbar-notice-chip span')
                || document.querySelector('.guide-mobile-badge')
                || document.querySelector('.restaurant-mobile-badge');

            if (!badge) {
                return;
            }

            badge.textContent = count;
            badge.classList.toggle('d-none', count <= 0);
            badge.style.display = count > 0 ? 'inline-flex' : '';
        }

        function showLiveSystemToast(title, body, requiresAck) {
            const old = document.getElementById('live-system-toast');
            if (old) {
                old.remove();
            }

            const toast = document.createElement(tag);
            toast.id = 'live-system-toast';
            toast.innerHTML = '<' + tag + ' style="position:fixed;right:24px;bottom:24px;width:360px;max-width:calc(100vw - 32px);background:#fff7ed;color:#9a3412;border:1px solid #fdba74;border-radius:16px;box-shadow:0 18px 40px rgba(15,23,42,0.15);padding:16px 18px;z-index:9999;">'
                + '<' + tag + ' style="font-weight:800;margin-bottom:6px;"><i class="bi bi-bell-fill" style="margin-right:8px;"></i>' + escapeHtml(title) + '</' + tag + '>'
                + '<' + tag + ' style="font-size:0.92rem;line-height:1.45;margin-bottom:' + (requiresAck ? '8px' : '0') + ';">' + escapeHtml(body) + '</' + tag + '>'
                + (requiresAck ? '<' + tag + ' style="font-size:0.8rem;font-weight:700;">Kräver kvittering i systemet.</' + tag + '>' : '')
                + '</' + tag + '>';

            document.body.appendChild(toast);

            window.setTimeout(() => {
                const current = document.getElementById('live-system-toast');
                if (current) {
                    current.remove();
                }
            }, 8000);
        }

        function showForcedSystemModal(message) {
            if (!message?.id || popupShownThisPage) {
                return;
            }

            const storageKey = 'system-message-modal-' + message.id;
            const needsAck = !!message.requires_ack;

            if (!needsAck && sessionStorage.getItem(storageKey) === '1') {
                return;
            }

            if (document.getElementById('forced-system-modal')) {
                return;
            }

            popupShownThisPage = true;

            const modal = document.createElement(tag);
            modal.id = 'forced-system-modal';

            const ackButton = needsAck
                ? '<button type="button" id="forced-system-ack" style="border:none;background:linear-gradient(135deg,#38bdf8,#2563eb);color:#fff;padding:10px 14px;border-radius:12px;font-weight:700;cursor:pointer;">Kvittera</button>'
                : '';

            modal.innerHTML = '<' + tag + ' style="position:fixed;inset:0;background:rgba(15,23,42,0.55);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;">'
                + '<' + tag + ' style="width:100%;max-width:640px;background:#fff;border-radius:20px;padding:24px;box-shadow:0 24px 60px rgba(15,23,42,0.25);">'
                + '<' + tag + ' style="font-size:1.2rem;font-weight:800;margin-bottom:10px;">' + escapeHtml(message.title) + '</' + tag + '>'
                + '<' + tag + ' style="color:#334155;line-height:1.6;margin-bottom:20px;white-space:pre-line;">' + escapeHtml(message.body || '') + '</' + tag + '>'
                + '<' + tag + ' style="display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;">'
                + '<button type="button" id="forced-system-read" style="border:none;background:#e2e8f0;color:#0f172a;padding:10px 14px;border-radius:12px;font-weight:700;cursor:pointer;">Markera läst</button>'
                + ackButton
                + '</' + tag + '>'
                + '</' + tag + '>'
                + '</' + tag + '>';

            document.body.appendChild(modal);

            if (!needsAck) {
                sessionStorage.setItem(storageKey, '1');
            }

            const readBtn = document.getElementById('forced-system-read');
            if (readBtn) {
                readBtn.addEventListener('click', async function () {
                    await postSystemMessageAction(readUrl(message.id));
                    modal.remove();
                    popupShownThisPage = false;
                    refreshSystemMessagePanel(false);
                });
            }

            const ackBtn = document.getElementById('forced-system-ack');
            if (ackBtn) {
                ackBtn.addEventListener('click', async function () {
                    await postSystemMessageAction(acknowledgeUrl(message.id));
                    sessionStorage.setItem(storageKey, '1');
                    modal.remove();
                    popupShownThisPage = false;
                    refreshSystemMessagePanel(false);
                });
            }
        }

        async function postSystemMessageAction(url) {
            await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            });
        }

        function csrfToken() {
            const meta = document.querySelector('meta[name="csrf-token"]');

            return meta ? meta.getAttribute('content') : '';
        }

        function escapeHtml(text) {
            return String(text || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        document.addEventListener('DOMContentLoaded', async function () {
            await loadForcedPopups();
            await refreshSystemMessagePanel(true);
            window.setInterval(() => refreshSystemMessagePanel(false), 30000);
        });
    })();
</script>
@endverbatim
@endif
