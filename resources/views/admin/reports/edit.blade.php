@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Redigera felrapport</h2>
        <div class="page-subtitle">Uppdatera status, klassning, plats och tilldelning.</div>
    </div>

    <div class="page-actions">
        <a href="{{ route('admin.reports.show', $report) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>
    </div>
</div>

<form method="POST" action="{{ route('admin.reports.update', $report) }}">
    @csrf
    @method('PUT')

    @include('admin.reports.form')
</form>
@endsection