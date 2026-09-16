@extends($useGuideLayout ? 'layouts.guide' : 'layouts.app')

@section('content')
@php
    $photoCompletionOnly = $photoCompletionOnly ?? false;
@endphp

<div class="staff-page-stack">
    <x-ui.page-header
        :guide="$useGuideLayout"
        :title="$photoCompletionOnly ? 'Lägg till bild på '.$dog->dog_name : 'Redigera '.$dog->dog_name"
        :subtitle="$photoCompletionOnly ? 'Komplettera någon annans registrering med foto.' : 'Uppdatera uppgifter eller byt bild.'"
        icon="bi-pencil"
    >
        <x-slot:actions>
            <a href="{{ \App\Support\VisitorDogSupport::routeForDog('visitor-dogs.show', $dog, $navQuery ?? []) }}" class="btn btn-outline-secondary{{ $useGuideLayout ? ' btn-sm' : '' }}">
                <i class="bi bi-arrow-left me-1"></i>Tillbaka
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    @include('partials.ui.flash-messages', ['guide' => $useGuideLayout])

    <div class="{{ $useGuideLayout ? 'guide-card' : 'page-card' }}" @if(! $useGuideLayout) style="max-width: 32rem;" @endif>
        @include('visitor-dogs._form', [
            'dog' => $dog,
            'formAction' => route('visitor-dogs.update', $dog),
            'cancelUrl' => \App\Support\VisitorDogSupport::routeForDog('visitor-dogs.show', $dog, $navQuery ?? []),
            'photoUrl' => $dog->photo_path ? route('visitor-dogs.photo', $dog) : null,
            'navigationQuery' => $navQuery ?? [],
            'photoCompletionOnly' => $photoCompletionOnly,
        ])
    </div>
</div>
@endsection
