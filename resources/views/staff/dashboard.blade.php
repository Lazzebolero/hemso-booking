@extends('layouts.app')

@section('content')

{{-- staff-dashboard-clean-no-local-nav --}}

<div class="staff-grid">
    <div class="page-card">
        <div class="section-title mb-3">Dagens pass</div>

        @if($todayShift)
            <div class="staff-info-line">
                <strong>Datum:</strong>
                {{ \Carbon\Carbon::parse($todayShift->shift_date)->format('Y-m-d') }}
            </div>

            <div class="staff-info-line">
                <strong>Tid:</strong>
                {{ substr($todayShift->start_time, 0, 5) }}–{{ $todayShift->end_time ? substr($todayShift->end_time, 0, 5) : '--:--' }}
            </div>

            <div class="staff-info-line">
                <strong>Roll:</strong>
                {{ ucfirst($todayShift->shift_role) }}
            </div>

            <div class="staff-info-line">
                <strong>Funktion:</strong>
                {{ $todayShift->shift_function ? (\App\Models\WorkShift::restaurantFunctions()[$todayShift->shift_function] ?? ucfirst($todayShift->shift_function)) : '-' }}
            </div>

            @if($todayShift->notes)
                <div class="small-muted mt-2">{{ $todayShift->notes }}</div>
            @endif
        @else
            <div class="small-muted">Inget arbetspass idag.</div>
        @endif
    </div>

    <div class="page-card">
        <div class="section-title mb-3">Kommande pass</div>

        @if($upcomingShifts->isNotEmpty())
            <div class="staff-list">
                @foreach($upcomingShifts as $shift)
                    <div class="staff-list-item">
                        <div class="fw-semibold">
                            {{ \Carbon\Carbon::parse($shift->shift_date)->translatedFormat('D j M') }}
                        </div>

                        <div class="small-muted">
                            {{ substr($shift->start_time, 0, 5) }}–{{ $shift->end_time ? substr($shift->end_time, 0, 5) : '--:--' }}
                            · {{ ucfirst($shift->shift_role) }}
                            @if($shift->shift_function)
                                · {{ \App\Models\WorkShift::restaurantFunctions()[$shift->shift_function] ?? ucfirst($shift->shift_function) }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="small-muted">Inga kommande pass hittades.</div>
        @endif
    </div>
</div>


@endsection
