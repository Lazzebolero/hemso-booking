@props([
    'tour',
    'prefix',
    'buttonClass' => 'btn btn-sm btn-outline-secondary',
    'fullWidth' => false,
    'buttonLabel' => 'Starta tur',
    'buttonIconClass' => 'bi bi-play-fill me-1',
])

@php
    $earlyStartService = app(\App\Services\TourEarlyStartService::class);
    $requiresEarlyStartConfirm = $earlyStartService->requiresConfirmation($tour);
@endphp

@if(($tour->status ?? null) === 'planned' && Route::has($prefix . '.tours.start'))
    <form
        method="POST"
        action="{{ route($prefix . '.tours.start', $tour) }}"
        data-tour-early-start-form
        @if($requiresEarlyStartConfirm)
            data-requires-early-start-confirm="1"
            data-early-start-message="@js($earlyStartService->confirmationMessage($tour))"
        @endif
        @class(['d-inline' => ! $fullWidth, 'w-100' => $fullWidth])
    >
        @csrf
        <button type="submit" @class([$buttonClass, 'w-100' => $fullWidth])>
            <i class="{{ $buttonIconClass }}" aria-hidden="true"></i>{{ $buttonLabel }}
        </button>
    </form>

    @once
        @include('partials.tours.early-start-confirm-script')
    @endonce
@endif
