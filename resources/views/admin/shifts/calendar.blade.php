@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="mb-1">Schema och kalender</h2>
        <div class="muted">Översikt över guidepass, turer, möten och blockerade tider.</div>
    </div>

    <form method="GET" class="stats-filter-card p-3">
        <div class="d-flex gap-3 align-items-end flex-wrap">
            <div>
                <label class="form-label">Månad</label>
                <input type="month" name="month" value="{{ $month }}" class="form-control">
            </div>
            <button class="btn btn-primary">
                <i class="bi bi-calendar-week me-2"></i>Visa
            </button>
        </div>
    </form>
</div>

<div class="page-card">
    <div class="row g-3 mb-3">
        @foreach(['Mån', 'Tis', 'Ons', 'Tor', 'Fre', 'Lör', 'Sön'] as $day)
            <div class="col">
                <div class="text-center fw-bold muted">{{ $day }}</div>
            </div>
        @endforeach
    </div>

    <div class="calendar-grid">
        @php
            $cursor = $start->copy();
            $startOffset = $cursor->dayOfWeekIso - 1;
        @endphp

        @for($i = 0; $i < $startOffset; $i++)
            <div class="calendar-cell empty"></div>
        @endfor

        @while($cursor <= $end)
            <div class="calendar-cell">
                <div class="calendar-date">{{ $cursor->format('j') }}</div>

                @foreach(($shifts[$cursor->toDateString()] ?? collect()) as $shift)
                    @php
                        $typeClass = match($shift->shift_type) {
                            'tour' => 'event-tour',
                            'meeting' => 'event-meeting',
                            'maintenance' => 'event-maintenance',
                            'blocked' => 'event-blocked',
                            default => 'event-work',
                        };
                    @endphp
                    <div class="calendar-event {{ $typeClass }}">
                        <div class="small fw-semibold">{{ $shift->start_time }}-{{ $shift->end_time }}</div>
                        <div>{{ $shift->title }}</div>
                        <div class="small opacity-75">{{ $shift->guide?->name }}</div>
                    </div>
                @endforeach
            </div>
            @php $cursor->addDay(); @endphp
        @endwhile
    </div>
</div>

<style>
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 1rem;
}
.calendar-cell {
    min-height: 180px;
    background: rgba(248,250,252,0.9);
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 0.75rem;
}
.calendar-cell.empty {
    background: transparent;
    border: none;
}
.calendar-date {
    font-weight: 700;
    margin-bottom: 0.6rem;
}
.calendar-event {
    border-radius: 12px;
    padding: 0.55rem 0.65rem;
    margin-bottom: 0.55rem;
    font-size: 0.9rem;
}
.event-tour { background: rgba(37,99,235,0.12); color: #1d4ed8; }
.event-work { background: rgba(22,163,74,0.12); color: #166534; }
.event-meeting { background: rgba(124,58,237,0.12); color: #6d28d9; }
.event-maintenance { background: rgba(245,158,11,0.15); color: #92400e; }
.event-blocked { background: rgba(220,38,38,0.12); color: #991b1b; }

@media (max-width: 992px) {
    .calendar-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 576px) {
    .calendar-grid { grid-template-columns: 1fr; }
}
</style>
@endsection
