@props(['openEntry' => null, 'guide' => false])

@if($openEntry)
    <form method="POST" action="{{ route('time.clock-out') }}" data-offline-queue>
        @csrf
        <button type="submit" class="btn btn-danger">
            <i class="bi bi-box-arrow-right {{ $guide ? 'me-2' : 'me-1' }}"></i>Stämpla ut
        </button>
    </form>
@else
    <form method="POST" action="{{ route('time.clock-in') }}" data-offline-queue>
        @csrf
        <button type="submit" class="btn {{ $guide ? 'btn-primary' : 'btn-success' }}">
            <i class="bi bi-box-arrow-in-right {{ $guide ? 'me-2' : 'me-1' }}"></i>Stämpla in
        </button>
    </form>
@endif
