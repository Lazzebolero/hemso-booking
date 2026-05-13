@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="mb-1">Redigera tur</h2>
        <div class="muted">Uppdatera turens uppgifter, guide och status.</div>
    </div>

    <div class="page-actions">
        <a href="{{ route('admin.tours.show', $tour) }}" class="btn btn-outline-secondary">
            <i class="bi bi-eye me-2"></i>Visa tur
        </a>

        <a href="{{ route('admin.tours.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>
    </div>
</div>

<form method="POST" action="{{ route('admin.tours.update', $tour) }}">
    @csrf
    @method('PUT')
    @include('admin.tours.form')
</form>
@endsection