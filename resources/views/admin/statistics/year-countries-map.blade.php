@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
    $mapQuery = array_filter([
        'year' => $year,
        'tour_type_id' => $tourTypeFilterValue !== \App\Support\StatisticsTourTypeFilter::ALL_VALUE
            ? $tourTypeFilterValue
            : null,
    ], fn ($value) => $value !== null && $value !== '');
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Besöksländer {{ $year }}</h2>
        <div class="page-subtitle">Stor karta med fokus på länder ni haft besök från under året. Sverige ingår inte.</div>
    </div>
    <div class="page-actions d-flex flex-wrap gap-2">
        <button type="button" id="year-countries-export-btn" class="btn btn-outline-primary" @disabled(empty($yearCountriesBreakdown))>
            <i class="bi bi-download me-2"></i>Ladda ner kartbild
        </button>
        <a href="{{ route($prefix . '.statistics.index', $mapQuery) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka till statistik
        </a>
    </div>
</div>

<div class="page-card mb-3">
    <form method="GET" action="{{ route($prefix . '.statistics.year-countries-map') }}" class="row g-3 align-items-end">
        <div class="col-auto">
            <label class="form-label" for="map_year">År</label>
            <input type="number" min="2000" max="2100" class="form-control" id="map_year" name="year" value="{{ $year }}" style="width: 7rem;">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="map_tour_type">Turtyp</label>
            <select class="form-select" id="map_tour_type" name="tour_type_id">
                <option value="all" @selected($tourTypeFilterValue === 'all')>Alla turtyper</option>
                @foreach($tourTypes as $tourType)
                    <option value="{{ $tourType->id }}" @selected((string) $tourTypeFilterValue === (string) $tourType->id)>
                        {{ $tourType->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Visa</button>
        </div>
    </form>

    <div class="d-flex flex-wrap gap-3 mt-3 pt-3 border-top">
        <div>
            <div class="form-label mb-1">Datakälla</div>
            <div class="btn-group" role="group" aria-label="Datakälla">
                <button type="button" class="btn btn-sm btn-outline-secondary js-map-source active" data-source="both">Båda</button>
                <button type="button" class="btn btn-sm btn-outline-secondary js-map-source" data-source="bookings">Bokningar</button>
                <button type="button" class="btn btn-sm btn-outline-secondary js-map-source" data-source="noted">Noterade dagar</button>
            </div>
        </div>
        <div>
            <div class="form-label mb-1">Vy</div>
            <div class="btn-group" role="group" aria-label="Vy">
                <button type="button" class="btn btn-sm btn-outline-secondary js-map-scope active" data-scope="world">Världen</button>
                <button type="button" class="btn btn-sm btn-outline-secondary js-map-scope" data-scope="europe">Europa</button>
            </div>
        </div>
        <div class="align-self-end small-muted">Klicka på ett land i listan för att zooma in.</div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="page-card" id="year-countries-export-root">
            <div class="year-countries-export-title mb-2">
                <div class="section-title mb-0">Besöksländer {{ $year }}</div>
                <div class="small-muted">{{ $tourTypeLabel }}</div>
            </div>
            <div id="year-countries-map-wrap">
                <div id="year-countries-map-large" class="year-countries-map-large" aria-label="Besöksländer {{ $year }}"></div>
            </div>
            <div id="year-countries-map-legend" class="d-flex align-items-center gap-2 mt-3 small-muted">
                <span>Få</span>
                <span class="year-countries-map-legend-swatch year-countries-map-legend-low"></span>
                <span class="flex-grow-1 year-countries-map-legend-bar"></span>
                <span class="year-countries-map-legend-swatch year-countries-map-legend-high"></span>
                <span>Fler</span>
            </div>
            <div id="year-countries-map-empty" class="muted d-none">Inga länder matchar vald datakälla/vy.</div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="page-card">
            <div class="section-title mb-3">
                Länder
                <span id="year-countries-count">({{ count($yearCountriesBreakdown ?? []) }})</span>
            </div>
            <div class="table-responsive-modern">
                <table class="table-modern" id="year-countries-table">
                    <thead>
                        <tr>
                            <th>Land</th>
                            <th style="width: 90px;">Bokningar</th>
                            <th style="width: 90px;">Personer</th>
                            <th style="width: 110px;">Noterade</th>
                        </tr>
                    </thead>
                    <tbody id="year-countries-table-body">
                        @forelse($yearCountriesBreakdown ?? [] as $row)
                            <tr class="js-country-row" role="button" tabindex="0" data-country-code="{{ strtoupper($row['code'] ?? '') }}">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ $row['flag_url'] }}" alt="" style="width:22px;height:16px;object-fit:cover;border-radius:2px;">
                                        <span class="fw-semibold">{{ $row['name'] }}</span>
                                    </div>
                                </td>
                                <td>{{ $row['bookings'] }}</td>
                                <td>{{ $row['people'] }}</td>
                                <td>{{ $row['noted_days'] }}</td>
                            </tr>
                        @empty
                            <tr class="js-country-empty">
                                <td colspan="4" class="muted">Inga utländska länder under {{ $year }}.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.year-countries-map-large {
    height: min(72vh, 720px);
    min-height: 420px;
    width: 100%;
    background: linear-gradient(180deg, #fbfdff 0%, #f8fafc 100%);
    border: 1px solid var(--brand-line-soft);
    border-radius: 16px;
}
.year-countries-map-large .jvm-container {
    width: 100%;
    height: 100%;
}
.year-countries-map-legend-swatch {
    width: 14px;
    height: 14px;
    border-radius: 3px;
    border: 1px solid rgba(15, 23, 42, 0.12);
    flex-shrink: 0;
}
.year-countries-map-legend-low { background: #dbeafe; }
.year-countries-map-legend-high { background: #1d4ed8; }
.year-countries-map-legend-bar {
    height: 10px;
    border-radius: 999px;
    background: linear-gradient(90deg, #dbeafe 0%, #93c5fd 20%, #60a5fa 40%, #3b82f6 60%, #2563eb 80%, #1d4ed8 100%);
    border: 1px solid rgba(15, 23, 42, 0.08);
}
#year-countries-table tbody tr.js-country-row {
    cursor: pointer;
}
#year-countries-table tbody tr.js-country-row:hover,
#year-countries-table tbody tr.js-country-row.is-active {
    background: rgba(37, 99, 235, 0.08);
}
.btn-group .btn.active {
    background: var(--brand-primary, #2563eb);
    border-color: var(--brand-primary, #2563eb);
    color: #fff;
}
</style>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsvectormap@1.6.0/dist/jsvectormap.min.css">
<script src="https://cdn.jsdelivr.net/npm/jsvectormap@1.6.0/dist/jsvectormap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsvectormap@1.6.0/dist/maps/world.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
@include('partials.admin.year-countries-choropleth')
<script>
    const yearCountriesMapData = @json($yearCountriesBreakdown ?? []);
    const yearCountriesExportYear = @json((int) $year);
    const mapUiLabels = {"export":"Ladda ner kartbild","exporting":"Skapar bild…","emptyFilter":"Inga länder matchar vald datakälla\/vy."};
    const EUROPE_CODES = new Set([
        'AD','AL','AT','BA','BE','BG','BY','CH','CY','CZ','DE','DK','EE','ES','FI','FO','FR','GB','UK','GG','GI','GR','HR','HU','IE','IM','IS','IT','JE','LI','LT','LU','LV','MC','MD','ME','MK','MT','NL','NO','PL','PT','RO','RS','RU','SE','SI','SK','SM','UA','VA','XK'
    ]);

    const EUROPE_FRAME = ['IS','NO','SE','FI','PT','ES','IT','GR','PL','RO','UA','GB','FR','DE'];

    (function initYearCountriesMapPage() {
        const mapWrap = document.getElementById('year-countries-map-wrap');
        const legendEl = document.getElementById('year-countries-map-legend');
        const emptyEl = document.getElementById('year-countries-map-empty');
        const countEl = document.getElementById('year-countries-count');
        const tableBody = document.getElementById('year-countries-table-body');
        const exportBtn = document.getElementById('year-countries-export-btn');

        if (!mapWrap || typeof jsVectorMap === 'undefined') {
            return;
        }

        let source = 'both';
        let scope = 'world';
        let mapInstance = null;
        let byCode = {};

        function rowWeight(row) {
            const bookings = Number(row.bookings || 0);
            const noted = Number(row.noted_days || 0);

            if (source === 'bookings') {
                return bookings;
            }
            if (source === 'noted') {
                return noted;
            }

            return bookings + noted;
        }

        function rowMatchesFilters(row) {
            const code = String(row.code || '').toUpperCase();
            if (!code) {
                return false;
            }
            if (scope === 'europe' && !EUROPE_CODES.has(code)) {
                return false;
            }
            return rowWeight(row) > 0;
        }

        function filteredRows() {
            return yearCountriesMapData.filter(rowMatchesFilters);
        }

        function setActiveToggle(groupSelector, attr, value) {
            document.querySelectorAll(groupSelector).forEach((btn) => {
                btn.classList.toggle('active', btn.getAttribute(attr) === value);
            });
        }

        function renderTable(rows) {
            if (!tableBody) {
                return;
            }

            tableBody.querySelectorAll('.js-country-row').forEach((row) => {
                const code = row.getAttribute('data-country-code');
                const match = rows.some((item) => String(item.code || '').toUpperCase() === code);
                row.classList.toggle('d-none', !match);
                row.classList.remove('is-active');
            });

            const emptyRow = tableBody.querySelector('.js-country-empty');
            if (emptyRow) {
                emptyRow.classList.toggle('d-none', yearCountriesMapData.length > 0);
            }

            if (countEl) {
                countEl.textContent = '(' + rows.length + ')';
            }
        }

        function destroyMap() {
            mapWrap.innerHTML = '<div id="year-countries-map-large" class="year-countries-map-large"></div>';
            mapInstance = null;
            byCode = {};
        }

        function buildMap(rows) {
            destroyMap();

            if (!rows.length) {
                if (legendEl) {
                    legendEl.classList.add('d-none');
                }
                if (emptyEl) {
                    emptyEl.classList.remove('d-none');
                }
                return;
            }

            if (legendEl) {
                legendEl.classList.remove('d-none');
            }
            if (emptyEl) {
                emptyEl.classList.add('d-none');
            }

            const rawValues = {};
            const focusRegions = [];

            rows.forEach((row) => {
                const code = String(row.code || '').toUpperCase();
                const weight = Math.max(1, rowWeight(row));
                byCode[code] = row;
                rawValues[code] = weight;
                focusRegions.push(code);

                if (code === 'GB') {
                    byCode.UK = row;
                    rawValues.UK = weight;
                    focusRegions.push('UK');
                }
            });

            const choropleth = window.HemsoCountryChoropleth;
            const values = choropleth.toSeriesValues(rawValues);

            let focusOn = focusRegions.length
                ? { regions: focusRegions, animate: true }
                : undefined;

            if (scope === 'europe') {
                const europeanFocus = focusRegions.filter((code) => EUROPE_CODES.has(code));
                focusOn = {
                    regions: europeanFocus.length ? europeanFocus : EUROPE_FRAME,
                    animate: true,
                };
            }

            mapInstance = new jsVectorMap({
                selector: '#year-countries-map-large',
                map: 'world',
                backgroundColor: 'transparent',
                zoomOnScroll: true,
                zoomButtons: true,
                focusOn: focusOn,
                regionStyle: {
                    initial: {
                        fill: '#e2e8f0',
                        stroke: '#94a3b8',
                        strokeWidth: 0.45,
                    },
                    hover: {
                        fill: '#93c5fd',
                    },
                },
                series: {
                    regions: [
                        {
                            attribute: 'fill',
                            scale: choropleth.scale,
                            values: values,
                            normalizeFunction: choropleth.normalizeFunction,
                        },
                    ],
                },
                onRegionTooltipShow(event, tooltip, code) {
                    const row = byCode[code];
                    if (!row) {
                        tooltip.text(code);
                        return;
                    }

                    tooltip.text(
                        row.name
                        + ' — '
                        + row.bookings + ' bokningar, '
                        + row.people + ' personer, '
                        + row.noted_days + ' noterade dagar'
                    );
                },
            });
        }

        function refresh() {
            const rows = filteredRows();
            renderTable(rows);
            buildMap(rows);
            if (exportBtn) {
                exportBtn.disabled = rows.length === 0;
            }
        }

        function focusCountry(code) {
            if (!mapInstance || !code) {
                return;
            }

            try {
                mapInstance.setFocus({ region: code, animate: true });
            } catch (error) {
                try {
                    mapInstance.setFocus({ regions: [code], animate: true });
                } catch (ignored) {
                    // Region may be missing from the map geometry.
                }
            }

            tableBody?.querySelectorAll('.js-country-row').forEach((row) => {
                row.classList.toggle('is-active', row.getAttribute('data-country-code') === code);
            });
        }

        document.querySelectorAll('.js-map-source').forEach((btn) => {
            btn.addEventListener('click', () => {
                source = btn.getAttribute('data-source') || 'both';
                setActiveToggle('.js-map-source', 'data-source', source);
                refresh();
            });
        });

        document.querySelectorAll('.js-map-scope').forEach((btn) => {
            btn.addEventListener('click', () => {
                scope = btn.getAttribute('data-scope') || 'world';
                setActiveToggle('.js-map-scope', 'data-scope', scope);
                refresh();
            });
        });

        tableBody?.addEventListener('click', (event) => {
            const row = event.target.closest('.js-country-row');
            if (!row || row.classList.contains('d-none')) {
                return;
            }
            focusCountry(row.getAttribute('data-country-code'));
        });

        tableBody?.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }
            const row = event.target.closest('.js-country-row');
            if (!row || row.classList.contains('d-none')) {
                return;
            }
            event.preventDefault();
            focusCountry(row.getAttribute('data-country-code'));
        });

        exportBtn?.addEventListener('click', async () => {
            const root = document.getElementById('year-countries-export-root');
            if (!root || typeof html2canvas !== 'function') {
                return;
            }

            const original = exportBtn.innerHTML;
            exportBtn.disabled = true;
            exportBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>' + mapUiLabels.exporting;

            try {
                const canvas = await html2canvas(root, {
                    backgroundColor: '#ffffff',
                    scale: 2,
                    useCORS: true,
                });
                const link = document.createElement('a');
                link.download = 'besokslander-karta-' + yearCountriesExportYear + '.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            } finally {
                exportBtn.disabled = filteredRows().length === 0;
                exportBtn.innerHTML = original;
            }
        });

        refresh();
    })();
</script>
@endsection