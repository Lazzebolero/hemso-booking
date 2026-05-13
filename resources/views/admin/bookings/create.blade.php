@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Skapa bokning</h2>
        <div class="page-subtitle">Registrera en ny bokning och koppla den till rätt tur.</div>
    </div>

    <div class="page-actions">
        <a href="{{ route($prefix . '.bookings.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>
    </div>
</div>

<form method="POST" action="{{ route($prefix . '.bookings.store') }}">
    @csrf
    @include('admin.bookings.form')
</form>
@endsection