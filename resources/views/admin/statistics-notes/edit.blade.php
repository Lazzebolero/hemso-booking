@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Statistiknotering</h2>
        <div class="page-subtitle">
            Daglig loggboksnotering för statistik — en text per datum.
        </div>
    </div>

    <div class="page-actions">
        <a href="{{ route($prefix . '.dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Till dashboard
        </a>
    </div>
</div>

@include('partials.ui.flash-messages')

<div class="page-card">
    <form method="POST" action="{{ route($prefix . '.statistics-notes.update') }}">
        @csrf
        @method('PUT')

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label" for="statistics_note_date">Datum</label>
                <input
                    type="date"
                    id="statistics_note_date"
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
                    @if($note?->updatedBy)
                        Senast sparad av {{ $note->updatedBy->name }}
                        @if($note->updated_at)
                            · {{ $note->updated_at->format('Y-m-d H:i') }}
                        @endif
                    @elseif($note)
                        Sparad notering finns för detta datum.
                    @else
                        Ingen notering sparad för detta datum ännu.
                    @endif
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="statistics_note_body">Notering</label>
            <textarea
                id="statistics_note_body"
                name="body"
                class="form-control @error('body') is-invalid @enderror"
                rows="6"
                maxlength="5000"
                placeholder="T.ex. kryssning, skolbesök, dåligt väder…"
            >{{ old('body', $note?->body) }}</textarea>
            @error('body')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="form-text">Lämna tomt och spara för att radera noteringen.</div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-2"></i>Spara notering
            </button>
            <a
                href="{{ route($prefix . '.statistics-notes.edit', ['date' => now()->toDateString()]) }}"
                class="btn btn-outline-secondary"
            >
                Idag
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.getElementById('statistics_note_date');
    if (!dateInput) {
        return;
    }

    dateInput.addEventListener('change', function () {
        if (!dateInput.value) {
            return;
        }

        const url = new URL(@json(route($prefix . '.statistics-notes.edit')), window.location.origin);
        url.searchParams.set('date', dateInput.value);
        window.location.href = url.toString();
    });
});
</script>
@endsection
