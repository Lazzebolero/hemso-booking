@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Läsare</h2>
        <div class="page-subtitle">Status för meddelandet: {{ $message->title }}</div>
    </div>

    <div class="page-actions">
        @if(Route::has('admin.system-messages.readers.export'))
            <a href="{{ route('admin.system-messages.readers.export', $message) }}" class="btn btn-outline-secondary">
                <i class="bi bi-download me-2"></i>Exportera
            </a>
        @endif

        @if(Route::has('admin.system-messages.index'))
            <a href="{{ route('admin.system-messages.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Tillbaka
            </a>
        @endif
    </div>
</div>

<div class="stats-grid mb-4">
    <div class="stats-card">
        <div class="stats-label">Berörda användare</div>
        <div class="stats-value">{{ $stats['total'] ?? 0 }}</div>
        <div class="stats-subtext">Alla användare som fått meddelandet</div>
    </div>

    <div class="stats-card">
        <div class="stats-label">Lästa</div>
        <div class="stats-value">{{ $stats['read'] ?? 0 }}</div>
        <div class="stats-subtext">Har markerat meddelandet som läst</div>
    </div>

    <div class="stats-card">
        <div class="stats-label">Kvitterade</div>
        <div class="stats-value">{{ $stats['acknowledged'] ?? 0 }}</div>
        <div class="stats-subtext">Har kvitterat meddelandet</div>
    </div>

    <div class="stats-card">
        <div class="stats-label">Ej kvitterade</div>
        <div class="stats-value">{{ $stats['unacknowledged'] ?? 0 }}</div>
        <div class="stats-subtext">Saknar fortfarande kvittering</div>
    </div>
</div>

<div class="page-card">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <div class="section-title mb-0">Lässtatus per användare</div>

        @if(!empty($onlyUnacknowledged))
            <span class="badge-soft badge-soft-warning">Filter: endast ej kvitterade</span>
        @endif
    </div>

    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Namn</th>
                    <th style="width: 130px;">Roll</th>
                    <th style="width: 170px;">Läst</th>
                    <th style="width: 170px;">Kvitterad</th>
                    <th style="width: 170px;">Stängd</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php
                        $user = $row['user'];
                        $readAt = $row['read_at'];
                        $ackAt = $row['acknowledged_at'];
                        $dismissedAt = $row['dismissed_at'];
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $user->name }}</div>
                            <div class="small-muted">{{ $user->email ?? '-' }}</div>
                        </td>
                        <td>{{ ucfirst($user->role ?? '-') }}</td>
                        <td>{{ $readAt ? \Carbon\Carbon::parse($readAt)->format('Y-m-d H:i') : '-' }}</td>
                        <td>{{ $ackAt ? \Carbon\Carbon::parse($ackAt)->format('Y-m-d H:i') : '-' }}</td>
                        <td>{{ $dismissedAt ? \Carbon\Carbon::parse($dismissedAt)->format('Y-m-d H:i') : '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center muted py-4">Inga läsare hittades.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection