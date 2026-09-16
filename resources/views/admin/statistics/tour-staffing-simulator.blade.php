@extends('layouts.app')

@section('content')
@php
    $formatWait = fn (?float $minutes): string => $minutes === null
        ? '–'
        : number_format($minutes, $minutes == (int) $minutes ? 0 : 1, ',', ' ').' min';
@endphp

<style>
    .tour-sim-table {
        width: 100%;
        min-width: 720px;
        table-layout: fixed;
        border-collapse: collapse;
    }
    .tour-sim-table th,
    .tour-sim-table td {
        padding: 0.65rem 0.55rem;
        border-bottom: 1px solid #eef2f7;
        white-space: nowrap;
        vertical-align: middle;
    }
    .tour-sim-table thead th {
        font-size: 0.78rem;
        font-weight: 700;
        color: #64748b;
        background: #f8fafc;
        text-align: left;
    }
    .tour-sim-table .col-end { text-align: right !important; }
    .tour-sim-table tfoot td {
        font-weight: 700;
        background: #f8fafc;
        border-top: 1px solid #dbe3ee;
        border-bottom: 0;
    }
    .tour-sim-table tr.is-over-max-wait td {
        background: #fff7ed;
    }
    .tour-sim-table .badge-soft {
        display: inline-block;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0.15rem 0.45rem;
        border-radius: 999px;
        background: #ffedd5;
        color: #9a3412;
    }
    .tour-sim-table .badge-extra {
        display: inline-block;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0.15rem 0.45rem;
        border-radius: 999px;
        background: #e0f2fe;
        color: #075985;
    }
</style>

<div class="stats-page">
    <div class="page-header stats-header">
        <div>
            <h2 class="page-title">Tur-/bemanningssimulator</h2>
            <div class="page-subtitle">
                Historisk simulering utifrån när bokningar skapades (samma dag).
                Skapar inga turer — bara analys.
                Väntetid räknas från tidigast första turen (bokning före öppning = självvald väntan, räknas som 0).
                <strong>P90</strong> = 90 % av bokningarna hade högst så lång väntetid.
                Extra <strong>15-minutersstarter</strong> läggs bara in om basintervallet skulle ge längre väntan än maxgränsen.
                Med <strong>dynamiskt schema</strong> används halvtimmesstarter med färjekorrigering på heltimmar; högst en extratur per lucka (≥30 min → :15, ≥40 min → :15 eller :20), och bara om väntan till nästa huvudtid annars skulle överskrida maxgränsen.
                Om sista starten blir full öppnas en <strong>sen extratur</strong> efteråt med ledig guide (även guider som blivit klara), så bokningar efter sista huvudtid kan tas med.
                Med <strong>minska guider från</strong> kan du testa färre guider efter förmiddagstrycket (t.ex. 14:00) och se när de blir klara för restaurangen.
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.statistics.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Övrig statistik
            </a>
        </div>
    </div>

    <div class="page-card mb-4">
        <div class="section-title mb-3">Parametrar</div>
        <form method="GET" action="{{ route('admin.tour-staffing-simulator.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label" for="date">Datum</label>
                <input type="date" class="form-control" id="date" name="date" value="{{ $form['date'] }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="guide_count">Antal guider</label>
                <input type="number" min="1" max="8" class="form-control" id="guide_count" name="guide_count" value="{{ $form['guide_count'] }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="guide_start">Gemensam arbetsstart</label>
                <input type="time" class="form-control" id="guide_start" name="guide_start" value="{{ $form['guide_start'] }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="guide_starts">Individuella starter (valfritt)</label>
                <input type="text" class="form-control" id="guide_starts" name="guide_starts" value="{{ $form['guide_starts'] }}" placeholder="10:45, 11:00, 11:15">
                <div class="form-text">Kommaseparerat. Saknade fylls med gemensam start.</div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="guide_reduce_at">Minska guider från</label>
                <input type="time" class="form-control" id="guide_reduce_at" name="guide_reduce_at" value="{{ $form['guide_reduce_at'] ?? '' }}">
                <div class="form-text">Tomt = samma antal hela dagen. T.ex. 14:00 efter förmiddagstrycket.</div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="guide_count_after">Antal guider efter minskning</label>
                <input type="number" min="0" max="8" class="form-control" id="guide_count_after" name="guide_count_after" value="{{ $form['guide_count_after'] ?? '' }}">
                <div class="form-text">Gäller turstarter från tiden ovan. Övriga guider går mot restaurangen.</div>
            </div>

            <div class="col-md-3">
                <label class="form-label" for="first_tour">Första tur</label>
                <input type="time" class="form-control" id="first_tour" name="first_tour" value="{{ $form['first_tour'] }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="last_tour">Sista turstart</label>
                <input type="time" class="form-control" id="last_tour" name="last_tour" value="{{ $form['last_tour'] }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="ferry_adjustment">Färjekorrigering</label>
                <select class="form-select" id="ferry_adjustment" name="ferry_adjustment">
                    <option value="0" @selected((int) $form['ferry_adjustment'] === 0)>0 min</option>
                    <option value="10" @selected((int) $form['ferry_adjustment'] === 10)>+10 min</option>
                    <option value="-10" @selected((int) $form['ferry_adjustment'] === -10)>-10 min</option>
                </select>
                <div class="form-text">Gäller heltimmar (t.ex. 11:00 → 11:10). I dynamiskt läge sätter detta grundschemat.</div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="schedule_mode">Schemaläge</label>
                <select class="form-select" id="schedule_mode" name="schedule_mode">
                    <option value="classic" @selected(($form['schedule_mode'] ?? 'classic') === 'classic')>Klassiskt intervall</option>
                    <option value="dynamic" @selected(($form['schedule_mode'] ?? 'classic') === 'dynamic')>Dynamiskt (halvtimme + färja)</option>
                </select>
                <div class="form-text">Dynamiskt: huvudtider varje halvtimme (med färja på heltimmar). Extratur i lucka bara om maxväntan annars överskrids (≥30 → :15, ≥40 → :15 eller :20).</div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="interval">Basintervall</label>
                <select class="form-select" id="interval" name="interval">
                    <option value="60" @selected((int) $form['interval'] === 60)>60 min</option>
                    <option value="30" @selected((int) $form['interval'] === 30)>30 min</option>
                    <option value="15" @selected((int) $form['interval'] === 15)>15 min</option>
                </select>
                <div class="form-text">Används i klassiskt läge. Dynamiskt låser till halvtimmesblock.</div>
            </div>

            <div class="col-md-3">
                <label class="form-label" for="duration_minutes">Turlängd (min)</label>
                <input type="number" min="45" max="120" class="form-control" id="duration_minutes" name="duration_minutes" value="{{ $form['duration_minutes'] }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="buffer_minutes">Buffert till nästa (min)</label>
                <input type="number" min="0" max="60" class="form-control" id="buffer_minutes" name="buffer_minutes" value="{{ $form['buffer_minutes'] }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="capacity">Kapacitet/tur</label>
                <input type="number" min="1" max="50" class="form-control" id="capacity" name="capacity" value="{{ $form['capacity'] }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="max_wait_minutes">Maximal väntetid (min)</label>
                <input type="number" min="0" max="180" class="form-control" id="max_wait_minutes" name="max_wait_minutes" value="{{ $form['max_wait_minutes'] }}" required>
                <div class="form-text">Över denna gräns försöker systemet lägga in 15-minutersstarter. Turer som ändå överskrider markeras.</div>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-play-circle me-2"></i>Kör simulering
                </button>
            </div>
        </form>
    </div>

    <div class="stats-kpi-grid mb-4">
        <div class="stats-card premium-kpi">
            <div class="stats-label">Samma-dag efterfrågan</div>
            <div class="stats-value">{{ $result['demand']['people'] }}</div>
            <div class="stats-subtext">{{ $result['demand']['bookings'] }} bokningar · förbokning {{ $result['advance_bookings']['people'] }} pers</div>
        </div>
        <div class="stats-card premium-kpi">
            <div class="stats-label">Snittväntetid</div>
            <div class="stats-value">{{ $formatWait($result['metrics']['wait_avg']) }}</div>
            <div class="stats-subtext">Max {{ $formatWait($result['metrics']['wait_max']) }} · P90 {{ $formatWait($result['metrics']['wait_p90']) }}</div>
        </div>
        <div class="stats-card premium-kpi">
            <div class="stats-label">Simulerade turer</div>
            <div class="stats-value">{{ $result['metrics']['tours'] }}</div>
            <div class="stats-subtext">Faktiskt schema: {{ $result['actual']['tours'] }} turer / {{ $result['actual']['people'] }} pers</div>
        </div>
        <div class="stats-card premium-kpi">
            <div class="stats-label">Över maxväntan</div>
            <div class="stats-value">{{ $result['metrics']['tours_over_max_wait'] }}</div>
            <div class="stats-subtext">
                Mål ≤ {{ $result['metrics']['max_wait_target'] }} min ·
                {{ $result['metrics']['extra_15_tours'] }} extra 15-min-turer
            </div>
        </div>
        <div class="stats-card premium-kpi">
            <div class="stats-label">Överspill</div>
            <div class="stats-value">{{ $result['metrics']['unassigned_people'] }}</div>
            <div class="stats-subtext">{{ $result['metrics']['assigned_people'] }} tilldelade · {{ number_format($result['metrics']['guide_hours'], 1, ',', ' ') }} guidetimmar</div>
        </div>
    </div>

    <div class="page-card mb-4">
        <div class="section-title mb-1">Guideöversikt</div>
        <div class="page-subtitle mb-3">
            Sluttid = när guidens sista tur är klar (utan buffert). Då kan guiden förstärka restaurangen,
            där trycket ofta ökar när guidningarna avtar.
            @if(! empty($result['params']['guide_reduce_at']))
                Från {{ $result['params']['guide_reduce_at'] }} används högst {{ $result['params']['guide_count_after'] ?? 0 }} guider till nya turer.
            @endif
        </div>
        <div class="table-responsive-modern">
            <table class="tour-sim-table">
                <thead>
                    <tr>
                        <th>Guide</th>
                        <th>Arbetsstart</th>
                        <th class="col-end">Turer</th>
                        <th class="col-end">Personer</th>
                        <th>Sista turen klar</th>
                        <th>Tillgänglig för restaurang</th>
                        <th>Efter minskning</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($result['guides'] ?? []) as $guide)
                        <tr>
                            <td class="fw-semibold">Guide {{ $guide['guide_index'] }}</td>
                            <td>{{ $guide['work_start'] }}</td>
                            <td class="col-end">{{ $guide['tours'] }}</td>
                            <td class="col-end">{{ $guide['people'] }}</td>
                            <td>{{ $guide['last_tour_end'] ?? '–' }}</td>
                            <td>
                                @if(! empty($guide['free_for_restaurant_from']))
                                    <span class="fw-semibold">{{ $guide['free_for_restaurant_from'] }}</span>
                                @else
                                    –
                                @endif
                            </td>
                            <td>
                                @if(empty($result['params']['guide_reduce_at']))
                                    –
                                @elseif(! empty($guide['available_after_reduce']))
                                    Fortsätter
                                @else
                                    <span class="badge-soft">Släpps till restaurang</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">Inga guider i simuleringen.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="page-card mb-4">
        <div class="section-title mb-1">Simulerade turer</div>
        <div class="page-subtitle mb-3">
            Tilldelning i ordning efter <code>created_at</code>. Guide blir ledig efter turlängd + buffert.
            Orange rad = någon fick vänta längre än maxgränsen.
        </div>
        <div class="table-responsive-modern">
            <table class="tour-sim-table">
                <thead>
                    <tr>
                        <th>Start</th>
                        <th>Slut</th>
                        <th class="col-end">Guide</th>
                        <th class="col-end">Bokningar</th>
                        <th class="col-end">Personer</th>
                        <th class="col-end">Kapacitet</th>
                        <th class="col-end">Fyllnad</th>
                        <th class="col-end">Max väntan</th>
                        <th>Markering</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($result['tours'] as $tour)
                        <tr @class(['is-over-max-wait' => $tour['exceeds_max_wait']])>
                            <td class="fw-semibold">
                                {{ $tour['start'] }}
                                @if($tour['is_extra_15'])
                                    @php
                                        $extraMinute = (int) substr($tour['start'], -2);
                                        $extraLabel = in_array($extraMinute, [20, 50], true) ? '+20' : '+15';
                                    @endphp
                                    <span class="badge-extra">Extra {{ $extraLabel }}</span>
                                @endif
                            </td>
                            <td>{{ $tour['end'] }}</td>
                            <td class="col-end">{{ $tour['guide_index'] }}</td>
                            <td class="col-end">{{ $tour['bookings'] }}</td>
                            <td class="col-end">{{ $tour['people'] }}</td>
                            <td class="col-end">{{ $tour['capacity'] }}</td>
                            <td class="col-end">{{ number_format($tour['fill_percent'], 1, ',', ' ') }}%</td>
                            <td class="col-end">{{ $tour['max_guest_wait'] }} min</td>
                            <td>
                                @if($tour['exceeds_max_wait'])
                                    <span class="badge-soft">Över max ({{ $tour['people_over_max_wait'] }} pers)</span>
                                @else
                                    –
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">Inga turer behövdes eller ingen samma-dagsefterfrågan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(count($result['unassigned']) > 0)
        <div class="page-card mb-4">
            <div class="section-title mb-3">Otilldelade personer</div>
            <div class="table-responsive-modern">
                <table class="tour-sim-table">
                    <thead>
                        <tr>
                            <th>Skapad</th>
                            <th class="col-end">Bokning</th>
                            <th class="col-end">Personer</th>
                            <th>Orsak</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($result['unassigned'] as $row)
                            <tr>
                                <td>{{ $row['created_at'] }}</td>
                                <td class="col-end">#{{ $row['booking_id'] }}</td>
                                <td class="col-end">{{ $row['people'] }}</td>
                                <td>{{ $row['reason'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
