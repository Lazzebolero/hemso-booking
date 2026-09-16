@extends('layouts.guide')

@section('content')
<div class="page-card mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h2 class="page-title mb-1">Spara minne</h2>
            <div class="page-subtitle">
                Dokumentera en berättelse med anknytning till anläggningen. Minnet skickas till arkivet.
            </div>
            @if($tour)
                <div class="guide-muted mt-2">
                    Kopplas till tur:
                    <strong>{{ $tour->tourType?->name ?? 'Tur' }}</strong>
                    {{ $tour->tour_date?->format('Y-m-d') }}
                    @if(!empty($tour->start_time))
                        {{ substr($tour->start_time, 0, 5) }}
                    @endif
                </div>
            @else
                <div class="guide-muted mt-2">
                    Ingen tur kopplas automatiskt — du kan spara minnet när som helst.
                </div>
            @endif
        </div>

        <a href="{{ $tour ? route('guide.tours.show', $tour) : route('guide.dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>
    </div>
</div>

@include('partials.ui.flash-messages', ['guide' => true])

@include('partials.facility-memories.form', [
    'formAction' => route('guide.memories.store'),
    'cancelUrl' => $tour ? route('guide.tours.show', $tour) : route('guide.dashboard'),
    'tour' => $tour,
    'locations' => $locations,
    'cardClass' => 'guide-card mb-3',
    'showTourPicker' => false,
    'allowAudioFileUpload' => false,
])
@endsection
