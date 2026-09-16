@props([
    'snapshot' => [],
    'timetableUrl' => null,
    'variant' => 'app',
])

@php
    $ferryNext = $snapshot['next'] ?? null;
    $ferryLast = $snapshot['last'] ?? null;
    $wrapperClass = ($variant ?? 'app') === 'kiosk'
        ? 'panel ferry-status-card'
        : 'page-card compact-card mb-3 ferry-status-card';
@endphp

<div class="{{ $wrapperClass }}">
    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-3 ferry-status-card-head">
        <div>
            <div class="section-title mb-1">Hemsöleden · Strinningen</div>
            <div class="small-muted board-subtitle-inline">
                Avgångar till ön enligt tidtabell
                @if($snapshot['traffic_live'] ?? false)
                    · live från Trafikverket
                @endif
            </div>
        </div>

        @if($timetableUrl)
            <a href="{{ $timetableUrl }}" class="btn btn-sm btn-outline-secondary ferry-status-card-link">
                Tidtabell
            </a>
        @endif
    </div>

    @include('partials.ferry.traffic-alerts', [
        'alerts' => $snapshot['traffic_alerts'] ?? [],
        'compact' => true,
    ])

    <div class="ferry-dashboard-summary">
        <div class="ferry-dashboard-block">
            <div class="ferry-dashboard-label">Senast avgått</div>
            <div class="ferry-dashboard-value">{{ $ferryLast['time'] ?? '-' }}</div>
            @if($ferryLast['from_live_api'] ?? false)
                <div class="small-muted">enligt Trafikverket</div>
            @endif
            @if($ferryLast['is_extra'] ?? false)
                <div class="small-muted">Extratur</div>
            @endif
        </div>

        <div class="ferry-dashboard-block">
            <div class="ferry-dashboard-label">Nästa avgång</div>
            <div class="ferry-dashboard-value">
                @if($ferryNext && !($ferryNext['is_cancelled'] ?? false))
                    {{ $ferryNext['time'] }}
                    @if(($ferryNext['minutes_until'] ?? null) !== null)
                        <span class="small-muted">· om {{ $ferryNext['minutes_until'] }} min</span>
                    @endif
                    @if(($ferryNext['delay_minutes'] ?? null) > 0)
                        <span class="small-muted">· försenad</span>
                    @endif
                    @include('partials.ferry.departure-notes', ['departure' => $ferryNext])
                @elseif($ferryNext['is_cancelled'] ?? false)
                    <span class="text-danger">Inställd</span>
                @else
                    -
                @endif
            </div>
        </div>
    </div>
</div>

@once
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

    .ferry-status-card .section-title {
        font-size: 1rem;
        font-weight: 800;
        margin: 0;
    }

    .ferry-status-card.panel {
        margin-bottom: 18px;
    }

    .ferry-status-card-link {
        text-decoration: none;
    }

    @media (max-width: 700px) {
        .ferry-dashboard-summary {
            grid-template-columns: 1fr;
        }
    }
</style>
@endonce
