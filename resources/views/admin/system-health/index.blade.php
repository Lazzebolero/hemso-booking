@extends('layouts.app')

@section('content')
@php
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

<div class="staff-page-stack">
    @include('partials.ui.flash-messages')

    <x-ui.page-header
        title="Systemhälsa"
        subtitle="Varje ruta nedan är en egen kontroll. Läs sammanfattningen först, sedan detaljerna under."
        icon="bi-heart-pulse"
    >
        <x-slot:actions>
            <form method="POST" action="{{ route('admin.system-health.test-mail') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-envelope-check me-1"></i>Skicka testmail
                </button>
            </form>
            <a href="{{ route('admin.system-health.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-arrow-clockwise me-1"></i>Uppdatera
            </a>
            @if(Route::has('admin.system-logs.index'))
                <a href="{{ route('admin.system-logs.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-file-earmark-text me-1"></i>Systemlogg
                </a>
            @endif
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
                <i class="bi bi-speedometer2 me-1"></i>Dashboard
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="page-card health-tip-card">
        <div class="section-title mb-2">Deploy & röktest</div>
        <p class="small-muted mb-0">
            Efter deploy: kör <code>php artisan deploy:smoke --strict</code> på servern.
            Se <code>DEPLOY.md</code> i projektroten.
        </p>
    </div>

    <div class="health-overview {{ $overallClass }}">
        <div>
            <div class="health-overview-label">Övergripande status</div>
            <div class="health-overview-title">{{ $overallLabel }}</div>
        </div>
        <div class="health-overview-time">
            Kontrollerad {{ $checkedAt->format('Y-m-d H:i:s') }}
        </div>
    </div>

    @if($history->isNotEmpty())
        <section class="page-card">
            <div class="section-title mb-3">Historik</div>
            <p class="small-muted mb-3">
                Senaste kontroller sparas automatiskt (max 30 dagar). Nuvarande kontroll visas nedan.
            </p>
            <div class="table-responsive-modern">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Tid</th>
                            <th>Status</th>
                            <th>Kontroller</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($history as $snapshot)
                            @php
                                $rowClass = match($snapshot->overall_status) {
                                    'ok' => 'health-history-ok',
                                    'warning' => 'health-history-warning',
                                    'error' => 'health-history-error',
                                    default => '',
                                };
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td class="text-nowrap">{{ $snapshot->checked_at?->format('Y-m-d H:i') }}</td>
                                <td>
                                    <span class="health-badge health-badge-inline">{{ $snapshot->statusLabel() }}</span>
                                </td>
                                <td>{{ $snapshot->checkSummary() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <section class="page-card">
        <div class="section-title mb-2">Extern övervakning (JSON)</div>
        @if($monitorConfigured)
            <p class="small-muted mb-2">
                Anrop för uptime-tjänster (token i <code>SYSTEM_HEALTH_MONITOR_TOKEN</code>):
            </p>
            <p class="mb-2"><code class="user-select-all">{{ $monitorUrl }}</code></p>
            <p class="small-muted mb-2">
                Header: <code>Authorization: Bearer &lt;token&gt;</code>
                eller query: <code>?token=&lt;token&gt;</code>
            </p>
            <p class="small-muted mb-0">
                Inloggad admin:
                <a href="{{ route('admin.system-health.status-json') }}" target="_blank" rel="noopener">status.json</a>
            </p>
        @else
            <p class="small-muted mb-0">
                Sätt <code>SYSTEM_HEALTH_MONITOR_TOKEN</code> i <code>.env</code> för att aktivera
                <code>/health/monitor</code>. Inloggad admin kan öppna
                <a href="{{ route('admin.system-health.status-json') }}" target="_blank" rel="noopener">status.json</a>.
            </p>
        @endif
    </section>

    <div class="health-check-list">
        @foreach($checks as $check)
            @include('partials.system-health.check-card', ['check' => $check])
        @endforeach
    </div>
</div>
@endsection
