@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Redigera tur</h2>
        <div class="page-subtitle">Uppdatera turens uppgifter, guide och status.</div>
    </div>

    <div class="page-actions">
        <a href="{{ route($prefix . '.tours.show', $tour) }}" class="btn btn-outline-secondary">
            <i class="bi bi-eye me-2"></i>Visa tur
        </a>

        <a href="{{ route($prefix . '.tours.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>
    </div>
</div>

<div class="page-card">
    <form method="POST" action="{{ route($prefix . '.tours.update', $tour) }}">
        @csrf
        @method('PUT')

        @include('admin.tours.form')

        <div class="mt-4 d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-2"></i>Spara ändringar
            </button>

            <a href="{{ route($prefix . '.tours.index') }}" class="btn btn-outline-secondary">
                Avbryt
            </a>
        </div>
    </form>
</div>
@endsection