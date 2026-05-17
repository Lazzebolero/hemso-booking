{{-- resources/views/partials/pwa.blade.php --}}

<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<meta name="theme-color" content="#0f172a">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Hemsö">
<link rel="apple-touch-icon" href="{{ asset('icons/pwa-icon-192.png') }}">

{{-- Offline POST queue: sync load (no defer) so submit handlers exist before first interaction; ?v= busts CDN/browser cache after deploy --}}
@php
    $__offlineQueuePath = public_path('js/offline-queue.js');
    $__offlineQueueVer = is_file($__offlineQueuePath) ? (string) filemtime($__offlineQueuePath) : '0';
@endphp
<script src="{{ asset('js/offline-queue.js') }}?v={{ $__offlineQueueVer }}"></script>

<style>
    .pwa-offline-banner {
        display: none;
        position: sticky;
        top: 0;
        z-index: 2000;
        padding: .65rem 1rem;
        background: #fff3cd;
        color: #664d03;
        border-bottom: 1px solid rgba(102, 77, 3, .2);
        font-size: .9rem;
        text-align: center;
    }

    body.is-offline .pwa-offline-banner {
        display: block;
    }

    /*
     * Offline banner + guide header are both sticky; top: 0 makes them overlap when
     * scrolling. Banner had z-index 2000 vs header 60 — banner stole clicks on nav icons.
     */
    body.is-offline .guide-header {
        top: 3rem;
        z-index: 2050;
    }

    @media (max-width: 767.98px) {
        .btn,
        .form-control,
        .form-select {
            min-height: 42px;
        }

        .side-link,
        .nav-link {
            min-height: 44px;
        }

        form[action*="clock-in"] .btn,
        form[action*="clock-out"] .btn {
            min-height: 52px;
            font-size: 1rem;
            font-weight: 700;
        }
    }
</style>

<script>
    (function () {
        function setOnlineState() {
            document.body.classList.toggle('is-offline', !navigator.onLine);
        }

        window.addEventListener('online', setOnlineState);
        window.addEventListener('offline', setOnlineState);
        document.addEventListener('DOMContentLoaded', setOnlineState);

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register(@json(asset('service-worker.js'))).catch(function () {});
            });
        }
    })();
</script>
