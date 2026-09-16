@props(['tour', 'bookingsCount' => null, 'buttonClass' => 'btn btn-sm btn-outline-danger'])

@php
    use App\Support\Roles;

    $count = $bookingsCount ?? $tour->bookings()->count();
    $confirm = $count > 0
        ? "Ta bort turen \"{$tour->title}\" och alla {$count} bokning(ar)? Detta kan inte ångras."
        : "Ta bort turen \"{$tour->title}\"? Detta kan inte ångras.";
@endphp

@if(session('active_role') === Roles::ADMIN && Route::has('admin.tours.destroy'))
    <form
        method="POST"
        action="{{ route('admin.tours.destroy', $tour) }}"
        class="d-inline"
        onsubmit="return confirm(@js($confirm));"
    >
        @csrf
        @method('DELETE')
        @if(request()->filled('scope'))
            <input type="hidden" name="scope" value="{{ request('scope') }}">
        @endif
        <button type="submit" class="{{ $buttonClass }}">
            <i class="bi bi-trash me-1"></i>Ta bort
        </button>
    </form>
@endif
