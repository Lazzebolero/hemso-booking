@extends('layouts.app')

@section('content')
@php
    $prefix = $prefix ?? \App\Support\ActiveRole::routePrefix();
@endphp
<div class="page-header">
    <div>
        <h2 class="page-title">Öppningskontroll</h2>
        <div class="page-subtitle">Daglig säkerhetsrutin och avvikelser från öppning av berget.</div>
    </div>
</div>

@include('partials.ui.flash-messages')

@if($openDeviations->isNotEmpty())
    <div class="page-card mb-4">
        <div class="section-title">Öppna avvikelser</div>
        <div class="table-responsive-modern">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Punkt</th>
                        <th>Beskrivning</th>
                        <th>Rapporterad av</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($openDeviations as $deviation)
                        <tr>
                            <td>{{ $deviation->openingCheck?->check_date?->format('Y-m-d') }}</td>
                            <td>{{ $deviation->checkpointLabel() }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($deviation->description, 90) }}</td>
                            <td>{{ $deviation->reporter?->name ?? '-' }}</td>
                            <td>
                                <a href="{{ route($prefix.'.opening-checks.show', $deviation->openingCheck) }}" class="btn btn-sm btn-primary">Visa</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="page-card">
    <div class="section-title">Protokoll</div>
    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Öppningsansvarig</th>
                    <th>Status</th>
                    <th>Öppnas kl</th>
                    <th>Avvikelser</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($checks as $check)
                    <tr>
                        <td class="fw-semibold">{{ $check->check_date?->format('Y-m-d') }}</td>
                        <td>{{ $check->openedBy?->name ?? '-' }}</td>
                        <td>
                            <span class="badge-soft {{ $check->isCompleted() ? 'badge-soft-success' : 'badge-soft-warning' }}">
                                {{ $check->statusLabel() }}
                            </span>
                        </td>
                        <td>{{ $check->visitorOpensAtInput() ?? '-' }}</td>
                        <td>
                            {{ $check->deviations_count ?? 0 }}
                            @if(($check->open_deviations_count ?? 0) > 0)
                                <span class="small-muted">({{ $check->open_deviations_count }} öppna)</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route($prefix.'.opening-checks.show', $check) }}" class="btn btn-sm btn-outline-secondary">Visa</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Inga öppningskontroller har registrerats ännu.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $checks->links() }}
    </div>
</div>
@endsection
