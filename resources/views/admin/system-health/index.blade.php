@extends('layouts.app')

@section('content')
@php
    $overallClass = match($overallStatus) {
        'ok' => 'health-ok',
        'warning' => 'health-warning',
        'error' => 'health-error',
        default => 'health-warning',
    };

    $overallLabel = match($overallStatus) {
        'ok' => 'Systemet ser bra ut',
        'warning' => 'Systemet har varningar',
        'error' => 'Systemet har fel',
        default => 'Okänd status',
    };
@endphp

<div class="staff-page-stack">
    <x-ui.page-header
        title="Systemhälsa"
        subtitle="Varje ruta nedan är en egen kontroll. Läs sammanfattningen först, sedan detaljerna under."
        icon="bi-heart-pulse"
    >
        <x-slot:actions>
            <a href="{{ route('admin.system-health.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-arrow-clockwise me-1"></i>Uppdatera
            </a>
            @if(Route::has('admin.system-logs.index'))
                <a href="{{ route('admin.system-logs.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-file-earmark-text me-1"></i>Systemlogg
                </a>
            @endif
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
                <i class="bi bi-speedometer2 me-1"></i>Dashboard
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="page-card health-tip-card">
        <div class="section-title mb-2">Deploy & röktest</div>
        <p class="small-muted mb-0">
            Efter deploy: kör <code>php artisan deploy:smoke --strict</code> på servern.
            Se <code>DEPLOY.md</code> i projektroten.
        </p>
    </div>

    <div class="health-overview {{ $overallClass }}">
        <div>
            <div class="health-overview-label">Övergripande status</div>
            <div class="health-overview-title">{{ $overallLabel }}</div>
        </div>
        <div class="health-overview-time">
            Kontrollerad {{ $checkedAt->format('Y-m-d H:i:s') }}
        </div>
    </div>

    <div class="health-check-list">
        @foreach($checks as $check)
            @include('partials.system-health.check-card', ['check' => $check])
        @endforeach
    </div>
</div>
@endsection
