@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="mb-1">Skapa dagens turer</h2>
        <div class="muted">Lägg upp flera turer samtidigt för dagens schema.</div>
    </div>

    <a href="{{ route($prefix . '.tours.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Tillbaka
    </a>
</div>

<div class="page-card">
    <form method="POST" action="{{ route($prefix . '.tours.batch-store') }}" class="row g-3">
        @csrf

        <div class="col-md-3">
            <label class="form-label">Datum</label>
            <input type="date" name="tour_date" class="form-control" value="{{ old('tour_date', now()->toDateString()) }}" required>
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
                <option value="60" @selected(old('interval') == '60')>Var 60:e minut</option>
                <option value="30" @selected(old('interval') == '30')>Var 30:e minut</option>
                <option value="15" @selected(old('interval') == '15')>Var 15:e minut</option>
            </select>
        </div>

        <div class="col-md-4">
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

        <div class="col-md-4">
            <label class="form-label">Guide</label>
            <select name="guide_id" class="form-select">
                <option value="">Ej tilldelad</option>
                @foreach($guides as $guide)
                    <option value="{{ $guide->id }}" @selected(old('guide_id') == $guide->id)">
                        {{ $guide->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">Max deltagare</label>
            <input type="number" name="max_participants" class="form-control" value="{{ old('max_participants', setting('default_tour_capacity', 25)) }}" min="1" required>
        </div>

        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="skip_existing" value="1" id="skip_existing" checked>
                <label class="form-check-label" for="skip_existing">
                    Hoppa över tider som redan finns
                </label>
            </div>
        </div>

        <div class="col-12">
            <button class="btn btn-primary">
                <i class="bi bi-calendar-plus me-2"></i>Skapa turer
            </button>
        </div>
    </form>
</div>
@endsection