@extends($useGuideLayout ? 'layouts.guide' : 'layouts.app')

@section('content')
    <x-ui.page-header
        :guide="$useGuideLayout"
        title="QR-stämpling"
        :subtitle="'Station: ' . ($station['label'] ?? '')"
        icon="bi-qr-code-scan"
    />

    @include('partials.ui.flash-messages', ['guide' => $useGuideLayout])

    <div class="page-card mb-4">
        <p class="mb-3">
            @if($openEntry)
                Du har ett öppet pass sedan {{ optional($openEntry->clock_in_at_original)->format('H:i') }}.
                Stämpla ut när du lämnar.
            @else
                Stämpla in när du börjar. Plats hämtas om telefonen tillåter det — annars sparas stämplingen ändå.
            @endif
        </p>

        @if($openEntry)
            <form method="POST"
                  action="{{ route('time.clock-out') }}"
                  data-offline-queue
                  data-time-clock-form>
                @csrf
                <input type="hidden" name="clock_station" value="{{ $station['key'] }}">
                <input type="hidden" name="scan_token" value="{{ $scanToken }}">
                @include('partials.time.geolocation-fields')

                <button type="submit" class="btn btn-danger btn-lg w-100">
                    <i class="bi bi-box-arrow-right me-2"></i>Stämpla ut
                </button>
            </form>
        @else
            <form method="POST"
                  action="{{ route('time.clock-in') }}"
                  data-offline-queue
                  data-time-clock-form>
                @csrf
                <input type="hidden" name="clock_station" value="{{ $station['key'] }}">
                <input type="hidden" name="scan_token" value="{{ $scanToken }}">
                @include('partials.time.geolocation-fields')

                <button type="submit" class="btn {{ $useGuideLayout ? 'btn-primary' : 'btn-success' }} btn-lg w-100">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Stämpla in
                </button>
            </form>
        @endif

        <div class="mt-3 text-center">
            @if(Route::has('time.index'))
                <a href="{{ route('time.index') }}" class="small-muted">Gå till tidrapportering</a>
            @endif
        </div>
    </div>

    @include('partials.time.geolocation-script')
@endsection
