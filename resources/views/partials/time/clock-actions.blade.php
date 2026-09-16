@props(['openEntry' => null, 'guide' => false, 'qrStations' => []])

@if(count($qrStations) > 0)
    <div class="d-flex flex-wrap gap-2">
        @foreach($qrStations as $station)
            @if(Route::has('time.station'))
                <a href="{{ route('time.station', $station['key']) }}"
                   class="btn {{ $openEntry ? 'btn-danger' : ($guide ? 'btn-primary' : 'btn-success') }}">
                    <i class="bi bi-qr-code-scan {{ $guide ? 'me-2' : 'me-1' }}"></i>
                    {{ $openEntry ? 'Stämpla ut' : 'Stämpla in' }}
                    <span class="opacity-75">· {{ $station['label'] }}</span>
                </a>
            @endif
        @endforeach
    </div>
@else
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
@endif
