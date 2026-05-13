@extends('layouts.app')

@section('content')
@php
    $riskClass = match($riskLevel) {
        'high' => 'risk-high',
        'medium' => 'risk-medium',
        default => 'risk-ok',
    };

    $riskLabel = match($riskLevel) {
        'high' => 'Hög aktivitet',
        'medium' => 'Förhöjd aktivitet',
        default => 'Normalt läge',
    };

    $riskText = match($riskLevel) {
        'high' => 'Många misslyckade försök eller flera IP-adresser har observerats.',
        'medium' => 'Några misslyckade försök har observerats. Håll koll.',
        default => 'Inget avvikande syns just nu.',
    };
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Säkerhetsöversikt</h2>
        <div class="page-subtitle">
            Snabb överblick över inloggningar, misslyckade försök och misstänkt aktivitet.
        </div>
    </div>

    <div class="page-actions">
        @if(Route::has('admin.login-events.index'))
            <a href="{{ route('admin.login-events.index') }}" class="btn btn-outline-secondary">
                Inloggningslogg
            </a>
        @endif
    </div>
</div>

<div class="security-risk-card {{ $riskClass }} mb-4">
    <div>
        <div class="risk-label">Aktuell bedömning</div>
        <div class="risk-title">{{ $riskLabel }}</div>
        <div class="risk-text">{{ $riskText }}</div>
    </div>

    <div class="risk-time">
        Uppdaterad {{ now()->format('Y-m-d H:i:s') }}
    </div>
</div>

<div class="security-stats-grid mb-4">
    <div class="security-stat-card">
        <div class="stat-label">Misslyckade 24h</div>
        <div class="stat-value">{{ $stats['failed_24h'] }}</div>
    </div>

    <div class="security-stat-card">
        <div class="stat-label">Lyckade inloggningar 24h</div>
        <div class="stat-value">{{ $stats['logins_24h'] }}</div>
    </div>

    <div class="security-stat-card">
        <div class="stat-label">Misslyckade 7 dagar</div>
        <div class="stat-value">{{ $stats['failed_7d'] }}</div>
    </div>

    <div class="security-stat-card">
        <div class="stat-label">Unika IP med fel 24h</div>
        <div class="stat-value">{{ $stats['unique_failed_ips_24h'] }}</div>
    </div>
</div>

<div class="security-layout">
    <div class="page-card">
        <div class="section-title mb-3">Flest misslyckade IP senaste 7 dagar</div>

        <div class="security-list">
            @forelse($topFailedIps as $row)
                <div class="security-row">
                    <div>
                        <div class="fw-semibold">{{ $row->ip_address }}</div>
                        <div class="small-muted">Misslyckade försök</div>
                    </div>

                    <span class="security-count {{ $row->attempts >= 5 ? 'security-count-danger' : '' }}">
                        {{ $row->attempts }}
                    </span>
                </div>
            @empty
                <div class="empty-state">Inga misslyckade IP-försök hittades.</div>
            @endforelse
        </div>
    </div>

    <div class="page-card">
        <div class="section-title mb-3">Flest misslyckade e-postadresser senaste 7 dagar</div>

        <div class="security-list">
            @forelse($topFailedEmails as $row)
                <div class="security-row">
                    <div>
                        <div class="fw-semibold">{{ $row->email }}</div>
                        <div class="small-muted">Misslyckade försök</div>
                    </div>

                    <span class="security-count {{ $row->attempts >= 5 ? 'security-count-danger' : '' }}">
                        {{ $row->attempts }}
                    </span>
                </div>
            @empty
                <div class="empty-state">Inga misslyckade e-postförsök hittades.</div>
            @endforelse
        </div>
    </div>
</div>

<div class="security-layout mt-4">
    <div class="page-card">
        <div class="section-title mb-3">Senaste misslyckade försök</div>

        <div class="table-responsive-modern">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Tid</th>
                        <th>E-post</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentFailed as $event)
                        <tr class="failed-row">
                            <td>{{ $event->occurred_at?->format('Y-m-d H:i:s') }}</td>
                            <td>{{ $event->email ?: '-' }}</td>
                            <td>{{ $event->ip_address ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center muted py-4">
                                Inga misslyckade försök.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="page-card">
        <div class="section-title mb-3">Senaste lyckade inloggningar</div>

        <div class="table-responsive-modern">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Tid</th>
                        <th>Användare</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentLogins as $event)
                        <tr>
                            <td>{{ $event->occurred_at?->format('Y-m-d H:i:s') }}</td>
                            <td>{{ $event->user?->name ?? $event->email ?? '-' }}</td>
                            <td>{{ $event->ip_address ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center muted py-4">
                                Inga inloggningar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.security-risk-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    border-radius: 16px;
    padding: 1.1rem 1.25rem;
    background: #ffffff;
    border: 1px solid var(--brand-line-soft);
}

.risk-label {
    color: #64748b;
    font-size: 0.78rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.risk-title {
    font-size: 1.4rem;
    font-weight: 900;
    margin-top: 0.15rem;
}

.risk-text {
    color: #64748b;
    margin-top: 0.2rem;
}

.risk-time {
    color: #64748b;
    font-weight: 700;
    white-space: nowrap;
}

.risk-ok {
    border-left: 5px solid #22c55e;
}

.risk-medium {
    border-left: 5px solid #f59e0b;
}

.risk-high {
    border-left: 5px solid #ef4444;
}

.security-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
}

.security-stat-card {
    background: #ffffff;
    border: 1px solid var(--brand-line-soft);
    border-radius: 14px;
    padding: 1rem;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
}

.stat-label {
    color: #64748b;
    font-size: 0.78rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.stat-value {
    margin-top: 0.25rem;
    font-size: 2rem;
    font-weight: 900;
    line-height: 1;
}

.security-layout {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}

.security-list {
    display: grid;
    gap: 0.65rem;
}

.security-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    border: 1px solid var(--brand-line-soft);
    background: #ffffff;
    border-radius: 12px;
    padding: 0.85rem 0.95rem;
}

.security-count {
    min-width: 42px;
    text-align: center;
    border-radius: 999px;
    padding: 0.35rem 0.7rem;
    font-weight: 900;
    background: #f1f5f9;
    color: #334155;
}

.security-count-danger {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

.failed-row {
    background: #fff7ed;
}

.empty-state {
    border: 1px dashed var(--brand-line-soft);
    border-radius: 12px;
    padding: 1rem;
    background: #f8fafc;
    color: #64748b;
}

@media (max-width: 1100px) {
    .security-stats-grid,
    .security-layout {
        grid-template-columns: 1fr;
    }

    .security-risk-card {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
@endsection