@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
    $matchedCount = $day?->entries->where('lookup_matched', true)->count() ?? 0;
    $unmatchedCount = $day?->entries->where('lookup_matched', false)->count() ?? 0;
    $peopleTotal = (int) ($day?->entries->sum('people_count') ?? 0);
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Postnummer (lista)</h2>
        <div class="page-subtitle">
            Frivilliga svenska postnummer från listan — ett per rad, med antal personer.
            Kopplas till ort och län via registret.
        </div>
    </div>

    <div class="page-actions d-flex gap-2 flex-wrap">
        <a href="{{ route($prefix . '.postal-codes.report') }}" class="btn btn-outline-primary">
            <i class="bi bi-map me-2"></i>Visa rapport
        </a>
        <a href="{{ route($prefix . '.dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Till dashboard
        </a>
    </div>
</div>

@include('partials.ui.flash-messages')

@if($lookupCount === 0)
    <div class="alert alert-warning">
        Postnummerregistret är tomt. Kör
        <code>php artisan postal-codes:import-lookups</code>
        så ort/län kan fyllas i.
    </div>
@elseif($lookupCount < 100)
    <div class="alert alert-warning">
        Postnummerregistret har bara {{ $lookupCount }} rader (sample). Kör
        <code>php artisan postal-codes:import-lookups --truncate</code>
        för fullständig lista (~19&nbsp;000 postnummer).
    </div>
@endif

<div class="page-card">
    <form method="POST" action="{{ route($prefix . '.postal-codes.update') }}">
        @csrf
        @method('PUT')

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label" for="postal_code_date">Datum</label>
                <input
                    type="date"
                    id="postal_code_date"
                    name="date"
                    class="form-control @error('date') is-invalid @enderror"
                    value="{{ old('date', $date->toDateString()) }}"
                    required
                >
                @error('date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-8 d-flex align-items-end">
                <div class="small-muted">
                    @if($day)
                        {{ $day->entries->count() }} rader · {{ $peopleTotal }} personer
                        @if($unmatchedCount > 0)
                            · {{ $unmatchedCount }} utan uppslag
                        @endif
                        @if($day->updatedBy)
                            · senast {{ $day->updatedBy->name }}
                            @if($day->updated_at)
                                {{ $day->updated_at->format('Y-m-d H:i') }}
                            @endif
                        @endif
                    @else
                        Ingen insamling sparad för detta datum ännu.
                    @endif
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="postal_codes">Postnummer och antal</label>
            <textarea
                id="postal_codes"
                name="postal_codes"
                class="form-control font-monospace @error('postal_codes') is-invalid @enderror"
                rows="14"
                placeholder="87140 4&#10;11122 2&#10;41101"
            >{{ $postalCodesText }}</textarea>
            @error('postal_codes')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="form-text">
                Ett per rad: <code>87140 4</code> eller bara <code>87140</code> (då räknas 1 person).
                Tom lista + spara raderar dagens poster.
            </div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-2"></i>Spara
            </button>
            <a
                href="{{ route($prefix . '.postal-codes.edit', ['date' => now()->toDateString()]) }}"
                class="btn btn-outline-secondary"
            >
                Idag
            </a>
        </div>
    </form>
</div>

@if($day && $day->entries->isNotEmpty())
    <div class="page-card mt-4">
        <div class="section-title mb-3">Uppslag för {{ $date->toDateString() }}</div>
        <div class="table-responsive-modern">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Postnummer</th>
                        <th>Personer</th>
                        <th>Ort</th>
                        <th>Kommun</th>
                        <th>Län</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($day->entries as $entry)
                        <tr>
                            <td class="fw-semibold">{{ $entry->postal_code }}</td>
                            <td>{{ $entry->people_count }}</td>
                            <td>{{ $entry->locality ?: '–' }}</td>
                            <td>{{ $entry->municipality_name ?: '–' }}</td>
                            <td>{{ $entry->county_name ?: '–' }}</td>
                            <td class="small-muted">
                                {{ $entry->lookup_matched ? 'Matchad' : 'Saknas i registret' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="small-muted mt-2">{{ $matchedCount }} matchade · {{ $unmatchedCount }} utan uppslag · {{ $peopleTotal }} personer totalt</div>
    </div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.getElementById('postal_code_date');
    if (!dateInput) {
        return;
    }

    dateInput.addEventListener('change', function () {
        if (!dateInput.value) {
            return;
        }

        const url = new URL(@json(route($prefix . '.postal-codes.edit')), window.location.origin);
        url.searchParams.set('date', dateInput.value);
        window.location.href = url.toString();
    });
});
</script>
@endsection
