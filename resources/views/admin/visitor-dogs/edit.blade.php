@extends('layouts.app')

@section('content')
@php
    $vPrefix = \App\Support\ActiveRole::visitorDogsRoutePrefix();
@endphp

<div class="staff-page-stack">
    @include('partials.ui.flash-messages')

    <x-ui.page-header
        :title="'Redigera '.$dog->dog_name"
        subtitle="Uppdatera uppgifter eller byt bild."
        icon="bi-pencil"
    >
        <x-slot:actions>
            <a href="{{ \App\Support\VisitorDogSupport::routeForDog($vPrefix . '.visitor-dogs.show', $dog, $navQuery ?? []) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Tillbaka
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="page-card" style="max-width: 32rem;">
        @include('visitor-dogs._form', [
            'dog' => $dog,
            'formAction' => route($vPrefix . '.visitor-dogs.update', $dog),
            'cancelUrl' => \App\Support\VisitorDogSupport::routeForDog($vPrefix . '.visitor-dogs.show', $dog, $navQuery ?? []),
            'photoUrl' => $dog->photo_path ? route($vPrefix . '.visitor-dogs.photo', $dog) : null,
            'navigationQuery' => $navQuery ?? [],
        ])
    </div>
</div>
@endsection
