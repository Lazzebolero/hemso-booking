@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Färjekorrigering</h2>
        <div class="page-subtitle">
            Justera start- och sluttider för enskilda turer när färjtiderna inte stämmer.
            Varje tur flyttas separat med +10 eller −10 minuter.
        </div>
    </div>

    <div class="page-actions">
        <a href="{{ route($prefix . '.dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Dashboard
        </a>
    </div>
</div>

<div class="page-card mb-4">
    <form method="GET" action="{{ route($prefix . '.ferry-adjustments.index') }}" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Datum</label>
            <input type="date" name="date" class="form-control" value="{{ $selectedDate }}" required>
        </div>

        <div class="col-md-8">
            <button type="submit" class="btn btn-primary">
                Visa dag
            </button>
        </div>
    </form>
</div>

@if(!empty($ferrySnapshot))
    <div class="page-card mb-4">
        <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
            <div>
                <div class="section-title mb-1">Färjeläge · Strinningen</div>
                <div class="small-muted">Senast avgått och nästa avgång enligt tidtabell.</div>
            </div>

            @if(Route::has('ferry-schedule.index'))
                <a href="{{ route('ferry-schedule.index', ['date' => $selectedDate]) }}" class="btn btn-sm btn-outline-secondary">
                    Visa färjeläge
                </a>
            @endif
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-6">
                <div class="small-muted">Senast avgått</div>
                <div class="fw-bold">{{ $ferrySnapshot['last']['time'] ?? '–' }}</div>
            </div>
            <div class="col-md-6">
                <div class="small-muted">Nästa avgång</div>
                <div class="fw-bold">
                    @if(!empty($ferrySnapshot['next']))
                        {{ $ferrySnapshot['next']['time'] }}
                    @else
                        –
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif

<div class="page-card">
    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th style="width: 150px;">Starttid</th>
                    <th style="width: 110px;">Sluttid</th>
                    <th>Tur</th>
                    <th style="width: 140px;">Guide</th>
                    <th style="width: 90px;">Bokade</th>
                    <th style="width: 110px;">Status</th>
                    <th style="width: 280px;">Åtgärder</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tours as $tour)
                    @php
                        $canAdjust = $tour->status === 'planned' && ! empty($tour->start_time);
                        $booked = (int) ($tour->booked_people_count ?? 0);
                    @endphp

                    <tr @class(['ferry-adjusted-row' => $tour->isFerryAdjusted()])>
                        <td>
                            <div class="fw-semibold">{{ $tour->displayTime($tour->start_time) }}</div>
                            @if($tour->isFerryAdjusted())
                                <div class="small-muted">Planerad {{ $tour->displayTime($tour->original_start_time) }}</div>
                            @endif
                        </td>

                        <td>
                            <div class="fw-semibold">{{ $tour->displayTime($tour->end_time) }}</div>
                            @if($tour->isFerryAdjusted() && $tour->original_end_time)
                                <div class="small-muted">Planerad {{ $tour->displayTime($tour->original_end_time) }}</div>
                            @endif
                        </td>

                        <td>
                            <div class="d-flex flex-wrap align-items-center gap-1">
                                <div class="fw-semibold">{{ $tour->title }}</div>
                                @include('partials.tours.meal-badge', ['tour' => $tour])
                                @include('partials.tours.ferry-badge', ['tour' => $tour])
                            </div>
                            <div class="small-muted">{{ $tour->tourType?->name ?? '-' }}</div>
                        </td>

                        <td>{{ $tour->guide?->name ?? 'Ej tilldelad' }}</td>

                        <td>{{ $booked }}/{{ $tour->max_participants }}</td>

                        <td>{{ $tour->completionStatusLabel() }}</td>

                        <td>
                            @if($canAdjust)
                                <div class="toolbar-inline">
                                    <form method="POST" action="{{ route($prefix . '.ferry-adjustments.shift', $tour) }}">
                                        @csrf
                                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                                        <input type="hidden" name="minutes" value="-10">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                            −10 min
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route($prefix . '.ferry-adjustments.shift', $tour) }}">
                                        @csrf
                                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                                        <input type="hidden" name="minutes" value="10">
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            +10 min
                                        </button>
                                    </form>

                                    @if($tour->isFerryAdjusted())
                                        <form method="POST" action="{{ route($prefix . '.ferry-adjustments.reset', $tour) }}">
                                            @csrf
                                            <input type="hidden" name="date" value="{{ $selectedDate }}">
                                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                                Återställ
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @else
                                <span class="small-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center muted py-4">
                            Inga turer hittades för valt datum.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<style>
    .ferry-adjusted-row {
        background: rgba(14, 116, 144, 0.06);
    }

    .tour-ferry-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.12rem 0.45rem;
        border-radius: 999px;
        background: rgba(14, 116, 144, 0.12);
        color: #0e7490;
        font-size: 0.72rem;
        font-weight: 700;
        line-height: 1.2;
        white-space: nowrap;
    }
</style>
@endsection
