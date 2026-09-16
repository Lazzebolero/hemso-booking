@php
    $quickPickCountries = $quickPickCountries ?? collect();
    $countries = $countries ?? collect();
    $bookingCountryId = isset($booking) ? ($booking->country_id ?? '') : '';
    $selectedCountryId = old(
        'country_id',
        $selectedCountryId ?? $bookingCountryId
    );
    $selectedCountryId = $selectedCountryId === null || $selectedCountryId === ''
        ? ''
        : (string) $selectedCountryId;
    $proposedName = old('country_proposed_name', '');
    $nothingSelected = $selectedCountryId === '' && $proposedName === '';
@endphp

<div class="col-12">
    <label class="form-label">Land</label>
    <div class="country-grid">
        <button
            type="button"
            class="country-tile js-country-tile{{ $nothingSelected ? ' is-selected' : '' }}"
            data-country-id=""
        >
            <span class="fw-semibold">Ej angivet</span>
        </button>

        @foreach($quickPickCountries as $country)
            <button
                type="button"
                class="country-tile js-country-tile{{ $selectedCountryId === (string) $country->id ? ' is-selected' : '' }}"
                data-country-id="{{ $country->id }}"
            >
                <img src="{{ $country->flagUrl() }}" alt="" class="country-flag-img" loading="lazy">
                <span class="fw-semibold">{{ $country->name }}</span>
            </button>
        @endforeach
    </div>

    <label class="form-label mt-2 mb-1">Övriga länder</label>
    <input type="hidden" name="country_id" value="{{ $selectedCountryId }}" class="js-country-id">
    <select class="form-select js-country-select">
        <option value="" @selected($selectedCountryId === '')>Ej angivet</option>
        @foreach($countries as $country)
            <option
                value="{{ $country->id }}"
                @selected($selectedCountryId === (string) $country->id)
            >
                {{ $country->name }}
            </option>
        @endforeach
    </select>

    <div class="mt-2">
        <label class="form-label mb-1">Finns inte i listan?</label>
        <input
            type="text"
            name="country_proposed_name"
            class="form-control js-country-proposed"
            value="{{ $proposedName }}"
            placeholder="Skriv land här, t.ex. Island"
        >
        <div class="form-text">Sparas som föreslaget land och kan godkännas i admin under Länder.</div>
    </div>
</div>

<style>
.country-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.65rem;
}

.country-tile {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.35rem;
    background: #f8fafc;
    border: 1px solid var(--brand-line-soft);
    border-radius: 12px;
    padding: 0.75rem 0.85rem;
    min-height: 52px;
    cursor: pointer;
    text-align: left;
    width: 100%;
}

.country-flag-img {
    width: 28px;
    height: 20px;
    object-fit: cover;
    border-radius: 3px;
    border: 1px solid rgba(0, 0, 0, 0.08);
}

.country-tile.is-selected {
    border-color: var(--brand-primary);
    background: rgba(99,102,241,0.08);
}

@media (max-width: 900px) {
    .country-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 600px) {
    .country-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const countryIdInput = document.querySelector('.js-country-id');
    const countrySelect = document.querySelector('.js-country-select');
    const countryTiles = Array.from(document.querySelectorAll('.js-country-tile'));
    const countryProposed = document.querySelector('.js-country-proposed');

    if (!countryIdInput || countryTiles.length === 0) {
        return;
    }

    function getSelectedCountryId() {
        return String(countryIdInput.value || '');
    }

    function setSelectedCountryId(value) {
        const nextValue = String(value || '');
        countryIdInput.value = nextValue;

        if (countrySelect) {
            const optionExists = Array.from(countrySelect.options).some(function (option) {
                return String(option.value) === nextValue;
            });
            countrySelect.value = optionExists ? nextValue : '';
        }
    }

    function syncCountryTiles() {
        const selectedValue = getSelectedCountryId();

        countryTiles.forEach(function (tile) {
            const tileValue = String(tile.dataset.countryId || '');
            tile.classList.toggle('is-selected', tileValue === selectedValue);
        });
    }

    countryTiles.forEach(function (tile) {
        tile.addEventListener('click', function () {
            setSelectedCountryId(tile.dataset.countryId || '');
            if (countryProposed) {
                countryProposed.value = '';
            }
            syncCountryTiles();
        });
    });

    if (countrySelect) {
        countrySelect.addEventListener('change', function () {
            setSelectedCountryId(countrySelect.value || '');
            if (countryProposed && countrySelect.value !== '') {
                countryProposed.value = '';
            }
            syncCountryTiles();
        });
    }

    if (countryProposed) {
        countryProposed.addEventListener('input', function () {
            if (countryProposed.value.trim() !== '') {
                setSelectedCountryId('');
                syncCountryTiles();
            }
        });
    }

    syncCountryTiles();
});
</script>
