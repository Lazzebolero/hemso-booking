@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();

    $eventLabels = [
        'login' => 'Inloggning',
        'failed' => 'Misslyckad',
        'logout' => 'Utloggning',
    ];
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Inloggningslogg</h2>
        <div class="page-subtitle">
            Visar lyckade inloggningar, misslyckade försök och utloggningar.
        </div>
    </div>
</div>

<div class="login-stats-grid mb-4">
    <div class="login-stat-card">
        <div class="stat-label">Inloggningar idag</div>
        <div class="stat-value">{{ $stats['logins_today'] }}</div>
    </div>

    <div class="login-stat-card {{ $stats['failed_today'] > 0 ? 'stat-warning' : '' }}">
        <div class="stat-label">Misslyckade idag</div>
        <div class="stat-value">{{ $stats['failed_today'] }}</div>
    </div>

    <div class="login-stat-card">
        <div class="stat-label">Unika IP idag</div>
        <div class="stat-value">{{ $stats['unique_ips_today'] }}</div>
    </div>
</div>

<div class="page-card mb-4">
    <form method="GET" action="{{ route($prefix . '.login-events.index') }}" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Typ</label>
            <select name="event_type" class="form-select">
                <option value="">Alla</option>
                <option value="login" @selected(request('event_type') === 'login')>Inloggning</option>
                <option value="failed" @selected(request('event_type') === 'failed')>Misslyckad</option>
                <option value="logout" @selected(request('event_type') === 'logout')>Utloggning</option>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label">E-post</label>
            <input type="text" name="email" class="form-control" value="{{ request('email') }}">
        </div>

        <div class="col-md-3">
            <label class="form-label">IP-adress</label>
            <input type="text" name="ip_address" class="form-control" value="{{ request('ip_address') }}">
        </div>

        <div class="col-md-3">
            <button type="submit" class="btn btn-primary">
                Filtrera
            </button>

            <a href="{{ route($prefix . '.login-events.index') }}" class="btn btn-outline-secondary">
                Rensa
            </a>
        </div>
    </form>
</div>

<div class="page-card">
    <div class="section-title mb-3">Senaste händelser</div>

    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Tid</th>
                    <th>Typ</th>
                    <th>Användare</th>
                    <th>E-post</th>
                    <th>IP</th>
                    <th>Webbläsare/enhet</th>
                </tr>
            </thead>
            <tbody>
                @forelse($events as $event)
                    @php
                        $typeClass = match($event->event_type) {
                            'login' => 'badge-soft-success',
                            'failed' => 'badge-soft-danger',
                            'logout' => 'badge-soft-secondary',
                            default => 'badge-soft-secondary',
                        };
                    @endphp

                    <tr class="{{ $event->event_type === 'failed' ? 'failed-login-row' : '' }}">
                        <td>
                            {{ $event->occurred_at?->format('Y-m-d H:i:s') }}
                        </td>

                        <td>
                            <span class="badge-soft {{ $typeClass }}">
                                {{ $eventLabels[$event->event_type] ?? $event->event_type }}
                            </span>
                        </td>

                        <td>
                            {{ $event->user?->name ?? '-' }}
                        </td>

                        <td>
                            {{ $event->email ?: '-' }}
                        </td>

                        <td>
                            {{ $event->ip_address ?: '-' }}
                        </td>

                        <td class="small-muted">
                            {{ $event->user_agent ? \Illuminate\Support\Str::limit($event->user_agent, 90) : '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center muted py-4">
                            Inga inloggningshändelser hittades.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $events->links() }}
    </div>
</div>

<style>
.login-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
}

.login-stat-card {
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

.stat-warning {
    border-left: 5px solid #f59e0b;
}

.failed-login-row {
    background: #fff7ed;
}

@media (max-width: 800px) {
    .login-stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection