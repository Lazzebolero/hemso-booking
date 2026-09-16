@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Bokningssekvens</h2>
        <div class="page-subtitle">Boka grupp till nästa lediga tur i dagens sekvens.</div>
    </div>

    <div class="page-actions">
        <a href="{{ route($prefix . '.bookings.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>
    </div>
</div>

@include('partials.ui.flash-messages')

<div class="page-card">
    <div class="quick-booking-grid">
        <form
            method="POST"
            action="{{ route($prefix . '.bookings.quick-store') }}"
            id="quick-booking-form"
            class="js-quick-booking-form quick-booking-main"
        >
            @csrf

            <div class="section-title">Bokningsuppgifter</div>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Tur</label>
                    <select name="tour_id" class="form-select js-tour-select" required>
                        @forelse($tours as $tour)
                            @include('admin.bookings._tour-option', [
                                'tour' => $tour,
                                'selectedTour' => (string) old('tour_id', $preferredTourId),
                                'compact' => true,
                            ])
                        @empty
                            <option value="">Inga turer finns idag</option>
                        @endforelse
                    </select>

                    @include('partials.bookings.tour-availability-warning')

                    <div class="form-text js-tour-help">Närmaste lediga tur är förvald.</div>
                    <button type="button" class="btn btn-link btn-sm p-0 js-pick-best-tour">
                        Välj nästa lediga tur
                    </button>
                </div>

                @include('partials.bookings.participant-fields', [
                    'booking' => new \App\Models\Booking(),
                    'compact' => true,
                    'focusFirstField' => 'men',
                ])

                <div class="col-md-4">
                    <label class="form-label">Totalt</label>
                    <input type="number" class="form-control js-total" value="0" readonly>
                </div>

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
                                    @checked(in_array($language->id, old('languages', $defaultLanguageId ? [$defaultLanguageId] : [])))
                                >
                                <span>{{ $language->name }} <span class="small-muted">({{ strtoupper($language->code) }})</span></span>
                            </label>
                        @endforeach
                    </div>
                </div>

                @include('partials.bookings.country-select', [
                    'quickPickCountries' => $quickPickCountries,
                    'countries' => $countries,
                ])

                <div class="col-md-4">
                    <label class="form-label">Kontaktperson</label>
                    <input type="text" name="contact_name" class="form-control js-enter-flow" value="{{ old('contact_name') }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Telefon</label>
                    <input type="text" name="phone" class="form-control js-enter-flow" value="{{ old('phone') }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label">E-post</label>
                    <input type="email" name="email" class="form-control js-enter-flow" value="{{ old('email') }}">
                </div>

                @include('partials.bookings.meal-select', ['columnClass' => 'col-md-6'])

                @include('partials.bookings.to-be-invoiced-checkbox', [
                    'columnClass' => 'col-md-6',
                    'checkboxId' => 'quick_to_be_invoiced',
                ])

                <div class="col-12">
                    <label class="form-label">Anteckning</label>
                    <textarea name="notes" class="form-control js-enter-flow" rows="3">{{ old('notes') }}</textarea>
                </div>

                <div class="col-12 d-lg-none">
                    <div class="quick-booking-bottom-actions">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-save me-2"></i>Boka och ny
                        </button>

                        <a href="{{ route($prefix . '.bookings.index') }}" class="btn btn-outline-secondary">
                            Avbryt
                        </a>
                    </div>
                </div>
            </div>
        </form>

        <aside class="quick-booking-side">
            <div class="form-side-box mb-3">
                <div class="section-title">Åtgärd</div>
                <div class="small-muted mb-3">Bokningen sparas och du kommer tillbaka till en tom vy för nästa bokning i sekvensen.</div>

                <div class="d-grid gap-2">
                    <button class="btn btn-primary" type="submit" form="quick-booking-form">
                        <i class="bi bi-save me-2"></i>Boka och ny
                    </button>

                    <a href="{{ route($prefix . '.bookings.index') }}" class="btn btn-outline-secondary">
                        Avbryt
                    </a>
                </div>
            </div>

            <div class="form-side-box quick-booking-close-panel">
                <div class="section-title">Stäng turer</div>
                <div class="small-muted mb-3">
                    Stängda turer försvinner från listan. Närmaste lediga tur förvalas automatiskt.
                </div>

                @if($tours->isEmpty())
                    <div class="small-muted">Inga öppna turer i sekvensen just nu.</div>
                @else
                    <div class="quick-booking-close-list d-grid gap-2">
                        @foreach($tours as $tour)
                            @php
                                $booked = (int) collect($tour->bookings ?? [])
                                    ->whereNotIn('status', ['cancelled'])
                                    ->sum('total_count');
                                $max = (int) ($tour->max_participants ?? 0);
                            @endphp
                            <div class="quick-booking-close-item">
                                <div class="small">
                                    <div class="fw-semibold">
                                        {{ ! empty($tour->start_time) ? substr($tour->start_time, 0, 5) : '—' }}
                                        · {{ $tour->title }}
                                    </div>
                                    <div class="text-muted">{{ $booked }}/{{ $max }} bokade</div>
                                </div>
                                @include('partials.admin.tour-close-bookings-form', [
                                    'tour' => $tour,
                                    'prefix' => $prefix,
                                    'buttonClass' => 'btn btn-sm btn-outline-warning',
                                    'reopenButtonClass' => 'btn btn-sm btn-outline-secondary',
                                    'returnToQuickBooking' => true,
                                ])
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </aside>
    </div>
</div>

<style>
.quick-booking-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 280px;
    gap: 1rem;
    align-items: start;
}
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

.form-side-box {
    background: #f8fafc;
    border: 1px solid var(--brand-line-soft);
    border-radius: 12px;
    padding: 0.95rem;
}
.quick-booking-close-panel {
    display: flex;
    flex-direction: column;
    min-height: 0;
}
.quick-booking-close-list {
    max-height: min(42vh, 420px);
    overflow-y: auto;
    padding-right: 0.15rem;
}
.quick-booking-close-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    border: 1px solid var(--brand-line-soft);
    border-radius: 12px;
    padding: 0.65rem 0.75rem;
    background: #fff;
}
.quick-booking-bottom-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    padding-top: 0.5rem;
    border-top: 1px solid var(--brand-line-soft);
}
@media (max-width: 1100px) {
    .quick-booking-grid {
        grid-template-columns: 1fr;
    }
    .quick-booking-close-list {
        max-height: none;
    }
}
@media (max-width: 900px) {
    .language-grid {
        grid-template-columns: 1fr 1fr;
    }
    .country-grid {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 600px) {
    .language-grid {
        grid-template-columns: 1fr;
    }
    .country-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.js-quick-booking-form');
    const countFields = Array.from(document.querySelectorAll('.js-participant-field, .js-count'));
    const totalField = document.querySelector('.js-total');
    const tourSelect = document.querySelector('.js-tour-select');
    const tourHelp = document.querySelector('.js-tour-help');
    const pickBestTourButton = document.querySelector('.js-pick-best-tour');
    const firstFocusField = document.querySelector('.js-focus-first');
    let manualTourSelection = @json((bool) old('tour_id'));

    function getTotal() {
        let total = 0;
        countFields.forEach(field => {
            total += parseInt(field.value || 0, 10);
        });
        return total;
    }

    function updateTotal() {
        if (!totalField) return;
        totalField.value = getTotal();
    }

    function getSelectedTourOption() {
        if (!tourSelect) {
            return null;
        }

        return tourSelect.options[tourSelect.selectedIndex] || null;
    }

    function updateManualTourFeedback() {
        const option = getSelectedTourOption();

        if (!option || !tourHelp) {
            return;
        }

        const available = parseInt(option.dataset.available || 0, 10);
        const requiredSeats = Math.max(1, getTotal());

        if (available < requiredSeats) {
            tourHelp.textContent = `Vald tur har ${available} platser kvar — gruppen är ${requiredSeats} personer. Du kan spara ändå om det behövs.`;
        } else {
            tourHelp.textContent = 'Du har valt tur manuellt. Turvalet ändras inte när antal personer uppdateras.';
        }

        tourSelect.dispatchEvent(new Event('change'));
    }

    function updateBestTour() {
        if (!tourSelect) return;

        const requiredSeats = Math.max(1, getTotal());
        const options = Array.from(tourSelect.options);

        let selected = false;
        let fallbackOption = null;

        options.forEach(option => {
            const available = parseInt(option.dataset.available || 0, 10);

            if (!fallbackOption) {
                fallbackOption = option;
            }

            if (!selected && available >= requiredSeats) {
                tourSelect.value = option.value;
                selected = true;
            }
        });

        if (!selected && fallbackOption) {
            tourSelect.value = fallbackOption.value;

            const fallbackAvailable = parseInt(fallbackOption.dataset.available || 0, 10);

            if (tourHelp) {
                if (fallbackAvailable < requiredSeats) {
                    tourHelp.textContent = 'Ingen tur rymmer hela gruppen just nu. Första möjliga tur visas.';
                } else {
                    tourHelp.textContent = 'Närmaste lediga tur är förvald.';
                }
            }
        } else if (tourHelp) {
            tourHelp.textContent = 'Närmaste tur som rymmer gruppen är förvald.';
        }

        tourSelect.dispatchEvent(new Event('change'));
    }

    function refreshTourSelection() {
        if (manualTourSelection) {
            updateManualTourFeedback();

            return;
        }

        updateBestTour();
    }

    function setupEnterFlow() {
        if (!form) return;

        const fields = [
            ...countFields,
            form.querySelector('[name="contact_name"]'),
            form.querySelector('[name="phone"]'),
            form.querySelector('[name="email"]'),
            form.querySelector('[name="notes"]')
        ].filter(Boolean);

        fields.forEach((field, index) => {
            field.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' && field.tagName !== 'TEXTAREA') {
                    event.preventDefault();

                    const nextField = fields[index + 1];

                    if (nextField) {
                        nextField.focus();

                        if (typeof nextField.select === 'function') {
                            nextField.select();
                        }
                    } else {
                        const submitButton = document.querySelector('button[type="submit"][form="quick-booking-form"]');
                        if (submitButton) {
                            submitButton.focus();
                        }
                    }
                }
            });
        });
    }

    if (tourSelect) {
        tourSelect.addEventListener('change', function (event) {
            if (event.isTrusted) {
                manualTourSelection = true;
            }

            if (manualTourSelection) {
                updateManualTourFeedback();
            }
        });
    }

    if (pickBestTourButton) {
        pickBestTourButton.addEventListener('click', function () {
            manualTourSelection = false;
            updateBestTour();
        });
    }

    countFields.forEach(field => {
        field.addEventListener('input', function () {
            updateTotal();
            refreshTourSelection();
        });
    });

    updateTotal();

    if (manualTourSelection) {
        updateManualTourFeedback();
    } else {
        updateBestTour();
    }

    setupEnterFlow();

    if (firstFocusField) {
        firstFocusField.focus();
        if (typeof firstFocusField.select === 'function') {
            firstFocusField.select();
        }
    }
});
</script>
@endsection
