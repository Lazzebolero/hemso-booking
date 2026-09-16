@props([
    'booking',
    'prefix',
    'fromTour' => false,
    'scope' => null,
    'buttonClass' => 'btn btn-sm btn-outline-danger',
    'label' => 'Ta bort',
])

@if(Route::has($prefix . '.bookings.destroy'))
    @php
        $bookingLabel = $booking->booking_name ?? ('bokning #' . $booking->id);
        $confirm = "Ta bort {$bookingLabel}? Detta kan inte ångras.";
    @endphp

    <form
        method="POST"
        action="{{ route($prefix . '.bookings.destroy', $booking) }}"
        class="d-inline"
        onsubmit="return confirm(@js($confirm));"
    >
        @csrf
        @method('DELETE')

        @if($fromTour)
            <input type="hidden" name="from_tour" value="1">
        @endif

        @if($scope)
            <input type="hidden" name="scope" value="{{ $scope }}">
        @endif

        <button type="submit" class="{{ $buttonClass }}">
            <i class="bi bi-trash me-1"></i>{{ $label }}
        </button>
    </form>
@endif
