@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Historik</h2>
        <div class="page-subtitle">{{ $entityType }} #{{ $entityId }}</div>
    </div>

    <div class="page-actions">
        <a href="{{ route($prefix . '.activity-logs.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>
    </div>
</div>

<div class="page-card compact-card">
    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th style="width: 160px;">Datum</th>
                    <th style="width: 150px;">Användare</th>
                    <th style="width: 120px;">Action</th>
                    <th>Beskrivning</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    @php
                        $actionClass = match($log->action) {
                            'created' => 'badge-soft badge-soft-success',
                            'updated' => 'badge-soft badge-soft-warning',
                            'deleted' => 'badge-soft badge-soft-danger',
                            default => 'badge-soft badge-soft-secondary',
                        };
                    @endphp

                    <tr>
                        <td>{{ $log->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>{{ $log->user?->name ?? 'System' }}</td>
                        <td>
                            <span class="{{ $actionClass }}">{{ $log->action ?? '—' }}</span>
                        </td>
                        <td>{{ $log->description ?? '—' }}</td>
                    </tr>

                    <tr>
                        <td colspan="4">
                            <details class="log-details">
                                <summary>Visa ändringsdata</summary>

                                <div class="log-json-grid">
                                    <div>
                                        <div class="small-muted mb-1">Före</div>
                                        <pre>{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </div>

                                    <div>
                                        <div class="small-muted mb-1">Efter</div>
                                        <pre>{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </div>
                                </div>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Ingen historik hittades.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $logs->links() }}
    </div>
</div>

<style>
.log-details {
    margin-top: 0.4rem;
}
.log-details summary {
    cursor: pointer;
    font-weight: 600;
}
.log-json-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    margin-top: 0.6rem;
}
.log-json-grid pre {
    background: #0f172a;
    color: #e2e8f0;
    padding: 0.7rem;
    border-radius: 8px;
    font-size: 0.75rem;
    max-height: 260px;
    overflow: auto;
}
@media (max-width: 900px) {
    .log-json-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection