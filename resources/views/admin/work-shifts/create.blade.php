@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Nytt arbetspass</h2>
        <div class="page-subtitle">Lägg till ett nytt schema-pass.</div>
    </div>

    <div class="page-actions">
        <a href="{{ route($prefix . '.work-shifts.index', ['date' => optional($workShift->shift_date)->format('Y-m-d')]) }}" class="btn btn-outline-secondary">
            Tillbaka
        </a>
    </div>
</div>

<form method="POST" action="{{ route($prefix . '.work-shifts.store') }}">
    @csrf
    @include('admin.work-shifts.form')

    <div class="mt-3">
        <button class="btn btn-primary">Spara arbetspass</button>
    </div>
</form>
@endsection