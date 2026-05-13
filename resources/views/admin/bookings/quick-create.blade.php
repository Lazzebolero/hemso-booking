@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Snabbbokning</h2>
        <div class="page-subtitle">Skapa bokning snabbt till närmaste lediga tur.</div>
    </div>

    <div class="page-actions">
        <a href="{{ route($prefix . '.bookings.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>
    </div>
</div>

<div class="page-card">
    <form method="POST" action="{{ route($prefix . '.bookings.quick-store') }}" class="quick-booking-grid js-quick-booking-form">
        @csrf

        <div class="quick-booking-main">
            <div class="section-title">Snabbregistrering</div>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Tur</label>
                    <select name="tour_id" class="form-select js-tour-select" required>
                        @forelse($tours as $tour)
                            @php
                                $booked = $tour->bookings
                                    ->where('status', '!=', 'cancelled')
                                    ->where('is_waitlist', false)
                                    ->sum('total_count');

                                $available = max(0, $tour->max_participants - $booked);
                            @endphp
                            <option
                                value="{{ $tour->id }}"
                                data-available="{{ $available }}"
                                @selected(old('tour_id', $preferredTourId) == $tour->id)
                            >
                                {{ substr($tour->start_time, 0, 5) }}
                                – {{ $tour->title }}
                                ({{ $available > 0 ? $available . ' lediga platser' : 'FULL' }})
                            </option>
                        @empty
                            <option value="">Inga turer finns idag</option>
                        @endforelse
                    </select>

                    <div class="form-text js-tour-help">Närmaste lediga tur är förvald.</div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Män</label>
                    <input type="number" min="0" name="men_count" class="form-control js-count js-focus-first" value="{{ old('men_count', 0) }}" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Kvinnor</label>
                    <input type="number" min="0" name="women_count" class="form-control js-count" value="{{ old('women_count', 0) }}" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Ungdomar</label>
                    <input type="number" min="0" name="youth_count" class="form-control js-count" value="{{ old('youth_count', 0) }}" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Barn</label>
                    <input type="number" min="0" name="child_count" class="form-control js-count" value="{{ old('child_count', 0) }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Totalt</label>
                    <input type="number" class="form-control js-total" value="0" readonly>
                </div>

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

                <div class="col-12">
                    <label class="form-label">Anteckning</label>
                    <textarea name="notes" class="form-control js-enter-flow" rows="3">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="quick-booking-side">
            <div class="form-side-box">
                <div class="section-title">Åtgärd</div>
                <div class="small-muted mb-3">Bokningen sparas och du kommer tillbaka till en tom snabbvy.</div>

                <div class="d-grid gap-2">
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
@media (max-width: 1100px) {
    .quick-booking-grid {
        grid-template-columns: 1fr;
    }
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
    const form = document.querySelector('.js-quick-booking-form');
    const countFields = Array.from(document.querySelectorAll('.js-count'));
    const totalField = document.querySelector('.js-total');
    const tourSelect = document.querySelector('.js-tour-select');
    const tourHelp = document.querySelector('.js-tour-help');
    const firstFocusField = document.querySelector('.js-focus-first');

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
                        const submitButton = form.querySelector('button[type="submit"]');
                        if (submitButton) {
                            submitButton.focus();
                        }
                    }
                }
            });
        });
    }

    countFields.forEach(field => {
        field.addEventListener('input', function () {
            updateTotal();
            updateBestTour();
        });
    });

    updateTotal();
    updateBestTour();
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