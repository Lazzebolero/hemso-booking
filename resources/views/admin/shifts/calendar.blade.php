@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Schema och kalender</h2>
        <div class="page-subtitle">Översikt över guidepass, turer, möten och blockerade tider.</div>
    </div>

    <div class="page-actions">
        <a href="{{ route('admin.shifts.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-list-ul me-2"></i>Listvy
        </a>

        <a href="{{ route('admin.shifts.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Ny schemapost
        </a>
    </div>
</div>

<div class="page-card compact-card mb-3">
    <form method="GET" class="calendar-filter-grid">
        <div>
            <label class="form-label">Månad</label>
            <input type="month" name="month" value="{{ $month }}" class="form-control">
        </div>

        <div class="calendar-filter-actions">
            <button class="btn btn-primary">
                <i class="bi bi-calendar-week me-2"></i>Visa
            </button>
        </div>
    </form>
</div>

<div class="page-card compact-card">
    <div class="calendar-weekdays mb-3">
        @foreach(['Mån', 'Tis', 'Ons', 'Tor', 'Fre', 'Lör', 'Sön'] as $day)
            <div class="calendar-weekday">{{ $day }}</div>
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

                        $startTime = $shift->start_time ? substr($shift->start_time, 0, 5) : '--';
                        $endTime = $shift->end_time ? substr($shift->end_time, 0, 5) : '--';
                    @endphp

                    <a href="{{ route('admin.shifts.edit', $shift) }}" class="calendar-event {{ $typeClass }}">
                        <div class="calendar-event-time">{{ $startTime }}–{{ $endTime }}</div>
                        <div class="calendar-event-title">{{ $shift->title }}</div>
                        <div class="calendar-event-guide">{{ $shift->guide?->name }}</div>
                    </a>
                @endforeach
            </div>
            @php $cursor->addDay(); @endphp
        @endwhile
    </div>
</div>

<style>
.calendar-filter-grid {
    display: grid;
    grid-template-columns: 220px auto;
    gap: 0.9rem;
    align-items: end;
}
.calendar-filter-actions {
    display: flex;
    align-items: end;
}
.calendar-weekdays {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 0.85rem;
}
.calendar-weekday {
    text-align: center;
    font-size: 0.82rem;
    font-weight: 800;
    color: var(--text-soft);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 0.85rem;
}
.calendar-cell {
    min-height: 190px;
    background: #f8fafc;
    border: 1px solid var(--brand-line-soft);
    border-radius: 16px;
    padding: 0.75rem;
}
.calendar-cell.empty {
    background: transparent;
    border: none;
}
.calendar-date {
    font-weight: 800;
    margin-bottom: 0.65rem;
    color: var(--text-main);
}
.calendar-event {
    display: block;
    border-radius: 12px;
    padding: 0.6rem 0.7rem;
    margin-bottom: 0.55rem;
    font-size: 0.86rem;
    text-decoration: none;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.calendar-event:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
}
.calendar-event-time {
    font-size: 0.75rem;
    font-weight: 800;
    margin-bottom: 0.18rem;
}
.calendar-event-title {
    font-weight: 700;
    line-height: 1.35;
}
.calendar-event-guide {
    font-size: 0.76rem;
    margin-top: 0.18rem;
    opacity: 0.8;
}
.event-tour { background: rgba(37,99,235,0.12); color: #1d4ed8; }
.event-work { background: rgba(22,163,74,0.12); color: #166534; }
.event-meeting { background: rgba(124,58,237,0.12); color: #6d28d9; }
.event-maintenance { background: rgba(245,158,11,0.15); color: #92400e; }
.event-blocked { background: rgba(220,38,38,0.12); color: #991b1b; }

@media (max-width: 1200px) {
    .calendar-grid,
    .calendar-weekdays {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}
@media (max-width: 900px) {
    .calendar-grid,
    .calendar-weekdays {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .calendar-filter-grid {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 576px) {
    .calendar-grid,
    .calendar-weekdays {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection