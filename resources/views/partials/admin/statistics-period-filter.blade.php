@php
    $formAction = $formAction ?? '';
    $formId = $formId ?? 'statistics-filter-form';
    $submitLabel = $submitLabel ?? 'Visa statistik';
    $showQuickFilters = $showQuickFilters ?? true;
    $showChartToggle = $showChartToggle ?? false;
    $showChart = $showChart ?? true;
    $showTourTypeFilter = $showTourTypeFilter ?? false;
    $tourTypes = $tourTypes ?? collect();
    $tourTypeFilterValue = $tourTypeFilterValue ?? \App\Support\StatisticsTourTypeFilter::ALL_VALUE;
    $scheduleTourTypeLabel = $scheduleTourTypeLabel ?? 'Alla turtyper';

    $currentYear = now()->year;
    $yearOptions = range($currentYear - 10, $currentYear + 1);
    $monthLabels = collect(range(1, 12))
        ->mapWithKeys(fn (int $monthNumber) => [
            $monthNumber => \Carbon\Carbon::create(2000, $monthNumber, 1)->locale('sv')->translatedFormat('F'),
        ])
        ->all();
    $tourTypeQuery = $showTourTypeFilter ? '&amp;tour_type_id='.urlencode($tourTypeFilterValue) : '';
@endphp

@if($showQuickFilters)
    <div class="stats-quick-filters mb-3">
        <span class="stats-quick-label">Snabbval:</span>
        <a href="{{ $formAction }}?period=year&amp;year={{ $currentYear }}{{ $tourTypeQuery }}" class="btn btn-sm btn-outline-secondary">
            Hela året {{ $currentYear }}
        </a>
        <a href="{{ $formAction }}?period=month&amp;year={{ $currentYear }}&amp;month={{ now()->month }}{{ $tourTypeQuery }}" class="btn btn-sm btn-outline-secondary">
            Denna månad
        </a>
        <a href="{{ $formAction }}?period=week&amp;date={{ now()->toDateString() }}{{ $tourTypeQuery }}" class="btn btn-sm btn-outline-secondary">
            Denna vecka
        </a>
        <a href="{{ $formAction }}?period=day&amp;date={{ now()->toDateString() }}{{ $tourTypeQuery }}" class="btn btn-sm btn-outline-secondary">
            Idag
        </a>
    </div>
@endif

<form method="GET" action="{{ $formAction }}" class="stats-filter-grid @if($showTourTypeFilter) stats-filter-grid-with-tour-type @endif" id="{{ $formId }}">
    <div>
        <label class="form-label">Period</label>
        <select name="period" class="form-select statistics-period-select">
            <option value="day" @selected($period === 'day')>Dag</option>
            <option value="week" @selected($period === 'week')>Vecka</option>
            <option value="month" @selected($period === 'month')>Månad</option>
            <option value="year" @selected($period === 'year')>År</option>
        </select>
    </div>

    <div data-filter-field="year">
        <label class="form-label">År</label>
        <select name="year" class="form-select">
            @foreach($yearOptions as $yearOption)
                <option value="{{ $yearOption }}" @selected((int) $year === (int) $yearOption)>{{ $yearOption }}</option>
            @endforeach
        </select>
    </div>

    <div data-filter-field="month">
        <label class="form-label">Månad</label>
        <select name="month" class="form-select">
            @foreach($monthLabels as $monthNumber => $monthLabel)
                <option value="{{ $monthNumber }}" @selected((int) $month === (int) $monthNumber)>{{ $monthLabel }}</option>
            @endforeach
        </select>
    </div>

    <div data-filter-field="date">
        <label class="form-label">Datum</label>
        <input type="date" name="date" class="form-control" value="{{ $date->toDateString() }}">
    </div>

    @if($showTourTypeFilter)
        <div>
            <label class="form-label">Turtyp</label>
            <select name="tour_type_id" class="form-select">
                <option value="{{ \App\Support\StatisticsTourTypeFilter::ALL_VALUE }}" @selected($tourTypeFilterValue === \App\Support\StatisticsTourTypeFilter::ALL_VALUE)>
                    Alla turtyper
                </option>
                @foreach($tourTypes as $tourType)
                    <option value="{{ $tourType->id }}" @selected((string) $tourTypeFilterValue === (string) $tourType->id)>
                        {{ $tourType->name }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    <div>
        <label class="form-label">Vald period</label>
        <div class="stats-period-box">
            {{ $from->toDateString() }} – {{ $to->toDateString() }}
        </div>
    </div>

    @if($showChartToggle)
        <div class="d-flex align-items-end">
            <div class="form-check mb-2">
                <input type="checkbox"
                       class="form-check-input"
                       name="chart"
                       value="1"
                       id="{{ $formId }}-show-chart"
                       @checked($showChart)>
                <label class="form-check-label" for="{{ $formId }}-show-chart">Linjediagram</label>
            </div>
        </div>
    @endif

    <div class="stats-filter-actions">
        <button class="btn btn-primary w-100">
            <i class="bi bi-funnel me-2"></i>{{ $submitLabel }}
        </button>
    </div>
</form>

@if($showTourTypeFilter)
    <div class="stats-tourtype-note mt-3">
        Hela rapporten baseras på <strong>{{ $scheduleTourTypeLabel }}</strong>.
        Turer markerade som borttagna från datum/tidsstatistik påverkar inte veckodagar, tider eller årets topplista.
    </div>
@endif

@once
<style>
.stats-filter-grid {
    display: grid;
    grid-template-columns: 160px 120px 180px 180px minmax(240px, 1fr) 200px;
    gap: 1rem;
    align-items: end;
}
.stats-filter-grid-with-tour-type {
    grid-template-columns: 160px 120px 180px 180px minmax(220px, 1fr) minmax(180px, 0.8fr) minmax(240px, 1fr) 200px;
}
.stats-tourtype-note {
    font-size: 0.92rem;
    color: #475569;
}
.stats-quick-filters {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}
.stats-quick-label {
    font-size: 0.82rem;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.stats-filter-field-hidden {
    display: none;
}
.stats-filter-actions {
    display: flex;
    align-items: end;
}
.stats-period-box {
    min-height: 46px;
    display: flex;
    align-items: center;
    padding: 0.72rem 0.86rem;
    border: 1px solid var(--brand-line-soft);
    border-radius: 12px;
    background: #f8fafc;
    font-weight: 600;
}
@media (max-width: 900px) {
    .stats-filter-grid {
        grid-template-columns: 1fr;
    }
}
</style>
<script>
    (function () {
        document.querySelectorAll('.statistics-period-select').forEach((periodSelect) => {
            const form = periodSelect.closest('form');

            if (!form) {
                return;
            }

            const toggleFilterFields = () => {
                const period = periodSelect.value;

                form.querySelectorAll('[data-filter-field]').forEach((field) => {
                    const name = field.getAttribute('data-filter-field');
                    const visible = (period === 'month' && (name === 'year' || name === 'month'))
                        || (period === 'year' && name === 'year')
                        || ((period === 'day' || period === 'week') && name === 'date');

                    field.classList.toggle('stats-filter-field-hidden', !visible);

                    field.querySelectorAll('input, select').forEach((input) => {
                        input.disabled = !visible;
                    });
                });
            };

            periodSelect.addEventListener('change', toggleFilterFields);
            toggleFilterFields();
        });
    })();
</script>
@endonce
