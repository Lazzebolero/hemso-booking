@props([
    'tour',
    'prefix',
    'buttonClass' => 'btn btn-sm btn-outline-warning',
    'reopenButtonClass' => 'btn btn-sm btn-outline-secondary',
    'returnToQuickBooking' => false,
])

@if(($tour->status ?? null) === 'planned' || ($tour->status ?? null) === 'started')
    @if($tour->closed_for_bookings)
        @if(Route::has($prefix . '.tours.reopen-for-bookings'))
            <form method="POST" action="{{ route($prefix . '.tours.reopen-for-bookings', $tour) }}" class="d-inline">
                @csrf
                @if($returnToQuickBooking)
                    <input type="hidden" name="return_to" value="quick-booking">
                @endif
                <button type="submit" class="{{ $reopenButtonClass }}" title="Tur syns igen i bokningssekvensen">
                    <i class="bi bi-unlock me-1"></i>Öppna för bokning
                </button>
            </form>
        @endif
    @elseif(Route::has($prefix . '.tours.close-for-bookings'))
        <form
            method="POST"
            action="{{ route($prefix . '.tours.close-for-bookings', $tour) }}"
            class="d-inline"
            onsubmit="return confirm('Stäng turen för fler bokningar i bokningssekvensen?')"
        >
            @csrf
            @if($returnToQuickBooking)
                <input type="hidden" name="return_to" value="quick-booking">
            @endif
            <button type="submit" class="{{ $buttonClass }}" title="Turen försvinner från bokningssekvensen">
                <i class="bi bi-lock me-1"></i>Stäng för bokning
            </button>
        </form>
    @endif
@endif
