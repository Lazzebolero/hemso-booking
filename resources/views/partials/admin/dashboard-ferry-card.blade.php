@php
    $snapshot = $snapshot ?? [];
    $next = $snapshot['next'] ?? null;
    $last = $snapshot['last'] ?? null;
@endphp

<div class="page-card compact-card mb-3">
    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-3">
        <div>
            <div class="section-title mb-1">Hemsöleden · Strinningen</div>
            <div class="small-muted">Avgångar till ön enligt tidtabell.</div>
        </div>

        @if(Route::has('ferry-schedule.index'))
            <a href="{{ route('ferry-schedule.index') }}" class="btn btn-sm btn-outline-secondary">
                Idag
            </a>
        @endif
    </div>

    <div class="ferry-dashboard-summary">
        <div class="ferry-dashboard-block">
            <div class="ferry-dashboard-label">Senast avgått</div>
            <div class="ferry-dashboard-value">{{ $last['time'] ?? '-' }}</div>
        </div>

        <div class="ferry-dashboard-block">
            <div class="ferry-dashboard-label">Nästa avgång</div>
            <div class="ferry-dashboard-value">
                @if($next)
                    {{ $next['time'] }}
                    @if(($next['minutes_until'] ?? null) !== null)
                        <span class="small-muted">· om {{ $next['minutes_until'] }} min</span>
                    @endif
                @else
                    -
                @endif
            </div>
        </div>
    </div>
</div>

<style>
.ferry-dashboard-summary {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
}

.ferry-dashboard-block {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 0.85rem;
    background: linear-gradient(180deg, #fbfdff 0%, #f8fafc 100%);
}

.ferry-dashboard-label {
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 800;
    color: #64748b;
    margin-bottom: 0.25rem;
}

.ferry-dashboard-value {
    font-size: 1.15rem;
    font-weight: 800;
    color: #0f172a;
}
</style>
