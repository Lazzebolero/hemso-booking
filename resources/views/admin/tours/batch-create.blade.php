@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="mb-1">Skapa dagens turer</h2>
        <div class="muted">Lägg upp flera turer samtidigt. Guider tilldelas automatiskt enligt dagens ordning.</div>
        <div class="mt-2">
            <a href="{{ url($prefix . '/daily-guide-orders') }}" class="small-link">
                <i class="bi bi-sort-numeric-down me-1"></i>Justera dagens guider
            </a>
        </div>
    </div>

    <a href="{{ route($prefix . '.tours.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Tillbaka
    </a>
</div>

<div class="page-card mb-4">
    <div class="section-title mb-2">Guideordning för {{ $selectedTourDate }}</div>

    @if($dailyGuideOrders->isEmpty())
        <div class="muted">
            Inga guider i dagens lista.
            <a href="{{ url($prefix . '/daily-guide-orders') }}?date={{ $selectedTourDate }}">Lägg till guider</a>
            innan du batchskapar, eller skapa turer utan guide.
        </div>
    @else
        <ol class="mb-0 ps-3">
            @foreach($dailyGuideOrders as $order)
                <li>{{ $order->user?->name ?? 'Okänd guide' }}</li>
            @endforeach
        </ol>
        <div class="form-text mt-2">Turer får guider i ordning 1 → 2 → 3 → 1 …</div>
    @endif
</div>

<div class="page-card">
    <form method="POST" action="{{ route($prefix . '.tours.batch-store') }}" class="row g-3">
        @csrf

        <div class="col-md-3">
            <label class="form-label">Datum</label>
            <input type="date" name="tour_date" class="form-control" value="{{ old('tour_date', $selectedTourDate) }}" required>
        </div>

        <div class="col-md-3">
            <label class="form-label">Första tur</label>
            <input type="time" name="first_tour" class="form-control" value="{{ old('first_tour', '11:00') }}" required>
        </div>

        <div class="col-md-3">
            <label class="form-label">Sista tur</label>
            <input type="time" name="last_tour" class="form-control" value="{{ old('last_tour', '16:00') }}" required>
        </div>

        <div class="col-md-3">
            <label class="form-label">Intervall</label>
            <select name="interval" class="form-select" required>
                <option value="60" @selected(old('interval', '60') == '60')>Var 60:e minut</option>
                <option value="30" @selected(old('interval') == '30')>Var 30:e minut</option>
                <option value="15" @selected(old('interval') == '15')>Var 15:e minut</option>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Turtyp</label>
            <select name="tour_type_id" class="form-select">
                <option value="">Förvald turtyp</option>
                @foreach($tourTypes as $tourType)
                    <option value="{{ $tourType->id }}" @selected(old('tour_type_id', $defaultTourTypeId) == $tourType->id)>
                        {{ $tourType->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Max deltagare</label>
            <input type="number" name="max_participants" class="form-control" value="{{ old('max_participants', setting('default_tour_capacity', 25)) }}" min="1" required>
        </div>

        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="assign_daily_guides" value="1" id="assign_daily_guides" @checked(old('assign_daily_guides', '1') == '1')>
                <label class="form-check-label" for="assign_daily_guides">
                    Tilldela guider enligt dagens ordning
                </label>
            </div>
        </div>

        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="skip_existing" value="1" id="skip_existing" @checked(old('skip_existing', '1') == '1')>
                <label class="form-check-label" for="skip_existing">
                    Hoppa över tider som redan finns
                </label>
            </div>
            <div class="form-text">Befintliga turer räknas med i rotationen — nya turer fortsätter på nästa guide i ordningen.</div>
        </div>

        <div class="col-12">
            <button class="btn btn-primary">
                <i class="bi bi-calendar-plus me-2"></i>Skapa turer
            </button>
        </div>
    </form>
</div>
@endsection
