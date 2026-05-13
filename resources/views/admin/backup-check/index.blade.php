@extends('layouts.app')

@section('content')
@php
    $statusClass = match($status) {
        'ok' => 'backup-ok',
        'warning' => 'backup-warning',
        'error' => 'backup-error',
        default => 'backup-warning',
    };

    $statusLabel = match($status) {
        'ok' => 'OK',
        'warning' => 'Varning',
        'error' => 'Fel',
        default => 'Okänd',
    };
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Backup-kontroll</h2>
        <div class="page-subtitle">
            Manuell driftkontroll av att backup finns och att återläsning testats.
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="backup-status-card {{ $statusClass }} mb-4">
    <div>
        <div class="backup-label">Backupstatus</div>
        <div class="backup-title">{{ $statusLabel }}</div>
        <div class="backup-message">{{ $message }}</div>
    </div>
</div>

<div class="backup-grid mb-4">
    <div class="backup-info-card">
        <div class="backup-label">Senast kontrollerad</div>
        <div class="backup-value">
            {{ $lastCheckedAt ? \Carbon\Carbon::parse($lastCheckedAt)->format('Y-m-d H:i') : '-' }}
        </div>
        <div class="small-muted">
            Av: {{ $data['last_checked_by'] ?? '-' }}
        </div>
    </div>

    <div class="backup-info-card">
        <div class="backup-label">Senaste återläsningstest</div>
        <div class="backup-value">
            {{ $lastRestoreTestAt ? \Carbon\Carbon::parse($lastRestoreTestAt)->format('Y-m-d H:i') : '-' }}
        </div>
        <div class="small-muted">
            Av: {{ $data['last_restore_test_by'] ?? '-' }}
        </div>
    </div>
</div>

<div class="page-card">
    <div class="section-title mb-3">Registrera backupkontroll</div>

    <form method="POST" action="{{ route('admin.backup-check.update') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">Notering</label>
            <textarea name="note" class="form-control" rows="4" placeholder="Ex. Kontrollerat webbhotellets backup, databasbackup finns, filer finns.">{{ old('note') }}</textarea>
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" name="mark_restore_tested" value="1" class="form-check-input" id="mark_restore_tested">
            <label class="form-check-label" for="mark_restore_tested">
                Markera även att återläsning/test har kontrollerats
            </label>
        </div>

        <button type="submit" class="btn btn-primary">
            Spara backupkontroll
        </button>
    </form>

    @if(!empty($data['last_note']))
        <hr>
        <div class="section-title mb-2">Senaste notering</div>
        <div class="backup-note">
            {{ $data['last_note'] }}
        </div>
    @endif
</div>

<style>
.backup-status-card {
    background: #ffffff;
    border: 1px solid var(--brand-line-soft);
    border-radius: 16px;
    padding: 1.1rem 1.25rem;
}

.backup-ok {
    border-left: 5px solid #22c55e;
}

.backup-warning {
    border-left: 5px solid #f59e0b;
}

.backup-error {
    border-left: 5px solid #ef4444;
}

.backup-label {
    color: #64748b;
    font-size: 0.78rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.backup-title {
    font-size: 1.4rem;
    font-weight: 900;
    margin-top: 0.15rem;
}

.backup-message {
    color: #64748b;
    margin-top: 0.2rem;
}

.backup-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}

.backup-info-card {
    background: #ffffff;
    border: 1px solid var(--brand-line-soft);
    border-radius: 14px;
    padding: 1rem;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
}

.backup-value {
    margin-top: 0.25rem;
    font-size: 1.15rem;
    font-weight: 900;
}

.backup-note {
    background: #f8fafc;
    border: 1px solid var(--brand-line-soft);
    border-radius: 12px;
    padding: 1rem;
    white-space: pre-wrap;
}

@media (max-width: 800px) {
    .backup-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection