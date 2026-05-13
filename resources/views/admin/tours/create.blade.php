@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Skapa tur</h2>
        <div class="page-subtitle">Planera en ny tur och tilldela guide direkt.</div>
    </div>

    <div class="page-actions">
        <a href="{{ route($prefix . '.tours.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>
    </div>
</div>

<form method="POST" action="{{ route($prefix . '.tours.store') }}">
    @include('admin.tours.form')
</form>
@endsection