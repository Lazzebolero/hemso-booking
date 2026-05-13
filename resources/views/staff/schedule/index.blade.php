@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Mitt schema</h2>
        <div class="page-subtitle">
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
    <div class="staff-list">
        @forelse($shifts as $shift)
            <div class="staff-shift-card">
                <div class="fw-semibold">
                    {{ \Carbon\Carbon::parse($shift->shift_date)->translatedFormat('l Y-m-d') }}
                </div>
                <div class="small-muted">
                    {{ substr($shift->start_time, 0, 5) }}–{{ $shift->end_time ? substr($shift->end_time, 0, 5) : '--:--' }}
                </div>
                <div class="small-muted">
                    {{ ucfirst($shift->shift_role) }}
                    @if($shift->shift_function)
                        · {{ \App\Models\WorkShift::restaurantFunctions()[$shift->shift_function] ?? ucfirst($shift->shift_function) }}
                    @endif
                </div>
                @if($shift->notes)
                    <div class="mt-2">{{ $shift->notes }}</div>
                @endif
            </div>
        @empty
            <div class="small-muted">Inga arbetspass denna vecka.</div>
        @endforelse
    </div>
</div>

<style>
.staff-list {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
}

.staff-shift-card {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 0.9rem;
    background: #f8fafc;
}
</style>
@endsection