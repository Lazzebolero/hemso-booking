@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();

    $sizeLabel = \App\Http\Controllers\Admin\SystemLogController::formatBytes($size);

    $logLines = trim($content) !== ''
        ? preg_split('/\r\n|\r|\n/', $content)
        : [];

    $errorCount = collect($logLines)->filter(fn ($line) =>
        str_contains($line, '.ERROR') ||
        str_contains($line, '.CRITICAL') ||
        str_contains($line, '.ALERT') ||
        str_contains($line, '.EMERGENCY')
    )->count();

    $warningCount = collect($logLines)->filter(fn ($line) =>
        str_contains($line, '.WARNING')
    )->count();

    $autoRefresh = request()->boolean('auto_refresh');
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Systemlogg</h2>
        <div class="page-subtitle">
            Visar senaste raderna från Laravel-loggen. Fel och varningar markeras automatiskt.
        </div>
    </div>

    <div class="page-actions">
        @if(Route::has('admin.system-health.index'))
            <a href="{{ route('admin.system-health.index', absolute: false) }}" class="btn btn-outline-secondary">
                Systemhälsa
            </a>
        @endif
    </div>
</div>

<div class="log-summary-grid mb-4">
    <div class="log-summary-card">
        <div class="summary-label">Loggfil</div>
        <div class="summary-value">{{ $exists ? 'Finns' : 'Saknas' }}</div>
    </div>

    <div class="log-summary-card">
        <div class="summary-label">Storlek</div>
        <div class="summary-value">{{ $exists ? $sizeLabel : '-' }}</div>
    </div>

    <div class="log-summary-card">
        <div class="summary-label">Senast ändrad</div>
        <div class="summary-value">{{ $modified ?: '-' }}</div>
    </div>

    <div class="log-summary-card">
        <div class="summary-label">Visar rader</div>
        <div class="summary-value">{{ $lines }}</div>
    </div>

    <div class="log-summary-card {{ $errorCount > 0 ? 'summary-danger' : '' }}">
        <div class="summary-label">Fel i visad logg</div>
        <div class="summary-value">{{ $errorCount }}</div>
    </div>

    <div class="log-summary-card {{ $warningCount > 0 ? 'summary-warning' : '' }}">
        <div class="summary-label">Varningar i visad logg</div>
        <div class="summary-value">{{ $warningCount }}</div>
    </div>
</div>

<div class="page-card mb-4">
    <form method="GET" action="{{ route($prefix . '.system-logs.index', absolute: false) }}" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Antal rader</label>
            <select name="lines" class="form-select">
                @foreach([50, 100, 200, 500, 1000] as $option)
                    <option value="{{ $option }}" @selected((int) $lines === $option)>
                        Senaste {{ $option }} raderna
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-8">
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <button type="submit" class="btn btn-primary">
                    Visa logg
                </button>

                <a href="{{ route($prefix . '.system-logs.index', ['lines' => $lines], false) }}" class="btn btn-outline-secondary">
                    Uppdatera
                </a>

                <a href="{{ route($prefix . '.system-logs.index', ['lines' => $lines, 'auto_refresh' => $autoRefresh ? 0 : 1], false) }}"
                   class="btn {{ $autoRefresh ? 'btn-success' : 'btn-outline-secondary' }}">
                    @if($autoRefresh)
                        Auto-refresh på
                    @else
                        Auto-refresh av
                    @endif
                </a>

                <button type="button" class="btn btn-outline-secondary" onclick="scrollToFirstError()">
                    Hoppa till första fel
                </button>
            </div>
        </div>
    </form>

    @if($autoRefresh)
        <div class="auto-refresh-note mt-3">
            Sidan uppdateras automatiskt var 10:e sekund.
        </div>
    @endif
</div>

<div class="page-card">
    <div class="section-title mb-3">Laravel-logg</div>

    @if(! $exists)
        <div class="empty-state">
            Ingen loggfil hittades på <code>storage/logs/laravel.log</code>.
        </div>
    @elseif(trim($content) === '')
        <div class="empty-state">
            Loggfilen finns men är tom.
        </div>
    @else
        <div class="log-output" id="logOutput">
            @foreach($logLines as $line)
                @php
                    $lineClass = 'log-line';

                    if (
                        str_contains($line, '.ERROR') ||
                        str_contains($line, '.CRITICAL') ||
                        str_contains($line, '.ALERT') ||
                        str_contains($line, '.EMERGENCY')
                    ) {
                        $lineClass .= ' log-line-error';
                    } elseif (str_contains($line, '.WARNING')) {
                        $lineClass .= ' log-line-warning';
                    } elseif (str_contains($line, '.INFO')) {
                        $lineClass .= ' log-line-info';
                    }
                @endphp

                <div class="{{ $lineClass }}">{{ $line }}</div>
            @endforeach
        </div>
    @endif
</div>

<style>
.log-summary-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 1rem;
}

.log-summary-card {
    background: #ffffff;
    border: 1px solid var(--brand-line-soft);
    border-radius: 14px;
    padding: 1rem;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
}

.summary-label {
    color: #64748b;
    font-size: 0.78rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.summary-value {
    margin-top: 0.25rem;
    font-size: 1.15rem;
    font-weight: 900;
}

.summary-danger {
    border-left: 5px solid #ef4444;
}

.summary-danger .summary-value {
    color: #b91c1c;
}

.summary-warning {
    border-left: 5px solid #f59e0b;
}

.summary-warning .summary-value {
    color: #b45309;
}

.log-output {
    background: #0f172a;
    color: #e5e7eb;
    border-radius: 14px;
    padding: 1rem;
    overflow-x: auto;
    white-space: pre-wrap;
    word-break: break-word;
    font-size: 0.82rem;
    line-height: 1.55;
    max-height: 70vh;
}

.log-line {
    padding: 0.08rem 0.35rem;
    border-radius: 6px;
    margin-bottom: 0.08rem;
}

.log-line-error {
    background: rgba(239, 68, 68, 0.22);
    color: #fecaca;
    border-left: 4px solid #ef4444;
}

.log-line-warning {
    background: rgba(245, 158, 11, 0.18);
    color: #fde68a;
    border-left: 4px solid #f59e0b;
}

.log-line-info {
    color: #bfdbfe;
}

.empty-state {
    border: 1px dashed var(--brand-line-soft);
    border-radius: 12px;
    padding: 1rem;
    background: #f8fafc;
    color: #64748b;
}

.auto-refresh-note {
    border: 1px solid #bbf7d0;
    background: #ecfdf5;
    color: #047857;
    border-radius: 12px;
    padding: 0.75rem 0.9rem;
    font-weight: 700;
}

@media (max-width: 1200px) {
    .log-summary-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 700px) {
    .log-summary-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function scrollToFirstError() {
    const firstError = document.querySelector('.log-line-error');

    if (firstError) {
        firstError.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }
}

@if($autoRefresh)
    setTimeout(function () {
        window.location.reload();
    }, 10000);
@endif
</script>
@endsection