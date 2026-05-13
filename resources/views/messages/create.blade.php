@extends(session('active_role') === \App\Support\Roles::GUIDE ? 'layouts.guide' : 'layouts.app')
@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Nytt PM</h2>
        <div class="page-subtitle">Starta ett direktmeddelande med en användare.</div>
    </div>

    <div class="page-actions">
        <a href="{{ route('messages.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>
    </div>
</div>

<form method="POST" action="{{ route('messages.direct.store') }}" data-offline-queue data-offline-chat-create>
    @csrf

    <div class="message-form-layout">
        <div class="page-card">
            <div class="section-title">Mottagare och meddelande</div>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Mottagare</label>
                    <select name="user_id" class="form-select" required>
                        <option value="">Välj användare</option>
                        @foreach($users as $user)
                            @php
                                $roleText = method_exists($user, 'roles')
                                    ? $user->roles->pluck('name')->implode(', ')
                                    : '';
                            @endphp
                            <option value="{{ $user->id }}" @selected((string) old('user_id') === (string) $user->id)>
                                {{ $user->name }}@if($roleText !== '') — {{ $roleText }}@endif @if(!empty($user->email)) ({{ $user->email }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Meddelande</label>
                    <textarea
                        name="body"
                        class="form-control message-textarea"
                        rows="10"
                        placeholder="Skriv ditt meddelande här..."
                        required
                    >{{ old('body') }}</textarea>
                </div>
            </div>
        </div>

        <div class="page-card form-side-box">
            <div class="section-title">Skicka</div>

            <div class="info-item mb-3">
                <div class="fw-semibold mb-1">Direktmeddelande</div>
                <div class="small-muted">Om ni redan har en konversation används samma tråd automatiskt.</div>
            </div>

            <div class="toolbar-inline flex-column">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-send-fill me-2"></i>Skicka PM
                </button>

                <a href="{{ route('messages.index') }}" class="btn btn-outline-secondary w-100">
                    Avbryt
                </a>
            </div>
        </div>
    </div>
</form>

<style>
.message-form-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.6fr) 320px;
    gap: 1rem;
    align-items: start;
}
.message-textarea {
    min-height: 260px !important;
}
@media (max-width: 1100px) {
    .message-form-layout {
        grid-template-columns: 1fr;
    }
}
</style>
<script>
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
</script>
@endsection