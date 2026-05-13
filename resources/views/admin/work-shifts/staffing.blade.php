@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();

    $groupLabels = [
        'admin' => 'Admin',
        'host' => 'Värd',
        'guide' => 'Guide',
    ];
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Dagens personal</h2>
        <div class="page-subtitle">
            Bemanning grupperad efter roll och restaurangfunktion.
        </div>
    </div>

    <div class="page-actions">
        <a href="{{ route($prefix . '.work-shifts.index', ['date' => $selectedDate->toDateString()]) }}" class="btn btn-outline-secondary">
            Dagvy
        </a>

        <a href="{{ route($prefix . '.work-shifts.person') }}" class="btn btn-outline-secondary">
            Personvy
        </a>
    </div>
</div>

<div class="page-card mb-4">
    <form method="GET" action="{{ route($prefix . '.work-shifts.staffing') }}" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Datum</label>
            <input
                type="date"
                name="date"
                class="form-control"
                value="{{ $selectedDate->toDateString() }}"
            >
        </div>

        <div class="col-md-8">
            <div class="d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">
                    Visa dag
                </button>

                <a href="{{ route($prefix . '.work-shifts.staffing', ['date' => now()->toDateString()]) }}" class="btn btn-outline-secondary">
                    Idag
                </a>

                <a href="{{ route($prefix . '.work-shifts.staffing', ['date' => $selectedDate->copy()->subDay()->toDateString()]) }}" class="btn btn-outline-secondary">
                    Föregående dag
                </a>

                <a href="{{ route($prefix . '.work-shifts.staffing', ['date' => $selectedDate->copy()->addDay()->toDateString()]) }}" class="btn btn-outline-secondary">
                    Nästa dag
                </a>
            </div>
        </div>
    </form>
</div>

<div class="staffing-summary-grid mb-4">
    <div class="staffing-summary-card">
        <div class="summary-label">Totalt</div>
        <div class="summary-value">{{ $shifts->count() }}</div>
    </div>

    <div class="staffing-summary-card">
        <div class="summary-label">Guider</div>
        <div class="summary-value">{{ $shifts->where('shift_role', 'guide')->count() }}</div>
    </div>

    <div class="staffing-summary-card">
        <div class="summary-label">Värdar</div>
        <div class="summary-value">{{ $shifts->where('shift_role', 'host')->count() }}</div>
    </div>

    <div class="staffing-summary-card">
        <div class="summary-label">Restaurang</div>
        <div class="summary-value">{{ $shifts->where('shift_role', 'restaurant')->count() }}</div>
    </div>
</div>

<div class="page-card">
    <div class="section-title mb-3">
        Personal {{ $selectedDate->format('Y-m-d') }}
    </div>

    @forelse($groupedShifts as $groupKey => $groupShifts)
        @php
            if (str_starts_with($groupKey, 'restaurant:')) {
                $functionKey = str_replace('restaurant:', '', $groupKey);
                $groupTitle = 'Restaurang – ' . ($restaurantFunctions[$functionKey] ?? ucfirst($functionKey));
            } else {
                $groupTitle = $groupLabels[$groupKey] ?? ($shiftRoles[$groupKey] ?? ucfirst($groupKey));
            }
        @endphp

        <div class="staffing-group">
            <div class="staffing-group-header">
                <div>
                    <div class="staffing-group-title">{{ $groupTitle }}</div>
                    <div class="small-muted">{{ $groupShifts->count() }} personer/pass</div>
                </div>
            </div>

            <div class="staffing-list">
                @foreach($groupShifts as $shift)
                    <div class="staffing-row">
                        <div>
                            <div class="staffing-name">{{ $shift->user?->name ?? 'Okänd användare' }}</div>

                            <div class="small-muted">
                                {{ substr($shift->start_time, 0, 5) }}
                                –
                                {{ $shift->end_time ? substr($shift->end_time, 0, 5) : '--:--' }}
                                · {{ $statuses[$shift->status] ?? ucfirst($shift->status) }}
                            </div>

                            @if($shift->notes)
                                <div class="small-muted mt-1">{{ $shift->notes }}</div>
                            @endif
                        </div>

                        <div class="staffing-actions">
                            <a href="{{ route($prefix . '.work-shifts.edit', $shift) }}" class="btn btn-sm btn-outline-secondary">
                                Redigera
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="empty-state">
            Ingen personal är schemalagd denna dag.
        </div>
    @endforelse
</div>

<style>
.staffing-summary-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
}

.staffing-summary-card {
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
    font-size: 2rem;
    font-weight: 800;
    line-height: 1;
}

.staffing-group {
    border-top: 1px solid var(--brand-line-soft);
    padding-top: 1rem;
    margin-top: 1rem;
}

.staffing-group:first-of-type {
    border-top: 0;
    margin-top: 0;
    padding-top: 0;
}

.staffing-group-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    gap: 1rem;
    margin-bottom: 0.75rem;
}

.staffing-group-title {
    font-weight: 800;
    font-size: 1rem;
}

.staffing-list {
    display: grid;
    gap: 0.65rem;
}

.staffing-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    border: 1px solid var(--brand-line-soft);
    border-radius: 12px;
    padding: 0.85rem 0.95rem;
    background: #ffffff;
}

.staffing-name {
    font-weight: 800;
}

.staffing-actions {
    display: flex;
    justify-content: end;
    gap: 0.5rem;
}

.empty-state {
    border: 1px dashed var(--brand-line-soft);
    border-radius: 12px;
    padding: 1rem;
    background: #f8fafc;
    color: #64748b;
}

@media (max-width: 900px) {
    .staffing-summary-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 650px) {
    .staffing-summary-grid {
        grid-template-columns: 1fr;
    }

    .staffing-row {
        align-items: stretch;
        flex-direction: column;
    }

    .staffing-actions {
        justify-content: start;
    }
}
</style>
@endsection