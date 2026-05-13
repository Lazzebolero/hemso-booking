@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Ny specialtur</h2>
        <div class="page-subtitle">Skapa tur och publik bokningssida i samma formulär.</div>
    </div>

    <div class="page-actions">
        <a href="{{ route($prefix . '.special-tours.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>
    </div>
</div>

<form method="POST" action="{{ route($prefix . '.special-tours.store') }}">
    @csrf
    @include('admin.special-tours.form')
</form>
@endsection