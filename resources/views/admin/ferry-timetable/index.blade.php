@extends('layouts.app')

@section('content')
@php
    $ferryTimetableRoute = \App\Support\ActiveRole::routeName('ferry-timetable.index');
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Färjetidtabell</h2>
        <div class="page-subtitle">
            Officiella avgångstider från Strinningen enligt Trafikverkets tidtabell för Hemsöleden.
            Vardag, lördag och söndag har egna tider. Fler kallelseturer på helg morgon och kväll.
        </div>
    </div>

    <div class="page-actions">
        <a href="https://www.trafikverket.se/resa-och-trafik/farjetrafik/hemsoleden/" target="_blank" rel="noopener" class="btn btn-outline-secondary">
            Trafikverket
        </a>

        @if(Route::has('ferry-schedule.index'))
            <a href="{{ route('ferry-schedule.index') }}" class="btn btn-outline-secondary">
                Visa färjeläge
            </a>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="page-card mb-4">
    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach(\App\Support\FerryDayTypes::labels() as $type => $label)
            <a
                href="{{ route($ferryTimetableRoute, ['day_type' => $type]) }}"
                class="btn btn-sm {{ $dayType === $type ? 'btn-primary' : 'btn-outline-secondary' }}"
            >
                {{ $label }}
            </a>
        @endforeach
    </div>

    @if(\App\Support\ActiveRole::isAdmin() && Route::has('admin.ferry-timetable.settings'))
    <form method="POST" action="{{ route('admin.ferry-timetable.settings') }}" class="row g-3 align-items-end">
        @csrf
        <input type="hidden" name="day_type" value="{{ $dayType }}">
        <div class="col-md-4">
            <label class="form-label">Marginal för korrigeringsvarning (min)</label>
            <input
                type="number"
                min="1"
                max="180"
                name="ferry_adjustment_margin_minutes"
                class="form-control"
                value="{{ old('ferry_adjustment_margin_minutes', $marginMinutes) }}"
                required
            >
            <div class="form-text">Används internt vid bedömning av kort marginal till färja – påverkar inte själva färjetrafiken.</div>
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary">Spara inställning</button>
        </div>
    </form>
    @endif

    <div class="mt-3 small-muted">
        @if($trafficLive ?? false)
            Live-trafik från Trafikverket är aktiv
            @if(!empty($trafficFetchedAt))
                · senast {{ \Carbon\Carbon::parse($trafficFetchedAt)->format('H:i') }}
            @endif
        @else
            Live-trafik saknas. Lägg till <code>TRAFIKVERKET_API_KEY</code> i <code>.env</code> och kör <code>php artisan ferry:sync-traffic</code>.
        @endif
    </div>
</div>

<div class="page-card mb-4">
    <div class="section-title mb-1">Strinningen</div>
    <div class="small-muted mb-3">
        Alla tider är avgångar från Strinningen mot Hemsön.
        Tidtabellversion {{ $timetableRevision ?? '?' }} · {{ count($departures) }} avgångar ({{ \App\Support\FerryDayTypes::labels()[$dayType] ?? $dayType }}).
    </div>

    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th style="width: 120px;">Tid</th>
                    <th>Kallelsetur</th>
                    <th>Dubbleringsturer</th>
                </tr>
            </thead>
            <tbody>
                @forelse($departures as $departure)
                    <tr>
                        <td class="fw-semibold">{{ $departure['time'] }}</td>
                        <td>{{ ($departure['requires_call'] ?? false) ? 'Ja' : 'Nej' }}</td>
                        <td>{{ ($departure['no_duplicates'] ?? false) ? 'Nej' : '' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="muted">Inga avgångar.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
