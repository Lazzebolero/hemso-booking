@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
    $today = $forecast['today'] ?? [];
    $forecastDays = $forecast['forecast_days'] ?? [];
    $fetchedAt = isset($forecast['fetched_at']) ? \Illuminate\Support\Carbon::parse($forecast['fetched_at']) : null;
    $referenceTime = isset($forecast['reference_time']) ? \Illuminate\Support\Carbon::parse($forecast['reference_time']) : null;
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Väder & prognos</h2>
        <div class="page-subtitle">
            Observationer från SMHI {{ $forecast['station_name'] ?? 'Lungö A' }} och punktprognos för Hemsöområdet.
            Dagens värden är preliminära tills dygnet är slut.
        </div>
    </div>

    <div class="page-actions">
        <a href="{{ route($prefix . '.dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Dashboard
        </a>
    </div>
</div>

@php
    $warnings = $forecast['warnings'] ?? [];
    $activeWarnings = $warnings['warnings'] ?? [];
@endphp

@if(!empty($warnings['has_warnings']))
    <div class="page-card mb-4 border-warning">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
            <div>
                <h3 class="h5 mb-1">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                    SMHI-varningar
                </h3>
                <p class="small-muted mb-0">
                    Aktiva varningar och meddelanden för Västernorrlands län och närliggande havsområden, t.ex. brandrisk och höga temperaturer.
                </p>
            </div>
        </div>

        <div class="d-flex flex-column gap-3">
            @foreach($activeWarnings as $warning)
                @php
                    $severity = $warning['severity'] ?? 'MESSAGE';
                    $badgeClass = match ($severity) {
                        'RED' => 'bg-danger',
                        'ORANGE' => 'bg-warning text-dark',
                        'YELLOW' => 'bg-warning text-dark',
                        default => 'bg-secondary',
                    };
                @endphp
                <div class="p-3 rounded border">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="badge {{ $badgeClass }}">{{ $warning['severity_label'] ?? 'Varning' }}</span>
                        <span class="fw-semibold">{{ $warning['event_label'] ?? 'Vädervarning' }}</span>
                        @if(!empty($warning['area_name']))
                            <span class="small-muted">{{ $warning['area_name'] }}</span>
                        @endif
                    </div>

                    @if(!empty($warning['period_label']))
                        <div class="small-muted mb-2">{{ $warning['period_label'] }}</div>
                    @endif

                    @if(!empty($warning['incident']))
                        <p class="mb-0">{{ $warning['incident'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@else
    <div class="page-card mb-4">
        <h3 class="h6 mb-1">SMHI-varningar</h3>
        <p class="small-muted mb-0">Inga aktiva varningar eller meddelanden för Västernorrlands län eller närliggande havsområden.</p>
    </div>
@endif

@php
    $latestObservations = $forecast['latest_observations'] ?? [];
    $observationRows = $latestObservations['rows'] ?? [];
@endphp

<div class="page-card mb-4">
    <h3 class="h5 mb-3">Senaste observationerna</h3>

    @if($latestObservations['available'] ?? false)
        <div class="table-responsive-modern">
            <table class="table-modern">
                <tbody>
                    @foreach($observationRows as $row)
                        <tr>
                            <td class="fw-semibold" style="width: 38%;">{{ $row['label'] }}</td>
                            <td>
                                {{ $row['value'] }}
                                @if(!empty($row['value_detail']))
                                    <span class="small-muted">{{ $row['value_detail'] }}</span>
                                @endif
                            </td>
                            <td class="small-muted text-end" style="white-space: nowrap;">{{ $row['observed_at'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="small-muted mb-0 mt-2">Timvärden från SMHI {{ $forecast['station_name'] ?? 'Lungö A' }}.</p>
    @else
        <p class="small-muted mb-0">Senaste observationer från Lungö A kunde inte hämtas just nu.</p>
    @endif
</div>

<div class="page-card mb-4">
    <h3 class="h5 mb-3">Idag</h3>

    @if($today['available'] ?? false)
        <div class="stats-kpi-grid mb-3">
            <div class="stats-card premium-kpi">
                <div class="stats-label">Nu</div>
                <div class="stats-value">
                    @if(isset($today['temp_now']))
                        {{ rtrim(rtrim(number_format((float) $today['temp_now'], 1, '.', ''), '0'), '.') }}°
                    @else
                        –
                    @endif
                </div>
                <div class="stats-subtext">Senaste timmen</div>
            </div>
            <div class="stats-card premium-kpi">
                <div class="stats-label">Dagens observation</div>
                <div class="stats-value">{{ $today['summary'] ?? '–' }}</div>
                <div class="stats-subtext">{{ $today['label'] ?? '' }} · preliminärt</div>
            </div>
        </div>
    @else
        <p class="small-muted mb-0">Ingen live-data från Lungö A kunde hämtas just nu.</p>
    @endif
</div>

<div class="page-card mb-4">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
        <div>
            <h3 class="h5 mb-1">Prognos</h3>
            <p class="small-muted mb-0">Dagliga värden från SMHI:s punktprognos (SNOW1g) och daglig skogsbrandsrisk (FWI), {{ count($forecastDays) }} dagar framåt.</p>
        </div>
    </div>

    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Dag</th>
                    <th>Väder</th>
                    <th>Temp</th>
                    <th>Vind</th>
                    <th>Nederbörd</th>
                    <th>Brandrisk</th>
                </tr>
            </thead>
            <tbody>
                @forelse($forecastDays as $day)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $day['label'] }}</div>
                            <div class="small-muted">{{ $day['label_short'] ?? '' }}</div>
                        </td>
                        <td>{{ $day['symbol_label'] ?? '–' }}</td>
                        <td>{{ $day['temp_summary'] ?? '–' }}</td>
                        <td>{{ $day['wind_summary'] ?? '–' }}</td>
                        <td>{{ $day['precipitation_summary'] ?? '–' }}</td>
                        <td>{{ $day['fire_risk_summary'] ?? '–' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center small-muted py-4">Prognos kunde inte hämtas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="page-card">
    <p class="small-muted mb-0">
        Källa:
        <a href="https://www.smhi.se" target="_blank" rel="noopener">SMHI</a>.
        @if($fetchedAt)
            Hämtad {{ $fetchedAt->timezone(config('smhi_weather.timezone'))->format('Y-m-d H:i') }}.
        @endif
        @if($referenceTime)
            Prognosreferens {{ $referenceTime->timezone(config('smhi_weather.timezone'))->format('Y-m-d H:i') }}.
        @endif
    </p>
</div>
@endsection
