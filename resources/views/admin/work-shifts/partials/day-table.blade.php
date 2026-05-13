<div class="page-card">
    <div class="section-title mb-3">
        Schema för {{ $selectedDate->format('Y-m-d') }}
    </div>

    @php
        $coverage = \App\Support\ShiftCoverage::evaluate($shifts, $selectedDate);
    @endphp

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

    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Start</th>
                    <th>Slut</th>
                    <th>Person</th>
                    <th>Roll</th>
                    <th>Funktion</th>
                    <th>Status</th>
                    <th>Anteckning</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($shifts as $shift)
                    <tr>
                        <td>{{ substr($shift->start_time, 0, 5) }}</td>
                        <td>{{ $shift->end_time ? substr($shift->end_time, 0, 5) : '-' }}</td>
                        <td>{{ $shift->user?->name }}</td>
                        <td>{{ ucfirst($shift->shift_role) }}</td>
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
                            <a href="{{ route($prefix . '.work-shifts.edit', $shift) }}" class="btn btn-sm btn-outline-secondary">
                                Redigera
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">Inga arbetspass denna dag.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<style>
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
</style>