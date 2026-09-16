@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Admin-inställningar</h2>
        <div class="page-subtitle">Justera standardvärden, tidszon, automatiska namn och bemanningsmål.</div>
    </div>
</div>

<form method="POST" action="{{ route('admin.settings.update') }}">
    @csrf
    @method('PUT')

    <div class="admin-grid-2">
        <div class="page-card">
            <div class="section-title">System</div>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Standard maxantal deltagare per tur</label>
                    <input
                        type="number"
                        name="default_tour_capacity"
                        class="form-control"
                        min="1"
                        max="500"
                        value="{{ old('default_tour_capacity', $settings['default_tour_capacity']) }}"
                    >
                    <div class="form-text">Används bland annat vid snabbtur och nya turer. Turlängd och auto-avslut styrs under Turtyper.</div>
                </div>

                <div class="col-12">
                    <label class="form-label">Tidszon</label>
                    <select name="timezone" class="form-select">
                        @foreach(['Europe/Stockholm', 'UTC', 'Europe/Oslo', 'Europe/Copenhagen'] as $timezone)
                            <option value="{{ $timezone }}" @selected(old('timezone', $settings['timezone']) === $timezone)>
                                {{ $timezone }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Sätt till Europe/Stockholm för svensk tid.</div>
                </div>

                <div class="col-12">
                    <label class="form-label">Varningsgräns väntetid till tur (minuter)</label>
                    <input
                        type="number"
                        name="tour_wait_warning_minutes"
                        class="form-control"
                        min="0"
                        max="180"
                        value="{{ old('tour_wait_warning_minutes', $settings['tour_wait_warning_minutes']) }}"
                    >
                    <div class="form-text">
                        Visas på dashboard under Kommande turer idag. Markerar turer där samma-dagsbokningar väntat längre än gränsen
                        (väntan före dagens första tur räknas som självvald och blir 0).
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">Max snabbval för länder i bokningssekvensen</label>
                    <input
                        type="number"
                        name="country_quick_pick_max"
                        class="form-control"
                        min="1"
                        max="{{ \App\Support\CountryCatalog::ABSOLUTE_MAX_QUICK_PICKS }}"
                        value="{{ old('country_quick_pick_max', $settings['country_quick_pick_max']) }}"
                    >
                    <div class="form-text">Styr hur många länder som visas som snabbknappar. Detaljerad hantering under Länder.</div>
                </div>
            </div>
        </div>

        <div class="page-card">
            <div class="section-title">Automatiska namn</div>

            <div class="form-check mb-3">
                <input
                    class="form-check-input"
                    type="checkbox"
                    name="auto_generate_tour_title"
                    value="1"
                    id="auto_generate_tour_title"
                    @checked(old('auto_generate_tour_title', $settings['auto_generate_tour_title']))
                >
                <label class="form-check-label" for="auto_generate_tour_title">
                    Auto-generera namn på tur
                </label>
            </div>

            <div class="form-check mb-4">
                <input
                    class="form-check-input"
                    type="checkbox"
                    name="auto_generate_booking_name"
                    value="1"
                    id="auto_generate_booking_name"
                    @checked(old('auto_generate_booking_name', $settings['auto_generate_booking_name']))
                >
                <label class="form-check-label" for="auto_generate_booking_name">
                    Auto-generera namn på bokning
                </label>
            </div>

            <div class="form-side-box">
                <div class="info-label">Spara</div>
                <div class="small-muted mb-3">
                    Dessa inställningar påverkar nya turer och bokningar i hela systemet.
                </div>

                <button class="btn btn-primary w-100">
                    <i class="bi bi-save me-2"></i>Spara inställningar
                </button>
            </div>
        </div>
    </div>

    <div class="page-card mt-4">
        <div class="section-title mb-3">Bemanningsmål för arbetsschema</div>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Guider vardag</label>
                <input
                    type="number"
                    min="0"
                    max="20"
                    name="staffing_goal_guides_weekday"
                    class="form-control"
                    value="{{ old('staffing_goal_guides_weekday', $settings['staffing_goal_guides_weekday'] ?? 2) }}"
                >
            </div>

            <div class="col-md-4">
                <label class="form-label">Guider helg</label>
                <input
                    type="number"
                    min="0"
                    max="20"
                    name="staffing_goal_guides_weekend"
                    class="form-control"
                    value="{{ old('staffing_goal_guides_weekend', $settings['staffing_goal_guides_weekend'] ?? 3) }}"
                >
            </div>

            <div class="col-md-4">
                <label class="form-label">Värdar</label>
                <input
                    type="number"
                    min="0"
                    max="20"
                    name="staffing_goal_hosts"
                    class="form-control"
                    value="{{ old('staffing_goal_hosts', $settings['staffing_goal_hosts'] ?? 1) }}"
                >
            </div>

            <div class="col-md-4">
                <label class="form-label">Kock</label>
                <input
                    type="number"
                    min="0"
                    max="20"
                    name="staffing_goal_kock"
                    class="form-control"
                    value="{{ old('staffing_goal_kock', $settings['staffing_goal_kock'] ?? 1) }}"
                >
            </div>

            <div class="col-md-4">
                <label class="form-label">Kallskänk</label>
                <input
                    type="number"
                    min="0"
                    max="20"
                    name="staffing_goal_kallskank"
                    class="form-control"
                    value="{{ old('staffing_goal_kallskank', $settings['staffing_goal_kallskank'] ?? 0) }}"
                >
            </div>

            <div class="col-md-4">
                <label class="form-label">Kassa</label>
                <input
                    type="number"
                    min="0"
                    max="20"
                    name="staffing_goal_kassa"
                    class="form-control"
                    value="{{ old('staffing_goal_kassa', $settings['staffing_goal_kassa'] ?? 1) }}"
                >
            </div>

            <div class="col-md-4">
                <label class="form-label">Disk</label>
                <input
                    type="number"
                    min="0"
                    max="20"
                    name="staffing_goal_disk"
                    class="form-control"
                    value="{{ old('staffing_goal_disk', $settings['staffing_goal_disk'] ?? 0) }}"
                >
            </div>

            <div class="col-md-4">
                <label class="form-label">Glassbar</label>
                <input
                    type="number"
                    min="0"
                    max="20"
                    name="staffing_goal_glassbar"
                    class="form-control"
                    value="{{ old('staffing_goal_glassbar', $settings['staffing_goal_glassbar'] ?? 0) }}"
                >
            </div>

            <div class="col-md-4">
                <label class="form-label">Servering</label>
                <input
                    type="number"
                    min="0"
                    max="20"
                    name="staffing_goal_servering"
                    class="form-control"
                    value="{{ old('staffing_goal_servering', $settings['staffing_goal_servering'] ?? 1) }}"
                >
            </div>
        </div>

        <div class="form-text mt-2">
            Sätt 0 på funktioner som inte ska räknas som krav i bemanningsläget.
        </div>
    </div>

    <style>
        .form-side-box {
            background: #f8fafc;
            border: 1px solid var(--brand-line-soft);
            border-radius: 12px;
            padding: 0.95rem;
        }
    </style>
</form>
@endsection