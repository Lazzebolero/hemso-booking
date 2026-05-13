@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Redigera arbetspass</h2>
        <div class="page-subtitle">Uppdatera schema-pass.</div>
    </div>

    <div class="page-actions">
        <a href="{{ route($prefix . '.work-shifts.index', ['date' => optional($workShift->shift_date)->format('Y-m-d')]) }}" class="btn btn-outline-secondary">
            Tillbaka
        </a>
    </div>
</div>

<form method="POST" action="{{ route($prefix . '.work-shifts.update', $workShift) }}">
    @csrf
    @method('PUT')
    @include('admin.work-shifts.form')

    <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary">Spara ändringar</button>
    </div>
</form>

<form method="POST" action="{{ route($prefix . '.work-shifts.destroy', $workShift) }}" class="mt-3" onsubmit="return confirm('Ta bort arbetspasset?')">
    @csrf
    @method('DELETE')
    <button class="btn btn-danger">Ta bort</button>
</form>
@endsection