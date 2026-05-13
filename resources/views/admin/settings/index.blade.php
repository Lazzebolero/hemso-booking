@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="mb-1">Admin-inställningar</h2>
        <div class="muted">Justera standardvärden, tidszon och automatiska namn.</div>
    </div>
</div>

<form method="POST" action="{{ route('admin.settings.update') }}">
    @csrf
    @method('PUT')

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="page-card h-100">
                <div class="section-title">System</div>

                <div class="mb-3">
                    <label class="form-label">Standard maxantal deltagare per tur</label>
                    <input type="number" name="default_tour_capacity" class="form-control" min="1" max="500" value="{{ old('default_tour_capacity', $settings['default_tour_capacity']) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Tidszon</label>
                    <select name="timezone" class="form-select">
                        @foreach(['Europe/Stockholm', 'UTC', 'Europe/Oslo', 'Europe/Copenhagen'] as $timezone)
                            <option value="{{ $timezone }}" @selected(old('timezone', $settings['timezone']) === $timezone)>{{ $timezone }}</option>
                        @endforeach
                    </select>
                    <div class="small muted mt-2">Sätt till Europe/Stockholm för svensk tid.</div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="page-card h-100">
                <div class="section-title">Automatiska namn</div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="auto_generate_tour_title" value="1" id="auto_generate_tour_title" @checked(old('auto_generate_tour_title', $settings['auto_generate_tour_title']))>
                    <label class="form-check-label" for="auto_generate_tour_title">Auto-generera namn på tur</label>
                </div>

                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" name="auto_generate_booking_name" value="1" id="auto_generate_booking_name" @checked(old('auto_generate_booking_name', $settings['auto_generate_booking_name']))>
                    <label class="form-check-label" for="auto_generate_booking_name">Auto-generera namn på bokning</label>
                </div>

                <button class="btn btn-primary w-100">
                    <i class="bi bi-save me-2"></i>Spara inställningar
                </button>
            </div>
        </div>
    </div>
</form>
@endsection
