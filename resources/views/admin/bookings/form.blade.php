@php
    use App\Models\Language;

    $defaultLanguageIds = $defaultLanguageIds ?? Language::defaultIds();
    $existingLanguageIds = $booking->exists
        ? $booking->languages?->pluck('id')->map(fn ($id) => (int) $id)->all() ?? []
        : $defaultLanguageIds;

    if ($booking->exists && $existingLanguageIds === []) {
        $existingLanguageIds = $defaultLanguageIds;
    }

    $selectedLanguages = collect(old('languages', $existingLanguageIds))
        ->map(fn ($id) => (int) $id)
        ->all();
@endphp

<div class="page-card">
    <div class="section-title">Bokningsuppgifter</div>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Tur</label>
            <select name="tour_id" class="form-select js-tour-select" required>
                @php
                    $upcomingTours = $upcomingTours ?? collect($tours ?? []);
                    $historicalTours = $historicalTours ?? collect();
                    $selectedTour = (string) old('tour_id', $selectedTourId ?? $booking->tour_id);
                @endphp

                @if($upcomingTours->isNotEmpty())
                    <optgroup label="Kommande turer">
                        @foreach($upcomingTours as $tour)
                            @include('admin.bookings._tour-option', [
                                'tour' => $tour,
                                'selectedTour' => $selectedTour,
                                'showStatus' => false,
                            ])
                        @endforeach
                    </optgroup>
                @endif

                @if($historicalTours->isNotEmpty())
                    <optgroup label="Historiska turer (retroaktiv bokning)">
                        @foreach($historicalTours as $tour)
                            @include('admin.bookings._tour-option', [
                                'tour' => $tour,
                                'selectedTour' => $selectedTour,
                                'showStatus' => true,
                            ])
                        @endforeach
                    </optgroup>
                @endif

                @if($upcomingTours->isEmpty() && $historicalTours->isEmpty())
                    @foreach($tours ?? [] as $tour)
                        @include('admin.bookings._tour-option', [
                            'tour' => $tour,
                            'selectedTour' => $selectedTour,
                            'showStatus' => false,
                        ])
                    @endforeach
                @endif
            </select>
            @include('partials.bookings.tour-availability-warning')
            @if(($historicalTours ?? collect())->isNotEmpty())
                <div class="form-text">Historiska turer kan fyllas i i efterhand utan kapacitetsgräns och utan bekräftelsemail.</div>
            @endif
        </div>

        <div class="col-md-6">
            <label class="form-label">Bokningsnamn</label>
            <input type="text" name="booking_name" class="form-control" value="{{ old('booking_name', $booking->booking_name) }}">
        </div>

        <div class="col-md-4">
            <label class="form-label">Kontaktperson</label>
            <input type="text" name="contact_name" class="form-control" value="{{ old('contact_name', $booking->contact_name) }}">
        </div>

        <div class="col-md-4">
            <label class="form-label">Telefon</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone', $booking->phone) }}">
        </div>

        <div class="col-md-4">
            <label class="form-label">E-post</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $booking->email) }}">
        </div>

        <div class="col-md-3">
            <label class="form-label">Män</label>
            <input type="number" min="0" name="men_count" class="form-control" value="{{ old('men_count', $booking->men_count ?? 0) }}">
        </div>

        <div class="col-md-3">
            <label class="form-label">Kvinnor</label>
            <input type="number" min="0" name="women_count" class="form-control" value="{{ old('women_count', $booking->women_count ?? 0) }}">
        </div>

        <div class="col-md-3">
            <label class="form-label">Ungdomar</label>
            <input type="number" min="0" name="youth_count" class="form-control" value="{{ old('youth_count', $booking->youth_count ?? 0) }}">
        </div>

        <div class="col-md-3">
            <label class="form-label">Barn</label>
            <input type="number" min="0" name="child_count" class="form-control" value="{{ old('child_count', $booking->child_count ?? 0) }}">
        </div>

        <div class="col-md-3">
            <label class="form-label">Ospecificerade</label>
            <input type="number" min="0" name="unspecified_count" class="form-control" value="{{ old('unspecified_count', $booking->unspecified_count ?? 0) }}">
            <div class="form-text">Använd när ni vet totalen men inte fördelningen.</div>
        </div>

        @if(isset($languages) && $languages->isNotEmpty())
            <div class="col-12">
                <label class="form-label">Språk</label>
                <div class="language-grid">
                    @foreach($languages as $language)
                        <label class="language-tile">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="languages[]"
                                value="{{ $language->id }}"
                                @checked(in_array((int) $language->id, $selectedLanguages, true))
                            >
                            <span>{{ $language->name }} <span class="small-muted">({{ strtoupper($language->code) }})</span></span>
                        </label>
                    @endforeach
                </div>
                <div class="form-text">Svenska är förvalt om inget annat väljs.</div>
            </div>
        @endif

        @include('partials.bookings.country-select', [
            'booking' => $booking,
            'quickPickCountries' => $quickPickCountries ?? collect(),
            'countries' => $countries ?? collect(),
        ])

        @include('partials.bookings.meal-select', [
            'booking' => $booking,
            'defaultIncludesMeal' => $defaultIncludesMeal ?? null,
        ])

        @include('partials.bookings.to-be-invoiced-checkbox', [
            'booking' => $booking,
        ])

        <div class="col-md-4">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                @foreach(['preliminary', 'confirmed', 'cancelled', 'completed'] as $status)
                    <option value="{{ $status }}" @selected(old('status', $booking->status ?? 'confirmed') === $status)>
                        {{ ucfirst($status) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-12">
            <label class="form-label">Anteckning</label>
            <textarea name="notes" class="form-control" rows="4">{{ old('notes', $booking->notes) }}</textarea>
        </div>
    </div>
</div>

<div class="form-side-box mt-3">
    <div class="section-title">Spara</div>

    <button class="btn btn-primary" type="submit">
        <i class="bi bi-save me-2"></i>Spara bokning
    </button>
</div>

<style>
.language-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.65rem;
}
.language-tile {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    background: #f8fafc;
    border: 1px solid var(--brand-line-soft);
    border-radius: 12px;
    padding: 0.75rem 0.85rem;
    min-height: 48px;
    cursor: pointer;
}
.language-tile .form-check-input {
    margin-top: 0;
    flex: 0 0 auto;
}
@media (max-width: 900px) {
    .language-grid {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 600px) {
    .language-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tourSelect = document.querySelector('select[name="tour_id"]');
        const mealSelect = document.querySelector('select[name="includes_meal"]');

        if (!tourSelect || !mealSelect) {
            return;
        }

        const applyTourMealDefault = function () {
            const option = tourSelect.options[tourSelect.selectedIndex];

            if (!option || !option.dataset.defaultMeal) {
                return;
            }

            mealSelect.value = option.dataset.defaultMeal === '1' ? '1' : '0';
        };

        tourSelect.addEventListener('change', applyTourMealDefault);
    });
</script>