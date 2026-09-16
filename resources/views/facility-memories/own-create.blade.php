@extends($layout)

@section('content')
@if($shell === 'staff')
<div class="staff-page-stack">
    @include('partials.ui.flash-messages')

    <x-ui.page-header
        title="Spara minne"
        subtitle="Dokumentera en berättelse med anknytning till anläggningen — text eller ljud."
        icon="bi-journal-text"
    >
        <x-slot:actions>
            <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>{{ $backLabel }}
            </a>
        </x-slot:actions>
    </x-ui.page-header>
@else
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

        <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>{{ $backLabel }}
        </a>
    </div>
</div>

@include('partials.ui.flash-messages', ['guide' => true])
@endif

@include('partials.facility-memories.form', [
    'formAction' => route($routePrefix . '.memories.store'),
    'cancelUrl' => $cancelUrl,
    'tour' => $tour,
    'locations' => $locations,
    'tourOptions' => $tourOptions ?? collect(),
    'cardClass' => $cardClass,
    'showTourPicker' => $showTourPicker ?? false,
    'allowAudioFileUpload' => $allowAudioFileUpload ?? false,
])

@if($shell === 'staff')
</div>
@endif
@endsection
