@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
    $networkStatusUrl = config('services.system_health.network_status_url');

    $overallClass = match($overallStatus) {
        'ok' => 'health-ok',
        'warning' => 'health-warning',
        'error' => 'health-error',
        default => 'health-warning',
    };

    $overallLabel = match($overallStatus) {
        'ok' => 'Systemet ser bra ut',
        'warning' => 'Systemet har varningar',
        'error' => 'Systemet har fel',
        default => 'Okänd status',
    };
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Systemhälsa</h2>
        <div class="page-subtitle">
            Snabb kontroll av driftstatus, databas, mail, scheduler och loggar.
        </div>
    </div>

    <div class="page-actions">
        @if(filled($networkStatusUrl))
            <a href="{{ $networkStatusUrl }}" class="btn btn-outline-primary" target="_blank" rel="noopener noreferrer">
                <i class="bi bi-router me-1"></i>Nätstatus
            </a>
        @endif
        <a href="{{ route($prefix . '.dashboard') }}" class="btn btn-outline-secondary">
            Till dashboard
        </a>
    </div>
</div>

@include('partials.ui.flash-messages')

@if(filled($networkStatusUrl))
    <div class="page-card compact-card mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <div class="section-title mb-1">Driftinformation nät</div>
                <div class="small-muted mb-0">
                    Extern övervakning av nätet via Comun (Hemsö fästning).
                </div>
            </div>
            <a href="{{ $networkStatusUrl }}" class="btn btn-primary" target="_blank" rel="noopener noreferrer">
                Öppna nätstatus
                <i class="bi bi-box-arrow-up-right ms-1"></i>
            </a>
        </div>
    </div>
@endif

<div class="health-overview {{ $overallClass }} mb-4">
    <div>
        <div class="health-overview-label">Övergripande status</div>
        <div class="health-overview-title">{{ $overallLabel }}</div>
    </div>

    <div class="health-overview-time">
        Kontrollerad {{ now()->format('Y-m-d H:i:s') }}
    </div>
</div>

<div class="health-grid">
    @foreach($checks as $check)
        @php
            $statusClass = match($check['status']) {
                'ok' => 'health-ok',
                'warning' => 'health-warning',
                'error' => 'health-error',
                default => 'health-warning',
            };

            $statusLabel = match($check['status']) {
                'ok' => 'OK',
                'warning' => 'Varning',
                'error' => 'Fel',
                default => 'Okänd',
            };
        @endphp

        <div class="health-card {{ $statusClass }}">
            <div class="health-card-header">
                <div>
                    <div class="health-card-title">{{ $check['title'] }}</div>
                    <div class="health-card-message">{{ $check['message'] }}</div>
                </div>

                <span class="health-badge">{{ $statusLabel }}</span>
            </div>

            <div class="health-items">
                @foreach($check['items'] as $label => $value)
                    <div class="health-item">
                        <div class="health-item-label">{{ $label }}</div>
                        <div class="health-item-value">{{ $value }}</div>
                    </div>
                @endforeach
            </div>

            @if(($canRunMigrations ?? false) && ($check['title'] ?? '') === 'Migrationer')
                <form method="POST" action="{{ route('admin.system-health.migrate') }}" class="mt-3" onsubmit="return confirm('Köra väntande databasmigrationer nu?')">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        Kör väntande migrationer
                    </button>
                </form>
            @endif

            @if(! empty($check['details']))
                <div class="health-details">
                    <div class="health-details-title">Senaste fel</div>
                    @foreach($check['details'] as $detail)
                        <div class="health-detail">
                            <div class="health-detail-meta">
                                <span class="health-detail-command">{{ $detail['command'] }}</span>
                                <span class="health-detail-time">{{ $detail['failed_at_label'] }}</span>
                            </div>
                            @if(($detail['message'] ?? '') !== '')
                                <div class="health-detail-message">{{ $detail['message'] }}</div>
                            @endif
                        </div>
                    @endforeach
                    @if(Route::has($prefix . '.system-logs.index'))
                        <a href="{{ route($prefix . '.system-logs.index') }}" class="health-details-link">
                            Öppna systemlogg
                        </a>
                    @endif
                </div>
            @endif
        </div>
    @endforeach
</div>

<style>
.alert-success,
.alert-danger {
    white-space: pre-wrap;
}

.health-overview {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    border-radius: 16px;
    padding: 1.1rem 1.25rem;
    border: 1px solid var(--brand-line-soft);
    background: #fff;
}

.health-overview-label {
    color: #64748b;
    font-weight: 800;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.health-overview-title {
    font-size: 1.35rem;
    font-weight: 900;
    margin-top: 0.15rem;
}

.health-overview-time {
    color: #64748b;
    font-weight: 700;
    white-space: nowrap;
}

.health-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}

.health-card {
    background: #fff;
    border: 1px solid var(--brand-line-soft);
    border-radius: 16px;
    padding: 1rem;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
}

.health-card-header {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: start;
    margin-bottom: 1rem;
}

.health-card-title {
    font-weight: 900;
    font-size: 1.05rem;
}

.health-card-message {
    color: #64748b;
    font-size: 0.92rem;
    margin-top: 0.2rem;
}

.health-badge {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 0.3rem 0.65rem;
    font-size: 0.78rem;
    font-weight: 900;
    white-space: nowrap;
}

.health-items {
    display: grid;
    gap: 0.5rem;
}

.health-item {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    border-top: 1px solid #edf2f7;
    padding-top: 0.5rem;
}

.health-item-label {
    color: #64748b;
    font-weight: 700;
}

.health-item-value {
    font-weight: 800;
    text-align: right;
}

.health-details {
    margin-top: 0.85rem;
    padding-top: 0.75rem;
    border-top: 1px solid #edf2f7;
    display: grid;
    gap: 0.65rem;
}

.health-details-title {
    color: #64748b;
    font-weight: 800;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.health-detail {
    background: #fff7ed;
    border: 1px solid #fed7aa;
    border-radius: 10px;
    padding: 0.55rem 0.7rem;
}

.health-detail-meta {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    align-items: baseline;
}

.health-detail-command {
    font-weight: 800;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 0.88rem;
}

.health-detail-time {
    color: #64748b;
    font-weight: 700;
    font-size: 0.82rem;
    white-space: nowrap;
}

.health-detail-message {
    color: #9a3412;
    font-size: 0.88rem;
    margin-top: 0.25rem;
}

.health-details-link {
    font-weight: 800;
    font-size: 0.88rem;
}

.health-ok {
    border-left: 5px solid #22c55e;
}

.health-ok .health-badge {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #bbf7d0;
}

.health-warning {
    border-left: 5px solid #f59e0b;
}

.health-warning .health-badge {
    background: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
}

.health-error {
    border-left: 5px solid #ef4444;
}

.health-error .health-badge {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

@media (max-width: 900px) {
    .health-grid {
        grid-template-columns: 1fr;
    }

    .health-overview {
        align-items: start;
        flex-direction: column;
    }

    .health-item {
        align-items: start;
        flex-direction: column;
        gap: 0.2rem;
    }

    .health-item-value {
        text-align: left;
    }

    .health-detail-meta {
        flex-direction: column;
        gap: 0.15rem;
    }
}
</style>
@endsection