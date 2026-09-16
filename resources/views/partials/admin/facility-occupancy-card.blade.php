@php
    $occupancy = $facilityOccupancy ?? ['ongoing_tour_guests' => 0, 'extra_count' => 0, 'total_in_facility' => 0];
    $routePrefix = \App\Support\ActiveRole::routePrefix();
@endphp

<div class="page-card mb-4" id="facility-occupancy-card">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <div class="section-title mb-1">Beläggning i anläggningen</div>
            <div class="small-muted">Pågående turer räknas automatiskt. Övrigt = personal, hantverkare m.m.</div>
        </div>
        <div class="text-end">
            <div class="small-muted">Totalt nu</div>
            <div class="fs-3 fw-bold">{{ (int) ($occupancy['total_in_facility'] ?? 0) }}</div>
        </div>
    </div>

    <div class="row g-3 align-items-end">
        <div class="col-md-4">
            <div class="small-muted">På turer just nu</div>
            <div class="fw-semibold fs-5">{{ (int) ($occupancy['ongoing_tour_guests'] ?? 0) }}</div>
        </div>

        <div class="col-md-8">
            @if(Route::has($routePrefix.'.facility-occupancy.update-extra'))
                <form method="POST" action="{{ route($routePrefix.'.facility-occupancy.update-extra') }}" class="row g-2 align-items-end">
                    @csrf
                    @method('PATCH')
                    <div class="col-sm-8">
                        <label class="form-label mb-1" for="facility_extra_count">Övrigt (personal m.m.)</label>
                        <input
                            type="number"
                            min="0"
                            max="9999"
                            class="form-control"
                            id="facility_extra_count"
                            name="extra_count"
                            value="{{ (int) ($occupancy['extra_count'] ?? 0) }}"
                        >
                    </div>
                    <div class="col-sm-4">
                        <button type="submit" class="btn btn-outline-primary w-100">Spara</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
