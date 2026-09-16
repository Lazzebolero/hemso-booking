@extends(\App\Support\GuideShell::layoutView())

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Färjetrafik</h2>
        <div class="page-subtitle">Hemsöleden · avgångar från Strinningen</div>
    </div>
</div>

<div class="page-card mb-3">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Datum</label>
            <input type="date" name="date" class="form-control" value="{{ $selectedDate->toDateString() }}">
        </div>

        <div class="col-md-4">
            <button class="btn btn-outline-secondary">Visa dag</button>
        </div>
    </form>
</div>

<div class="page-card mb-3 ferry-hero-card">
    <div class="small-muted mb-1">
        {{ $activeSnapshot['day_type_label'] ?? '' }}
        · {{ $selectedDate->translatedFormat('l j F Y') }}
        @if($activeSnapshot['traffic_live'] ?? false)
            · Live från Trafikverket
        @endif
    </div>

    @include('partials.ferry.traffic-alerts', [
        'alerts' => $activeSnapshot['traffic_alerts'] ?? [],
        'compact' => true,
    ])

    @if($activeSnapshot['next'] && !($activeSnapshot['next']['is_cancelled'] ?? false))
        <div class="ferry-hero-label">Nästa färja</div>
        <div class="ferry-hero-time">{{ $activeSnapshot['next']['time'] }}</div>
        <div class="ferry-hero-meta">
            @if(($activeSnapshot['next']['minutes_until'] ?? null) !== null)
                om {{ $activeSnapshot['next']['minutes_until'] }} min
            @else
                {{ $activeSnapshot['next']['status_label'] }}
            @endif
            @if($activeSnapshot['next']['requires_call'])
                · Kallelsetur
            @endif
            @if($activeSnapshot['next']['no_duplicates'] ?? false)
                · Dubbleringsturer: Nej
            @endif
            @if(!empty($activeSnapshot['next']['live_time']) && ($activeSnapshot['next']['live_time'] !== ($activeSnapshot['next']['scheduled_time'] ?? $activeSnapshot['next']['time'])))
                · planerad {{ $activeSnapshot['next']['scheduled_time'] ?? $activeSnapshot['next']['time'] }}
            @endif
        </div>
    @elseif(!empty($activeSnapshot['next']) && ($activeSnapshot['next']['is_cancelled'] ?? false))
        <div class="ferry-hero-label">Nästa färja</div>
        <div class="ferry-hero-meta">Nästa planerade avgång {{ $activeSnapshot['next']['scheduled_time'] ?? $activeSnapshot['next']['time'] }} är inställd.</div>
    @else
        <div class="ferry-hero-label">Nästa färja</div>
        <div class="ferry-hero-meta">Ingen fler avgång idag enligt tabellen.</div>
    @endif

    @if($activeSnapshot['last'])
        <div class="ferry-last-line mt-3">
            Senast avgått:
            <strong>{{ $activeSnapshot['last']['time'] }}</strong>
            @if($activeSnapshot['last']['from_live_api'] ?? false)
                <span class="small-muted">· Trafikverket</span>
            @endif
            @if($activeSnapshot['last']['is_extra'] ?? false)
                <span class="small-muted">· extratur</span>
            @endif
            @if(($activeSnapshot['last']['guest_arrival_relevant'] ?? false) && ($activeSnapshot['last']['is_extra'] ?? false))
                <div class="small-muted mt-1">
                    Gäster kan anlända om ca {{ $activeSnapshot['last']['guest_arrival_minutes_min'] }}–{{ $activeSnapshot['last']['guest_arrival_minutes_max'] }} min
                </div>
            @endif
        </div>
    @endif

    @if($commuteHint)
        <div class="alert alert-info mt-3 mb-0 py-2">{{ $commuteHint }}</div>
    @endif
</div>

<div class="page-card">
    <div class="section-title mb-3">Dagens avgångar</div>

    <div class="ferry-departure-list">
        @forelse($activeSnapshot['departures'] as $departure)
            <div @class([
                'ferry-departure-item',
                'is-'.$departure['status'] => !in_array($departure['status'], ['cancelled'], true),
                'is-cancelled' => ($departure['is_cancelled'] ?? false) || ($departure['status'] ?? '') === 'cancelled',
            ])>
                <div class="ferry-departure-time">
                    {{ $departure['live_time'] ?? $departure['time'] }}
                    @if(!empty($departure['live_time']) && $departure['live_time'] !== $departure['time'])
                        <div class="small-muted" style="font-size:.72rem;font-weight:600;">{{ $departure['time'] }}</div>
                    @endif
                </div>
                <div class="ferry-departure-meta">
                    <div class="fw-semibold">{{ $departure['status_label'] }}</div>
                    @include('partials.ferry.departure-notes', ['departure' => $departure])
                    @if(!empty($departure['traffic_message']))
                        <div class="small-muted">{{ $departure['traffic_message'] }}</div>
                    @endif
                </div>
            </div>
        @empty
            <div class="muted">Inga avgångar registrerade för denna dagtyp. Be admin uppdatera tidtabellen.</div>
        @endforelse
    </div>
</div>

<style>
.ferry-hero-card {
    background: linear-gradient(180deg, #fbfdff 0%, #f8fafc 100%);
}

.ferry-hero-label {
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 800;
    color: #64748b;
}

.ferry-hero-time {
    font-size: 2.2rem;
    font-weight: 900;
    line-height: 1.1;
    color: #0f172a;
}

.ferry-hero-meta,
.ferry-last-line {
    color: #475569;
    font-weight: 600;
}

.ferry-departure-list {
    display: grid;
    gap: 0.65rem;
}

.ferry-departure-item {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    padding: 0.8rem 0.9rem;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    background: #fff;
}

.ferry-departure-item.is-next {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.ferry-departure-item.is-passed {
    opacity: 0.72;
}

.ferry-departure-item.is-cancelled {
    border-color: #fca5a5;
    background: #fff1f2;
}

.ferry-departure-time {
    width: 58px;
    font-size: 1.05rem;
    font-weight: 800;
}
</style>

<script>
    setTimeout(function () {
        window.location.reload();
    }, 60000);
</script>
@endsection
