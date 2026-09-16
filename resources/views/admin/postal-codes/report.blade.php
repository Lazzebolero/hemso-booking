@extends('layouts.app')

@section('content')
@php
    $summary = $report['summary'];
    $counties = $report['counties'];
    $localities = $report['localities'];
    $unmatched = $report['unmatched'];
@endphp

<div class="stats-page">
    <div class="page-header stats-header">
        <div>
            <h2 class="page-title">Postnummerrapport {{ $year }}</h2>
            <div class="page-subtitle">
                Frivilliga svenska postnummer från listan — aggregerat per län och ort.
                Separat från boknings- och besöksstatistiken.
            </div>
        </div>
        <div class="page-actions d-flex gap-2 flex-wrap">
            <a href="{{ route($prefix . '.postal-codes.edit') }}" class="btn btn-outline-secondary">
                <i class="bi bi-mailbox me-2"></i>Mata in postnummer
            </a>
        </div>
    </div>

    <div class="page-card compact-card mb-4">
        <form method="GET" action="{{ route($prefix . '.postal-codes.report') }}" class="row g-3 align-items-end">
            <div class="col-auto">
                <label class="form-label" for="postal_report_year">År</label>
                <select class="form-select" id="postal_report_year" name="year" style="width: 8rem;">
                    @foreach($availableYears as $yearOption)
                        <option value="{{ $yearOption }}" @selected((int) $year === (int) $yearOption)>{{ $yearOption }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Visa</button>
            </div>
        </form>
    </div>

    <div class="stats-kpi-grid mb-4">
        <div class="stats-card premium-kpi">
            <div class="stats-label">Personer</div>
            <div class="stats-value">{{ $summary['people'] }}</div>
            <div class="stats-subtext">{{ $summary['entries'] }} rader · {{ $summary['collection_days'] }} dagar</div>
        </div>
        <div class="stats-card premium-kpi">
            <div class="stats-label">Unika postnummer</div>
            <div class="stats-value">{{ $summary['unique_codes'] }}</div>
            <div class="stats-subtext">{{ $summary['matched_entries'] }} matchade</div>
        </div>
        <div class="stats-card premium-kpi">
            <div class="stats-label">Län med data</div>
            <div class="stats-value">{{ count($counties) }}</div>
            <div class="stats-subtext">
                @if($summary['unmatched_entries'] > 0)
                    {{ $summary['unmatched_entries'] }} utan uppslag
                @else
                    Alla matchade
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="page-card">
                <div class="section-title mb-1">Sverigekarta – personer per län</div>
                <div class="page-subtitle mb-3">
                    Mörkare blått = fler personer. Klicka på ett län i listan för att markera.
                </div>
                @if(count($counties) > 0)
                    <div id="postal-sweden-map" class="postal-sweden-map" aria-label="Sverigekarta per län">
                        <div class="postal-sweden-map-loading muted p-4 text-center">Laddar karta…</div>
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-3 small-muted">
                        <span>Få</span>
                        <span class="year-countries-map-legend-swatch year-countries-map-legend-low"></span>
                        <span class="flex-grow-1 year-countries-map-legend-bar"></span>
                        <span class="year-countries-map-legend-swatch year-countries-map-legend-high"></span>
                        <span>Fler</span>
                    </div>
                    <div class="small-muted mt-2">Kartunderlag: förenklade länspolygoner (Open Knowledge Sweden).</div>
                @else
                    <div class="muted py-4 text-center">Ingen postnummerdata för {{ $year }}.</div>
                @endif
            </div>
        </div>
        <div class="col-lg-5">
            <div class="page-card">
                <div class="section-title mb-1">Län ({{ count($counties) }})</div>
                <div class="page-subtitle mb-3">Klicka + för orter. Klicka på länsnamnet för att markera kartan.</div>
                <div class="table-responsive-modern" x-data="{ openCounty: null }">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th style="width: 44px;"></th>
                                <th>Län</th>
                                <th style="width: 90px;">Personer</th>
                                <th style="width: 80px;">Rader</th>
                                <th style="width: 90px;">Postnr</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($counties as $index => $row)
                                @php
                                    $countyLocalities = $row['localities'] ?? [];
                                    $hasLocalities = count($countyLocalities) > 0;
                                @endphp
                                <tr
                                    class="js-county-row"
                                    data-map-name="{{ $row['map_name'] ?? '' }}"
                                >
                                    <td>
                                        @if($hasLocalities)
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary postal-county-toggle"
                                                @click.stop="openCounty = openCounty === {{ $index }} ? null : {{ $index }}"
                                                :aria-expanded="openCounty === {{ $index }} ? 'true' : 'false'"
                                                aria-label="Visa orter i {{ $row['name'] }}"
                                            >
                                                <i class="bi" :class="openCounty === {{ $index }} ? 'bi-dash-lg' : 'bi-plus-lg'"></i>
                                            </button>
                                        @endif
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            class="postal-county-name-btn fw-semibold js-county-map-trigger"
                                            data-map-name="{{ $row['map_name'] ?? '' }}"
                                        >
                                            {{ $row['name'] }}
                                        </button>
                                    </td>
                                    <td>{{ $row['people'] }}</td>
                                    <td>{{ $row['entries'] }}</td>
                                    <td>{{ $row['unique_codes'] }}</td>
                                </tr>
                                @if($hasLocalities)
                                    <tr class="postal-county-localities" x-show="openCounty === {{ $index }}" x-cloak>
                                        <td></td>
                                        <td colspan="4" class="py-2">
                                            <table class="table-modern mb-0 postal-locality-nested">
                                                <thead>
                                                    <tr>
                                                        <th>Ort</th>
                                                        <th style="width: 90px;">Personer</th>
                                                        <th style="width: 80px;">Rader</th>
                                                        <th style="width: 90px;">Postnr</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($countyLocalities as $locality)
                                                        <tr>
                                                            <td class="small-muted">{{ $locality['name'] }}</td>
                                                            <td>{{ $locality['people'] }}</td>
                                                            <td>{{ $locality['entries'] }}</td>
                                                            <td>{{ $locality['unique_codes'] }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center muted py-4">Ingen data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if(count($counties) > 0)
        <div class="page-card mb-4">
            <div class="section-title mb-1">Personer per län (diagram)</div>
            <div class="page-subtitle mb-3">Samma rangordning som kartan.</div>
            <div class="chart-shell postal-counties-chart-shell">
                <canvas id="postalCountiesChart" height="{{ max(280, count($counties) * 28) }}"></canvas>
            </div>
        </div>
    @endif

    <div class="page-card mb-4">
        <div class="section-title mb-1">Vanligaste orter</div>
        <div class="page-subtitle mb-3">Topp {{ count($localities) }} utifrån antal personer.</div>
        <div class="table-responsive-modern">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Ort</th>
                        <th>Län</th>
                        <th style="width: 100px;">Personer</th>
                        <th style="width: 90px;">Rader</th>
                        <th style="width: 100px;">Postnummer</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($localities as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row['name'] }}</td>
                            <td class="small-muted">{{ $row['county_name'] ?: '–' }}</td>
                            <td>{{ $row['people'] }}</td>
                            <td>{{ $row['entries'] }}</td>
                            <td>{{ $row['unique_codes'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center muted py-4">Ingen ortsdata.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(count($unmatched) > 0)
        <div class="page-card mb-4">
            <div class="section-title mb-1">Utan uppslag</div>
            <div class="page-subtitle mb-3">
                Postnummer som saknades i registret när de sparades.
                Öppna dagen och spara om efter uppdaterat register.
            </div>
            <div class="table-responsive-modern">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Postnummer</th>
                            <th style="width: 100px;">Personer</th>
                            <th style="width: 90px;">Rader</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($unmatched as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['postal_code'] }}</td>
                                <td>{{ $row['people'] }}</td>
                                <td>{{ $row['entries'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

@if(count($counties) > 0)
<style>
.postal-sweden-map {
    height: 520px;
    width: 100%;
    background: linear-gradient(180deg, #fbfdff 0%, #f8fafc 100%);
    border: 1px solid var(--brand-line-soft);
    border-radius: 16px;
    overflow: hidden;
}
.postal-sweden-map .leaflet-container {
    height: 100%;
    width: 100%;
    background: transparent;
    font: inherit;
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
.postal-counties-chart-shell {
    position: relative;
    min-height: 280px;
}
#postal-codes-table tbody tr.js-county-row,
.table-modern tbody tr.js-county-row {
    cursor: default;
}
.table-modern tbody tr.js-county-row.is-active {
    background: rgba(37, 99, 235, 0.08);
}
.postal-county-name-btn {
    background: none;
    border: 0;
    padding: 0;
    color: inherit;
    text-align: left;
    cursor: pointer;
}
.postal-county-name-btn:hover {
    color: #1d4ed8;
    text-decoration: underline;
}
.postal-county-toggle {
    width: 2rem;
    height: 2rem;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.postal-locality-nested {
    background: rgba(248, 250, 252, 0.9);
    border: 1px solid rgba(148, 163, 184, 0.25);
    border-radius: 10px;
}
.postal-locality-nested th,
.postal-locality-nested td {
    font-size: 0.92rem;
}
</style>
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@include('partials.admin.year-countries-choropleth')
<script>
(function () {
    const counties = @json($counties);
    const geoUrl = @json(asset('maps/sweden-lan.geojson'));
    const choropleth = window.HemsoCountryChoropleth;
    const scale = choropleth ? choropleth.scale : ['#dbeafe', '#93c5fd', '#60a5fa', '#3b82f6', '#2563eb', '#1d4ed8'];
    const emptyFill = '#e2e8f0';

    function showMapError(message) {
        const mapEl = document.getElementById('postal-sweden-map');
        if (mapEl) {
            mapEl.innerHTML = '<div class="muted p-4 text-center">' + message + '</div>';
        }
    }

    const byMapName = {};
    counties.forEach((row) => {
        if (row.map_name) {
            byMapName[row.map_name] = row;
        }
    });

    const ranks = (choropleth && choropleth.toSeriesValues)
        ? choropleth.toSeriesValues(
            Object.fromEntries(
                counties
                    .filter((row) => row.map_name)
                    .map((row) => [row.map_name, row.people])
            )
        )
        : {};
    const maxRank = Math.max(...Object.values(ranks), 1);

    function colorForPeople(mapName) {
        const rank = ranks[mapName];
        if (!rank) {
            return emptyFill;
        }
        const t = maxRank <= 1 ? 1 : (rank - 1) / (maxRank - 1);
        const pos = t * (scale.length - 1);
        const high = Math.min(scale.length - 1, Math.ceil(pos));
        return scale[high] || scale[scale.length - 1];
    }

    const mapEl = document.getElementById('postal-sweden-map');
    let geoLayer = null;
    let map = null;

    if (!mapEl) {
        // no-op
    } else if (typeof L === 'undefined') {
        showMapError('Kartbiblioteket kunde inte laddas. Ladda om sidan eller kontakta support.');
    } else {
        mapEl.innerHTML = '';
        map = L.map(mapEl, {
            zoomControl: true,
            attributionControl: false,
            scrollWheelZoom: false,
            dragging: true,
            doubleClickZoom: true,
            boxZoom: false,
            keyboard: true,
        }).setView([62.5, 16.5], 4);

        fetch(geoUrl, { credentials: 'same-origin' })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then((geojson) => {
                if (!geojson || !Array.isArray(geojson.features) || geojson.features.length === 0) {
                    throw new Error('Tom GeoJSON');
                }

                geoLayer = L.geoJSON(geojson, {
                    style(feature) {
                        const name = feature?.properties?.name;
                        return {
                            fillColor: colorForPeople(name),
                            weight: 1,
                            opacity: 1,
                            color: '#94a3b8',
                            fillOpacity: 0.92,
                        };
                    },
                    onEachFeature(feature, layer) {
                        const name = feature?.properties?.name || '';
                        const row = byMapName[name];
                        const label = row
                            ? name + ' — ' + row.people + ' personer, ' + row.entries + ' rader'
                            : name + ' — ingen data';
                        layer.bindTooltip(label);
                        layer.on('click', () => highlightCounty(name));
                    },
                }).addTo(map);

                map.fitBounds(geoLayer.getBounds(), { padding: [16, 16], maxZoom: 7 });
                setTimeout(() => map.invalidateSize(), 50);
                setTimeout(() => map.invalidateSize(), 300);
            })
            .catch(() => {
                showMapError('Kunde inte ladda länspolygonerna (maps/sweden-lan.geojson). Kontrollera att filen finns på servern.');
            });
    }

    function highlightCounty(mapName) {
        document.querySelectorAll('.js-county-row').forEach((row) => {
            row.classList.toggle('is-active', row.getAttribute('data-map-name') === mapName);
        });

        if (!geoLayer) {
            return;
        }

        geoLayer.eachLayer((layer) => {
            const name = layer.feature?.properties?.name;
            const active = name === mapName;
            layer.setStyle({
                fillColor: colorForPeople(name),
                weight: active ? 2.5 : 1,
                color: active ? '#1e3a8a' : '#94a3b8',
                fillOpacity: 0.92,
            });
            if (active) {
                layer.bringToFront();
            }
        });
    }

    document.querySelectorAll('.js-county-map-trigger').forEach((button) => {
        const activate = () => {
            const mapName = button.getAttribute('data-map-name');
            if (mapName) {
                highlightCounty(mapName);
            }
        };
        button.addEventListener('click', activate);
        button.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                activate();
            }
        });
    });

    const labels = counties.map((row) => row.name);
    const people = counties.map((row) => row.people);
    const chartRanks = (choropleth && choropleth.toSeriesValues)
        ? choropleth.toSeriesValues(
            Object.fromEntries(counties.map((row, index) => ['c' + index, row.people]))
        )
        : {};
    const chartMaxRank = Math.max(...Object.values(chartRanks), 1);
    const colors = counties.map((_, index) => {
        const rank = chartRanks['c' + index] || 1;
        const t = chartMaxRank <= 1 ? 0.5 : (rank - 1) / (chartMaxRank - 1);
        const pos = t * (scale.length - 1);
        const high = Math.min(scale.length - 1, Math.ceil(pos));
        return scale[high] || scale[scale.length - 1];
    });

    const canvas = document.getElementById('postalCountiesChart');
    if (canvas && typeof Chart !== 'undefined') {
        new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Personer',
                    data: people,
                    backgroundColor: colors,
                    borderWidth: 0,
                    borderRadius: 4,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            afterLabel(context) {
                                const row = counties[context.dataIndex];
                                return row
                                    ? row.entries + ' rader · ' + row.unique_codes + ' postnummer'
                                    : '';
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        grid: { color: 'rgba(148, 163, 184, 0.2)' },
                    },
                    y: {
                        grid: { display: false },
                        ticks: { font: { size: 12 } },
                    },
                },
            },
        });
    }
})();
</script>
@endif
@endsection
