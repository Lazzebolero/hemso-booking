{{-- resources/views/partials/pwa-offline-banner.blade.php --}}

<div class="pwa-offline-banner" data-offline-banner>
    <div class="pwa-offline-banner-inner">
        <span data-offline-banner-offline-text>
            <i class="bi bi-wifi-off me-1"></i>
            Du är offline. Ändringar sparas lokalt och skickas när nätet är tillbaka.
        </span>
        <span data-offline-banner-pending-text class="d-none">
            <i class="bi bi-cloud-upload me-1"></i>
            <span data-offline-banner-pending-label>Väntande offline-åtgärder ska skickas.</span>
        </span>
        <button type="button" class="btn btn-sm btn-warning ms-2 d-none" data-offline-sync-now>
            Synka nu
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary ms-1 d-none" data-offline-skip-pending>
            Hoppa över
        </button>
    </div>
</div>
