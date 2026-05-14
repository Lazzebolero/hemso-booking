@extends('layouts.app')

@section('content')

<div class="mx-auto" style="max-width: 640px;">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h1 class="h4 mb-3">
                <i class="bi bi-exclamation-triangle text-warning me-2"></i>Kontrollera innan nedladdning
            </h1>

            <p class="text-muted">
                @if($mode === 'all')
                    Det finns fortfarande poster i perioden som bör hanteras innan löneunderlag anses komplett.
                @else
                    Det finns fortfarande poster för <strong>{{ $user->name }}</strong> i perioden som bör hanteras.
                @endif
                PDF innehåller bara <strong>godkända</strong> pass, men du kan ändå ladda ner om du vill.
            </p>

            <div class="mb-3 small">
                <div><strong>Period:</strong> {{ $period['label'] }}</div>
                <div><strong>Antal poster i perioden:</strong> {{ $readiness['entry_count'] }}</div>
                <div><strong>Öppna:</strong> {{ $readiness['open_count'] }}</div>
                <div><strong>Inskickade:</strong> {{ $readiness['submitted_count'] }}</div>
                <div><strong>Korrigerade:</strong> {{ $readiness['corrected_count'] }}</div>
                <div><strong>Poster med avvikelse (varning/allvar):</strong> {{ $readiness['blocking_deviation_entry_count'] }}</div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="{{ $downloadUrl }}" class="btn btn-danger">
                    <i class="bi bi-filetype-pdf me-1"></i>Ladda ner PDF ändå
                </a>
                <a href="{{ $backUrl }}" class="btn btn-outline-secondary">Tillbaka</a>
            </div>
        </div>
    </div>
</div>

@endsection
