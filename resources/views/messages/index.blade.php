@extends(session('active_role') === \App\Support\Roles::GUIDE ? 'layouts.guide' : 'layouts.app')

@section('content')
@php
    $authUser = auth()->user();
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Meddelanden</h2>
        <div class="page-subtitle">Direktmeddelanden och gruppchattar.</div>
    </div>

    <div class="page-actions">
        <a href="{{ route('messages.create') }}" class="btn btn-outline-secondary">
            <i class="bi bi-person-plus me-2"></i>Nytt PM
        </a>

        <a href="{{ route('group-chats.create') }}" class="btn btn-primary">
            <i class="bi bi-people-fill me-2"></i>Ny gruppchatt
        </a>
    </div>
</div>

<div class="messages-layout">
    <div class="page-card">
        <div class="section-title">Konversationer</div>

        @if($conversations->isEmpty())
            <div class="empty-state-box">
                <div class="empty-state-icon"><i class="bi bi-chat-dots"></i></div>
                <div class="fw-semibold mb-1">Inga meddelanden ännu</div>
                <div class="small-muted mb-3">Skapa ett nytt PM eller en gruppchatt för att komma igång.</div>

                <div class="toolbar-inline">
                    <a href="{{ route('messages.create') }}" class="btn btn-outline-secondary">
                        Nytt PM
                    </a>
                    <a href="{{ route('group-chats.create') }}" class="btn btn-primary">
                        Ny gruppchatt
                    </a>
                </div>
            </div>
        @else
            <div class="conversation-list">
                @foreach($conversations as $conversation)
                    @php
                        $participantRow = $conversation->participants->firstWhere('user_id', $authUser->id);
                        $lastReadAt = $participantRow?->last_read_at;
                        $lastMessage = $conversation->latestMessage->first();
                        $isUnread = $conversation->last_message_at
                            && (!$lastReadAt || \Carbon\Carbon::parse($conversation->last_message_at)->gt($lastReadAt));

                        $displayName = $conversation->displayNameFor($authUser);
                        $subtitle = $conversation->isGroup()
                            ? ($conversation->users->count() . ' deltagare')
                            : ($conversation->users->firstWhere('id', '!=', $authUser->id)?->email ?? 'Direktmeddelande');

                        $lastMessagePreview = $lastMessage?->body
                            ? \Illuminate\Support\Str::limit($lastMessage->body, 80)
                            : 'Ingen text ännu';
                    @endphp

                    <a href="{{ route('messages.show', $conversation) }}"
                       class="conversation-row {{ $isUnread ? 'conversation-row-unread' : '' }}">
                        <div class="conversation-row-main">
                            <div class="d-flex align-items-center justify-content-between gap-3">
                                <div class="fw-semibold">{{ $displayName }}</div>

                                <div class="d-flex align-items-center gap-2">
                                    @if($conversation->isGroup())
                                        <span class="badge-soft badge-soft-secondary">
                                            <i class="bi bi-people-fill"></i>Grupp
                                        </span>
                                    @endif

                                    @if($isUnread)
                                        <span class="badge-soft badge-soft-warning">Oläst</span>
                                    @endif
                                </div>
                            </div>

                            <div class="small-muted mt-1">{{ $subtitle }}</div>

                            <div class="conversation-snippet mt-2">{{ $lastMessagePreview }}</div>
                        </div>

                        <div class="conversation-row-side">
                            @if($conversation->last_message_at)
                                {{ \Carbon\Carbon::parse($conversation->last_message_at)->format('Y-m-d H:i') }}
                            @else
                                -
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <div class="page-card">
        <div class="section-title">Snabbguide</div>

        <div class="info-item mb-3">
            <div class="fw-semibold mb-1">PM</div>
            <div class="small-muted">Skicka direktmeddelanden till en användare.</div>
        </div>

        <div class="info-item mb-3">
            <div class="fw-semibold mb-1">Gruppchatt</div>
            <div class="small-muted">Skapa en gemensam chatt för flera personer eller team.</div>
        </div>

        <div class="info-item">
            <div class="fw-semibold mb-1">Lässtatus</div>
            <div class="small-muted">Konversationer markeras som olästa tills de öppnas eller markeras som lästa.</div>
        </div>
    </div>
</div>

<style>
.messages-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.5fr) 320px;
    gap: 1rem;
    align-items: start;
}

.conversation-list {
    display: grid;
    gap: 0.75rem;
}

.conversation-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 150px;
    gap: 0.9rem;
    align-items: start;
    padding: 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    color: inherit;
    text-decoration: none;
    transition: all 0.18s ease;
}

.conversation-row:hover {
    border-color: #93c5fd;
    background: linear-gradient(180deg, #ffffff 0%, #eff6ff 100%);
}

.conversation-row-unread {
    box-shadow: inset 0 0 0 2px rgba(37, 99, 235, 0.12);
    border-color: #bfdbfe;
}

.conversation-snippet {
    color: #334155;
    line-height: 1.45;
}

.conversation-row-side {
    font-size: 0.8rem;
    color: #64748b;
    text-align: right;
    white-space: nowrap;
}

.empty-state-box {
    border: 1px dashed #cbd5e1;
    border-radius: 18px;
    padding: 2rem 1.2rem;
    text-align: center;
    background: #f8fafc;
}

.empty-state-icon {
    font-size: 2rem;
    color: #94a3b8;
    margin-bottom: 0.5rem;
}

@media (max-width: 1100px) {
    .messages-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 700px) {
    .conversation-row {
        grid-template-columns: 1fr;
    }

    .conversation-row-side {
        text-align: left;
    }
}
</style>
@endsection