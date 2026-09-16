@props(['qrStations' => [], 'guide' => false])

@if(count($qrStations) > 0)
    <div class="{{ $guide ? 'page-card mb-4' : 'alert alert-light border shadow-sm mb-4' }}">
        <div class="d-flex align-items-start gap-3">
            <i class="bi bi-qr-code-scan fs-3 text-primary flex-shrink-0"></i>
            <div class="flex-grow-1">
                <div class="fw-semibold mb-1">Stämpling via QR</div>
                <p class="small {{ $guide ? 'text-muted mb-2' : 'mb-2' }}">
                    Välj station vid in- och utstämpling — det sparas på passet.
                    Skanna QR-skylt eller använd knapparna nedan.
                    Plats hämtas om du tillåter det.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($qrStations as $station)
                        @if(Route::has('time.station'))
                            <a href="{{ route('time.station', $station['key']) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-qr-code-scan me-1"></i>{{ $station['label'] }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif
