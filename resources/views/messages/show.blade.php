@extends(session('active_role') === \App\Support\Roles::GUIDE ? 'layouts.guide' : 'layouts.app')

@section('content')
@php
    $authUser = auth()->user();
    $displayName = $conversation->displayNameFor($authUser);
    $participants = $conversation->users;
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">{{ $displayName }}</h2>
        <div class="page-subtitle">
            @if($conversation->isGroup())
                Gruppchatt • {{ $participants->count() }} deltagare
            @else
                Direktmeddelande
            @endif
        </div>
    </div>

    <div class="page-actions">
        <form method="POST" action="{{ route('messages.read', $conversation) }}" data-offline-queue>
            @csrf
            <button type="submit" class="btn btn-outline-secondary">
                <i class="bi bi-check2-all me-2"></i>Markera läst
            </button>
        </form>

        <a href="{{ route('messages.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>
    </div>
</div>

<div class="messages-show-layout">
    <div class="page-card">
        <div class="section-title">Konversation</div>

        <div class="message-thread" id="message-thread">
            @forelse($conversation->messages as $message)
                @php
                    $isMine = (int) $message->user_id === (int) $authUser->id;
                @endphp

                <div class="message-bubble-row {{ $isMine ? 'message-bubble-row-mine' : '' }}">
                    <div class="message-bubble {{ $isMine ? 'message-bubble-mine' : '' }}">
                        <div class="message-bubble-head">
                            <span class="fw-semibold">{{ $message->sender?->name ?? 'Okänd användare' }}</span>
                            <span class="small-muted">
                                {{ $message->created_at?->format('Y-m-d H:i') ?? '-' }}
                                @if($message->edited_at)
                                    • redigerat
                                @endif
                            </span>
                        </div>

                        <div class="message-bubble-body">{!! nl2br(e($message->body)) !!}</div>
                    </div>
                </div>
            @empty
                <div class="empty-state-box">
                    <div class="empty-state-icon"><i class="bi bi-chat-left-text"></i></div>
                    <div class="fw-semibold mb-1">Inga meddelanden ännu</div>
                    <div class="small-muted">Skicka första meddelandet nedan.</div>
                </div>
            @endforelse
        </div>

        <form method="POST" action="{{ route('messages.send', $conversation) }}" class="mt-3" data-offline-queue data-offline-chat>
            @csrf

            <label class="form-label">Skriv svar</label>
            <textarea
                name="body"
                class="form-control reply-textarea"
                rows="5"
                placeholder="Skriv ditt svar..."
                required
            >{{ old('body') }}</textarea>

            <div class="toolbar-inline mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send-fill me-2"></i>Skicka
                </button>
            </div>
        </form>
    </div>

    <div class="page-card">
        <div class="section-title">Deltagare</div>

        <div class="participant-list">
            @foreach($participants as $user)
                @php
                    $roleText = method_exists($user, 'roles')
                        ? $user->roles->pluck('name')->implode(', ')
                        : '';
                @endphp

                <div class="participant-row">
                    <div>
                        <div class="fw-semibold">{{ $user->name }}</div>
                        <div class="small-muted">
                            {{ $roleText !== '' ? $roleText : 'Ingen roll' }}
                            @if(!empty($user->email))
                                • {{ $user->email }}
                            @endif
                        </div>
                    </div>

                    @if((int) $user->id === (int) $authUser->id)
                        <span class="badge-soft badge-soft-success">Du</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>

<style>
.messages-show-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.6fr) 320px;
    gap: 1rem;
    align-items: start;
}

.message-thread {
    display: grid;
    gap: 0.85rem;
    max-height: 62vh;
    overflow-y: auto;
    padding-right: 0.2rem;
}

.message-bubble-row {
    display: flex;
    justify-content: flex-start;
}

.message-bubble-row-mine {
    justify-content: flex-end;
}

.message-bubble {
    width: min(720px, 100%);
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 0.9rem 1rem;
}

.message-bubble-mine {
    background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
    border-color: #bfdbfe;
}

.message-bubble-head {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    align-items: center;
    margin-bottom: 0.45rem;
    flex-wrap: wrap;
}

.message-bubble-body {
    line-height: 1.6;
    color: #0f172a;
}

.reply-textarea {
    min-height: 140px !important;
}

.participant-list {
    display: grid;
    gap: 0.75rem;
}

.participant-row {
    display: flex;
    justify-content: space-between;
    gap: 0.8rem;
    align-items: center;
    padding: 0.9rem;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
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
    .messages-show-layout {
        grid-template-columns: 1fr;
    }

    .message-thread {
        max-height: none;
    }
}
</style>

<script>
    setTimeout(function () {
        if (navigator.onLine) {
            window.location.reload();
        }
    }, 30000);

    window.addEventListener('load', function () {
        const thread = document.getElementById('message-thread');
        if (thread) {
            thread.scrollTop = thread.scrollHeight;
        }
    });

    window.addEventListener('offline-queue-flushed', function (event) {
        try {
            if (!navigator.onLine) return;
            const processed = Number(event?.detail?.processed ?? 0);
            if (processed > 0) {
                window.location.reload();
            }
        } catch (e) {
        }
    });

    window.addEventListener('offline-queued', function (event) {
        try {
            const detail = event?.detail || {};
            const form = document.querySelector('form[data-offline-chat]');
            if (!form) return;

            const action = String(form.getAttribute('action') || '');
            if (!detail.url || String(detail.url) !== action) return;

            const body = String(detail.fields?.body ?? '').trim();
            if (!body) return;

            const thread = document.getElementById('message-thread');
            if (!thread) return;

            const row = document.createElement('div');
            row.className = 'message-bubble-row message-bubble-row-mine';

            const bubble = document.createElement('div');
            bubble.className = 'message-bubble message-bubble-mine';

            const head = document.createElement('div');
            head.className = 'message-bubble-head';

            const left = document.createElement('span');
            left.className = 'fw-semibold';
            left.textContent = 'Du';

            const right = document.createElement('span');
            right.className = 'small-muted';
            right.textContent = 'Skickas när du är online…';

            head.appendChild(left);
            head.appendChild(right);

            const content = document.createElement('div');
            content.className = 'message-bubble-body';
            content.textContent = body;

            bubble.appendChild(head);
            bubble.appendChild(content);
            row.appendChild(bubble);
            thread.appendChild(row);

            thread.scrollTop = thread.scrollHeight;

            // Ensure the user actually sees the queued bubble even if the page is long.
            try {
                row.scrollIntoView({ block: 'end', behavior: 'smooth' });
            } catch (e) {
            }

            try {
                window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
            } catch (e) {
                window.scrollTo(0, document.body.scrollHeight);
            }

            const textarea = form.querySelector('textarea[name="body"]');
            if (textarea) {
                textarea.value = '';
                textarea.focus();
            }
        } catch (e) {
        }
    });
</script>
@endsection