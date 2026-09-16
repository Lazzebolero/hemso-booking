@php
    use App\Services\TimeClockLocationPayload;
@endphp

@if($entry->clock_in_station || $entry->clock_out_station)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">QR-stämpling och plats</h2>
            <p class="small text-muted mb-4">
                Endast för granskning vid misstanke. Grov GPS räcker för att skilja hemma från anläggningen.
            </p>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="text-muted small mb-2">Instämpling</div>
                        <div class="fw-semibold mb-2">{{ $entry->clockInStationLabel() ?? '–' }}</div>
                        <div class="small">
                            {{ TimeClockLocationPayload::statusLabel($entry->clock_in_location_status) }}
                        </div>
                        @if($entry->clock_in_latitude && $entry->clock_in_longitude)
                            <div class="small text-muted mt-2">
                                {{ number_format((float) $entry->clock_in_latitude, 5, ',', '') }},
                                {{ number_format((float) $entry->clock_in_longitude, 5, ',', '') }}
                                @if($entry->clock_in_location_accuracy_m)
                                    (±{{ $entry->clock_in_location_accuracy_m }} m)
                                @endif
                            </div>
                            @if($entry->clockInDistanceFromFacilityKm() !== null)
                                <div class="small mt-1">Ca {{ $entry->clockInDistanceFromFacilityKm() }} km från referenspunkt</div>
                            @endif
                            <a href="https://www.google.com/maps?q={{ $entry->clock_in_latitude }},{{ $entry->clock_in_longitude }}"
                               class="small d-inline-block mt-2"
                               target="_blank"
                               rel="noopener noreferrer">Visa på karta</a>
                        @endif
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="text-muted small mb-2">Utstämpling</div>
                        <div class="fw-semibold mb-2">{{ $entry->clockOutStationLabel() ?? '–' }}</div>
                        <div class="small">
                            {{ TimeClockLocationPayload::statusLabel($entry->clock_out_location_status) }}
                        </div>
                        @if($entry->clock_out_latitude && $entry->clock_out_longitude)
                            <div class="small text-muted mt-2">
                                {{ number_format((float) $entry->clock_out_latitude, 5, ',', '') }},
                                {{ number_format((float) $entry->clock_out_longitude, 5, ',', '') }}
                                @if($entry->clock_out_location_accuracy_m)
                                    (±{{ $entry->clock_out_location_accuracy_m }} m)
                                @endif
                            </div>
                            @if($entry->clockOutDistanceFromFacilityKm() !== null)
                                <div class="small mt-1">Ca {{ $entry->clockOutDistanceFromFacilityKm() }} km från referenspunkt</div>
                            @endif
                            <a href="https://www.google.com/maps?q={{ $entry->clock_out_latitude }},{{ $entry->clock_out_longitude }}"
                               class="small d-inline-block mt-2"
                               target="_blank"
                               rel="noopener noreferrer">Visa på karta</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
