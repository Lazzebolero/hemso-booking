@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="mb-1">Skapa tur</h2>
        <div class="muted">Planera en ny tur och tilldela guide direkt.</div>
    </div>

    <a href="{{ route('admin.tours.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Tillbaka
    </a>
</div>

<form method="POST" action="{{ route('admin.tours.store') }}">
    @include('admin.tours.form')
</form>
@endsection

