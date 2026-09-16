@extends('layouts.app')

@section('content')
@php
    $vPrefix = \App\Support\ActiveRole::visitorDogsRoutePrefix();
@endphp

<div class="staff-page-stack">
    @include('partials.ui.flash-messages')

    <x-ui.page-header
        title="Ny besökshund"
        subtitle="Registrera hund utan bild. Foto kan läggas till senare i appen."
        icon="bi-heart-pulse"
    >
        <x-slot:actions>
            <a href="{{ route($vPrefix . '.visitor-dogs.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Till listan
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="page-card" style="max-width: 32rem;">
        @include('visitor-dogs._staff-register-form', [
            'defaultVisitDate' => $defaultVisitDate,
        ])
    </div>
</div>
@endsection
