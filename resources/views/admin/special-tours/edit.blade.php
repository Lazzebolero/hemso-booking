@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Redigera specialtur</h2>
        <div class="page-subtitle">Uppdatera både turdata och publik bokningssida.</div>
    </div>

    <div class="page-actions">
        @if(!empty($publicUrl))
            <a href="{{ $publicUrl }}" target="_blank" class="btn btn-outline-secondary">
                <i class="bi bi-box-arrow-up-right me-2"></i>Öppna publik sida
            </a>
        @endif

        <a href="{{ route($prefix . '.special-tours.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>
    </div>
</div>

<form method="POST" action="{{ route($prefix . '.special-tours.update', $tour) }}">
    @csrf
    @method('PUT')
    @include('admin.special-tours.form')
</form>
@endsection