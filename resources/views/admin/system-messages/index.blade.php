@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
@endphp
<div class="page-header">
    <div>
        <h2 class="page-title">Systemmeddelanden</h2>
        <div class="page-subtitle">Skapa, redigera och följ upp interna meddelanden.</div>
    </div>

    <div class="page-actions">
        @if(Route::has('admin.system-messages.create'))
            <a href="{{ route('admin.system-messages.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Nytt meddelande
            </a>
        @endif
    </div>
</div>

<div class="stats-grid mb-4">
    <div class="stats-card">
        <div class="stats-label">Totalt</div>
        <div class="stats-value">{{ $messages->total() ?? $messages->count() }}</div>
        <div class="stats-subtext">Antal systemmeddelanden</div>
    </div>

    <div class="stats-card">
        <div class="stats-label">Aktiva nu</div>
        <div class="stats-value">{{ $messages->where('is_active', true)->count() }}</div>
        <div class="stats-subtext">Som är markerade aktiva</div>
    </div>

    <div class="stats-card">
        <div class="stats-label">Stängda för mig</div>
        <div class="stats-value">{{ $dismissedMessages->count() }}</div>
        <div class="stats-subtext">Kan återställas i listan nedan</div>
    </div>

    <div class="stats-card">
        <div class="stats-label">Påminnelse</div>
        <div class="stats-value">
            @if(Route::has($prefix . '.system-messages.reminder-sweep'))
    <form method="POST" action="{{ route($prefix . '.system-messages.reminder-sweep') }}" class="d-inline">
        @csrf

        <button type="submit" class="btn btn-outline-secondary">
            <i class="bi bi-send-check me-2"></i>Kör nu
        </button>
    </form>
@endif
        </div>
        <div class="stats-subtext">Manuell körning av påminnelser</div>
    </div>
</div>

<div class="page-card mb-4">
    <div class="section-title">Alla systemmeddelanden</div>

    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Rubrik</th>
                    <th style="width: 150px;">Målgrupp</th>
                    <th style="width: 110px;">Prioritet</th>
                    <th style="width: 90px;">Aktiv</th>
                    <th style="width: 170px;">Start</th>
                    <th style="width: 170px;">Slut</th>
                    <th style="width: 150px;">Skapad av</th>
                    <th style="width: 280px;">Åtgärder</th>
                </tr>
            </thead>
            <tbody>
                @forelse($messages as $message)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $message->title }}</div>

                            @if($message->body)
                                <div class="small-muted">{{ \Illuminate\Support\Str::limit($message->body, 120) }}</div>
                            @endif

                            <div class="toolbar-inline mt-2">
                                @if($message->popup_only)
                                    <span class="badge-soft badge-soft-warning">Popup</span>
                                @endif

                                @if($message->requires_ack)
                                    <span class="badge-soft badge-soft-danger">Kvittering krävs</span>
                                @endif

                                @if($message->send_email)
                                    <span class="badge-soft badge-soft-secondary">E-post</span>
                                @endif
                            </div>
                        </td>

                        <td>{{ $message->target_roles_label ?? '-' }}</td>

                        <td>
                            @if($message->is_important)
                                <span class="badge-soft badge-soft-danger">Viktigt</span>
                            @else
                                <span class="badge-soft badge-soft-secondary">Normal</span>
                            @endif
                        </td>

                        <td>
                            @if($message->is_active)
                                <span class="badge-soft badge-soft-success">Ja</span>
                            @else
                                <span class="badge-soft badge-soft-secondary">Nej</span>
                            @endif
                        </td>

                        <td>{{ $message->starts_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>{{ $message->ends_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>{{ $message->creator?->name ?? '-' }}</td>

                        <td>
                            <div class="toolbar-inline">
                                @if(Route::has('admin.system-messages.edit'))
                                    <a href="{{ route('admin.system-messages.edit', $message) }}" class="btn btn-sm btn-outline-secondary">
                                        Redigera
                                    </a>
                                @endif

                                @if(Route::has('admin.system-messages.readers'))
                                    <a href="{{ route('admin.system-messages.readers', $message) }}" class="btn btn-sm btn-outline-secondary">
                                        Läsare
                                    </a>
                                @endif

                                @if(Route::has('admin.system-messages.destroy'))
                                    <form method="POST" action="{{ route('admin.system-messages.destroy', $message) }}" onsubmit="return confirm('Ta bort meddelandet?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Ta bort</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center muted py-4">Inga systemmeddelanden finns ännu.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $messages->links() }}
    </div>
</div>

<div class="page-card">
    <div class="section-title">Stängda för mig</div>

    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Rubrik</th>
                    <th style="width: 150px;">Målgrupp</th>
                    <th style="width: 170px;">Start</th>
                    <th style="width: 170px;">Slut</th>
                    <th style="width: 170px;">Stängd</th>
                    <th style="width: 160px;">Åtgärd</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dismissedMessages as $message)
                    @php
                        $dismissedAt = $message->users->first()?->pivot?->dismissed_at;
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $message->title }}</div>
                            @if($message->body)
                                <div class="small-muted">{{ \Illuminate\Support\Str::limit($message->body, 100) }}</div>
                            @endif
                        </td>
                        <td>{{ $message->target_roles_label ?? '-' }}</td>
                        <td>{{ $message->starts_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>{{ $message->ends_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>{{ $dismissedAt ? \Carbon\Carbon::parse($dismissedAt)->format('Y-m-d H:i') : '-' }}</td>
                        <td>
                            @if(Route::has('system-messages.restore'))
                                <form method="POST" action="{{ route('system-messages.restore', $message) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Visa igen</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center muted py-4">Du har inga stängda meddelanden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
<script>
document.addEventListener('DOMContentLoaded', function () {
    async function refreshSystemMessagesPanel() {
        try {
            const response = await fetch('{{ route('system-messages.live-panel') }}', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            });

            if (!response.ok) return;

            const data = await response.json();

            const badge = document.querySelector('[data-system-message-count]');
            if (badge) {
                badge.textContent = data.unread_count ?? 0;
                badge.style.display = (data.unread_count ?? 0) > 0 ? '' : 'none';
            }
        } catch (error) {
            console.error('Systemmeddelanden kunde inte uppdateras', error);
        }
    }

    refreshSystemMessagesPanel();
    setInterval(refreshSystemMessagesPanel, 30000);
});
</script>