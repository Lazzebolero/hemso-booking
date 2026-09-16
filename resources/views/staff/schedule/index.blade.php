@extends('layouts.app')

@section('content')
@php
    $statusLabels = [
        'planned' => 'Planerat',
        'confirmed' => 'Bekräftat',
        'changed' => 'Ändrat',
        'cancelled' => 'Inställt',
    ];
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Mitt schema</h2>
        <div class="page-subtitle">
            Alla dina arbetspass denna vecka, oavsett roll.
            Vecka {{ $startOfWeek->format('Y-m-d') }} – {{ $endOfWeek->format('Y-m-d') }}
        </div>
    </div>
</div>

<div class="page-card mb-3">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Utgå från datum</label>
            <input
                type="date"
                name="date"
                class="form-control"
                value="{{ $selectedDate->toDateString() }}"
            >
        </div>

        <div class="col-md-3">
            <button class="btn btn-outline-secondary">Visa vecka</button>
        </div>
    </form>
</div>

<div class="page-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="section-title mb-0">Schemalagda tider</div>
        <div class="small-muted">{{ $shifts->count() }} pass</div>
    </div>

    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Start</th>
                    <th>Slut</th>
                    <th>Roll</th>
                    <th>Funktion</th>
                    <th>Status</th>
                    <th>Anteckning</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shifts as $shift)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($shift->shift_date)->translatedFormat('l Y-m-d') }}</td>
                        <td>{{ substr($shift->start_time, 0, 5) }}</td>
                        <td>{{ $shift->end_time ? substr($shift->end_time, 0, 5) : '--:--' }}</td>
                        <td>
                            <span class="schedule-role-badge">
                                {{ $shiftRoleLabels[$shift->shift_role] ?? ucfirst($shift->shift_role) }}
                            </span>
                        </td>
                        <td>
                            @if($shift->shift_function)
                                {{ \App\Models\RestaurantFunction::label($shift->shift_function) }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $statusLabels[$shift->status] ?? ucfirst($shift->status) }}</td>
                        <td>{{ $shift->notes ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center muted py-4">Inga arbetspass denna vecka.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<style>
.schedule-role-badge {
    display: inline-block;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    background: #eef2ff;
    color: #3730a3;
    font-size: 0.82rem;
    font-weight: 700;
}
</style>
@endsection
