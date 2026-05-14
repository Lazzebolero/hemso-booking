@extends('layouts.app')

@section('content')
@php
    $vPrefix = $visitorDogsRoutePrefix ?? 'admin';
@endphp
<div class="page-header">
    <div>
        <h2 class="page-title">{{ $dog->dog_name }}</h2>
        <div class="page-subtitle">
            Besök {{ $dog->visit_date?->format('Y-m-d') }}
            @if($dog->tour_start_time)
                · Turstart {{ \Illuminate\Support\Str::of((string) $dog->tour_start_time)->substr(0, 5) }}
            @endif
        </div>
    </div>
    <div class="page-actions">
        <a href="{{ route($vPrefix . '.visitor-dogs.index', ['from_date' => $dog->visit_date?->format('Y-m-d'), 'to_date' => $dog->visit_date?->format('Y-m-d')]) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Till listan
        </a>
    </div>
</div>

<div class="admin-grid-2">
    <div class="page-card">
        <div class="section-title mb-3">Uppgifter</div>
        <dl class="row mb-0">
            <dt class="col-sm-4 small-muted">Namn</dt>
            <dd class="col-sm-8 fw-semibold">{{ $dog->dog_name }}</dd>

            <dt class="col-sm-4 small-muted">Ras</dt>
            <dd class="col-sm-8">{{ $dog->breed ?: '—' }}</dd>

            <dt class="col-sm-4 small-muted">Ägarens telefon</dt>
            <dd class="col-sm-8">{{ $dog->owner_phone ?: '—' }}</dd>

            <dt class="col-sm-4 small-muted">Datum</dt>
            <dd class="col-sm-8">{{ $dog->visit_date?->format('Y-m-d') }}</dd>

            <dt class="col-sm-4 small-muted">Turstart</dt>
            <dd class="col-sm-8">
                @if($dog->tour_start_time)
                    {{ \Illuminate\Support\Str::of((string) $dog->tour_start_time)->substr(0, 5) }}
                @else
                    —
                @endif
            </dd>

            <dt class="col-sm-4 small-muted">Registrerad</dt>
            <dd class="col-sm-8">{{ $dog->created_at?->format('Y-m-d H:i') }}</dd>

            <dt class="col-sm-4 small-muted">Som roll</dt>
            <dd class="col-sm-8">{{ $dog->registered_as_role }}</dd>

            <dt class="col-sm-4 small-muted">Av användare</dt>
            <dd class="col-sm-8">{{ $dog->registrar?->name ?? '—' }} ({{ $dog->registrar?->email ?? '—' }})</dd>
        </dl>
    </div>

    <div class="page-card">
        <div class="section-title mb-3">Bild</div>
        @if($dog->photo_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($dog->photo_path) }}"
                 alt="Bild på {{ $dog->dog_name }}"
                 class="img-fluid rounded"
                 style="max-height: 420px; object-fit: contain;">
        @else
            <p class="small-muted mb-0">Ingen bild bifogad.</p>
        @endif
    </div>
</div>
@endsection
