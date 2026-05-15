@extends('layouts.app')

@section('content')
@php
    $vPrefix = \App\Support\ActiveRole::visitorDogsRoutePrefix();
@endphp

<div class="staff-page-stack">
    @include('partials.ui.flash-messages')

    <x-ui.page-header
        title="Hundbilder"
        subtitle="Registreringar med foto i valt datumintervall. Klicka på en bild för detaljsidan."
        icon="bi-images"
    >
        <x-slot:actions>
            <a href="{{ route($vPrefix . '.visitor-dogs.index', request()->only(['from_date', 'to_date'])) }}" class="btn btn-outline-secondary">
                <i class="bi bi-list-ul me-1"></i>Lista
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="page-card">
        @include('partials.visitor-dogs.date-filter', [
            'action' => route($vPrefix . '.visitor-dogs.gallery'),
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'resetUrl' => route($vPrefix . '.visitor-dogs.gallery'),
        ])
    </div>

    <div class="page-card">
        @if($dogs->isEmpty())
            <p class="text-muted mb-0">Inga hundbilder i valt intervall.</p>
        @else
            <div class="visitor-dog-gallery-grid">
                @foreach($dogs as $dog)
                    <a href="{{ \App\Support\VisitorDogSupport::routeForDog($vPrefix . '.visitor-dogs.show', $dog, \App\Support\VisitorDogSupport::linkQueryForReturn(request(), \App\Support\VisitorDogSupport::RETURN_GALLERY)) }}"
                       class="visitor-dog-gallery-tile">
                        <div class="visitor-dog-gallery-thumb rounded-2 overflow-hidden border shadow-sm">
                            <img src="{{ route($vPrefix . '.visitor-dogs.photo', $dog) }}"
                                 alt="{{ $dog->dog_name }}"
                                 class="visitor-dog-gallery-tile-img"
                                 loading="lazy"
                                 width="240"
                                 height="180">
                        </div>
                        <div class="visitor-dog-gallery-caption mt-1">
                            <div class="visitor-dog-gallery-name text-truncate">{{ $dog->dog_name }}</div>
                            <div class="visitor-dog-gallery-date text-truncate">{{ $dog->visit_date?->format('Y-m-d') }}</div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-3">
                {{ $dogs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
