@php
    $prefix = \App\Support\ActiveRole::routePrefix();
    $roleLabels = [
        'guide' => 'Guider',
        'host' => 'Värdar',
        'restaurant' => 'Restaurang',
        'admin' => 'Admin',
    ];
@endphp

<div class="page-card">
    <div class="section-title mb-3">
        Vecka {{ $weekDays->first()->format('Y-m-d') }} – {{ $weekDays->last()->format('Y-m-d') }}
    </div>

    @foreach($weekDays as $day)
        @php
            $dayShiftsFlat = $shifts
                ->filter(fn ($shift) => $shift->shift_date->toDateString() === $day->toDateString())
                ->sortBy('start_time');

            $dayShifts = $dayShiftsFlat->groupBy('shift_role');

            $guideCount = $dayShiftsFlat->where('shift_role', 'guide')->count();
            $hostCount = $dayShiftsFlat->where('shift_role', 'host')->count();
            $adminCount = $dayShiftsFlat->where('shift_role', 'admin')->count();
            $restaurantCount = $dayShiftsFlat->where('shift_role', 'restaurant')->count();

            $restaurantSummary = $dayShiftsFlat
                ->where('shift_role', 'restaurant')
                ->groupBy('shift_function')
                ->map(fn ($items) => $items->count());

            $coverage = \App\Support\ShiftCoverage::evaluate($dayShiftsFlat, $day);
        @endphp

        <div class="week-day-block mb-4">
            <div class="week-day-header">
                <div>
                    <div class="fw-semibold">{{ $day->translatedFormat('l') }}</div>
                    <div class="small-muted">{{ $day->format('Y-m-d') }}</div>
                </div>

                <a href="{{ route($prefix . '.work-shifts.create', ['date' => $day->toDateString()]) }}"
                   class="btn btn-sm btn-outline-secondary">
                    Nytt pass
                </a>
            </div>

            <div class="coverage-box coverage-{{ $coverage['overall'] }} mb-3">
                <div class="coverage-title">Bemanningsläge</div>

                <div class="coverage-pills">
                    @foreach($coverage['items'] as $item)
                        <span class="coverage-pill coverage-pill-{{ $item['status'] }}">
                            {{ $item['text'] }}
                        </span>
                    @endforeach
                </div>
            </div>

            <div class="week-summary-row mb-3">
                <span class="week-summary-pill">Guider: {{ $guideCount }}</span>
                <span class="week-summary-pill">Värdar: {{ $hostCount }}</span>
                <span class="week-summary-pill">Restaurang: {{ $restaurantCount }}</span>

                @if($adminCount > 0)
                    <span class="week-summary-pill">Admin: {{ $adminCount }}</span>
                @endif

                @foreach($restaurantSummary as $function => $count)
                    <span class="week-summary-pill week-summary-sub">
                        {{ \App\Models\WorkShift::restaurantFunctions()[$function] ?? ucfirst($function) }}: {{ $count }}
                    </span>
                @endforeach
            </div>

            @if($coverage['overall'] !== 'green')
                <div class="staffing-warning-box mb-3">
                    <div class="staffing-warning-title">Bemanningsvarning</div>

                    <div class="staffing-warning-list">
                        @foreach($coverage['items'] as $item)
                            @if($item['status'] === 'red')
                                <div class="staffing-warning-item staffing-danger">
                                    Saknar bemanning för {{ strtolower($item['label']) }}.
                                </div>
                            @elseif($item['status'] === 'yellow')
                                <div class="staffing-warning-item staffing-warn">
                                    Låg bemanning för {{ strtolower($item['label']) }}.
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            @if($dayShifts->isEmpty())
                <div class="empty-day-box">
                    Inga arbetspass denna dag.
                </div>
            @else
                @foreach(['guide', 'host', 'restaurant', 'admin'] as $roleKey)
                    @php
                        $roleShifts = $dayShifts->get($roleKey, collect());
                    @endphp

                    @if($roleShifts->isNotEmpty())
                        <div class="role-block">
                            <div class="role-block-title">
                                {{ $roleLabels[$roleKey] ?? ucfirst($roleKey) }}
                            </div>

                            <div class="table-responsive-modern">
                                <table class="table-modern">
                                    <thead>
                                        <tr>
                                            <th style="width: 90px;">Start</th>
                                            <th style="width: 90px;">Slut</th>
                                            <th>Person</th>
                                            <th style="width: 140px;">Funktion</th>
                                            <th style="width: 120px;">Status</th>
                                            <th>Anteckning</th>
                                            <th style="width: 110px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($roleShifts as $shift)
                                            <tr>
                                                <td>{{ substr($shift->start_time, 0, 5) }}</td>
                                                <td>{{ $shift->end_time ? substr($shift->end_time, 0, 5) : '-' }}</td>
                                                <td>{{ $shift->user?->name }}</td>
                                                <td>
                                                    @if($shift->shift_function)
                                                        {{ \App\Models\WorkShift::restaurantFunctions()[$shift->shift_function] ?? ucfirst($shift->shift_function) }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>{{ ucfirst($shift->status) }}</td>
                                                <td>{{ $shift->notes ?: '-' }}</td>
                                                <td>
                                                    <a href="{{ route($prefix . '.work-shifts.edit', $shift) }}"
                                                       class="btn btn-sm btn-outline-secondary">
                                                        Redigera
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                @endforeach
            @endif
        </div>
    @endforeach
</div>

<style>
.week-day-block {
    border-top: 1px solid #e2e8f0;
    padding-top: 1rem;
}

.week-day-block:first-child {
    border-top: 0;
    padding-top: 0;
}

.week-day-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.9rem;
    flex-wrap: wrap;
}

.role-block {
    margin-bottom: 1rem;
}

.role-block:last-child {
    margin-bottom: 0;
}

.role-block-title {
    font-size: 0.9rem;
    font-weight: 800;
    color: #334155;
    margin-bottom: 0.5rem;
}

.empty-day-box {
    border: 1px dashed #cbd5e1;
    border-radius: 12px;
    padding: 1rem;
    color: #64748b;
    background: #f8fafc;
}

.coverage-box {
    border-radius: 12px;
    padding: 0.9rem 1rem;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
}

.coverage-green {
    border-color: #86efac;
    background: #f0fdf4;
}

.coverage-yellow {
    border-color: #fcd34d;
    background: #fffbeb;
}

.coverage-red {
    border-color: #fca5a5;
    background: #fef2f2;
}

.coverage-title {
    font-size: 0.9rem;
    font-weight: 800;
    margin-bottom: 0.6rem;
    color: #334155;
}

.coverage-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.coverage-pill {
    display: inline-flex;
    align-items: center;
    min-height: 30px;
    padding: 0.3rem 0.7rem;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 700;
    border: 1px solid transparent;
}

.coverage-pill-green {
    background: #22c55e;
    color: #ffffff;
    border-color: #16a34a;
}

.coverage-pill-yellow {
    background: #f59e0b;
    color: #ffffff;
    border-color: #d97706;
}

.coverage-pill-red {
    background: #ef4444;
    color: #ffffff;
    border-color: #dc2626;
}

.staffing-warning-box {
    border: 1px solid #fecaca;
    background: #fff7f7;
    border-radius: 12px;
    padding: 0.85rem 1rem;
}

.staffing-warning-title {
    font-size: 0.9rem;
    font-weight: 800;
    color: #991b1b;
    margin-bottom: 0.5rem;
}

.staffing-warning-list {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.staffing-warning-item {
    font-size: 0.92rem;
    font-weight: 600;
}

.staffing-danger {
    color: #b91c1c;
}

.staffing-warn {
    color: #b45309;
}

.week-summary-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.week-summary-pill {
    display: inline-flex;
    align-items: center;
    min-height: 30px;
    padding: 0.3rem 0.7rem;
    border-radius: 999px;
    background: #eef2ff;
    color: #3730a3;
    font-size: 0.85rem;
    font-weight: 700;
    border: 1px solid #c7d2fe;
}

.week-summary-sub {
    background: #f8fafc;
    color: #334155;
    border: 1px solid #cbd5e1;
}
</style>