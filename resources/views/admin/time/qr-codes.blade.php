@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">
    <div>
        <h1 class="h3 mb-1">
            <i class="bi bi-qr-code me-2"></i>QR-stämpling
        </h1>
        <div class="text-muted">
            Två stationer (entré och restaurang). All personal med tidrapportering kan stämpla vid båda;
            in- och utstämpling sparas med stationsnamn på passet.
            Informera personalen om platsloggning vid stämpling.
        </div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        @if(Route::has('admin.time.index'))
            <a href="{{ route('admin.time.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-clock-history me-1"></i>Tidrapportering
            </a>
        @endif
    </div>
</div>

@if(! $facilityConfigured)
    <div class="alert alert-info border-0 shadow-sm mb-4">
        <strong>Valfritt:</strong> Sätt <code>TIME_CLOCK_FACILITY_LAT</code> och <code>TIME_CLOCK_FACILITY_LNG</code> i <code>.env</code>
        för att visa avstånd från anläggningen i admin (vid misstanke).
    </div>
@endif

<p class="small mb-4">
    Roller som kan stämpla via QR:
    @foreach($clockRoles as $role)
        <span class="badge text-bg-secondary">{{ $clockRoleLabels[$role] ?? $role }}</span>
    @endforeach
</p>

<div class="row g-4">
    @foreach($stations as $station)
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 mb-1">{{ $station['label'] }}</h2>
                    <p class="text-muted small mb-3">{{ $station['description'] }}</p>

                    @if(! $station['configured'])
                        <div class="alert alert-warning mb-0">
                            Sätt token i <code>.env</code>:
                            @if($station['key'] === 'entrance')
                                <code>TIME_CLOCK_TOKEN_ENTRANCE</code>
                            @else
                                <code>TIME_CLOCK_TOKEN_RESTAURANT</code>
                            @endif
                            (lång slumpsträng). Kör sedan <code>php artisan config:clear</code>.
                        </div>
                    @else
                        <div class="mb-3">
                            <div class="text-muted small mb-1">Länk för QR-kod</div>
                            <div class="font-monospace small text-break border rounded p-2 bg-light">
                                {{ $station['scan_url'] }}
                            </div>
                        </div>

                        <div class="text-center mb-3">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=280x280&amp;data={{ urlencode($station['scan_url']) }}"
                                 width="280"
                                 height="280"
                                 alt="QR {{ $station['label'] }}"
                                 class="img-fluid border rounded">
                        </div>

                        <p class="small text-muted mb-0">
                            Skriv ut och sätt upp vid {{ strtolower($station['label']) }}.
                            Personal måste vara inloggad med rätt roll innan skanning.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

@endsection
