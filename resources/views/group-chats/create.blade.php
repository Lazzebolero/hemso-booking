@extends(session('active_role') === \App\Support\Roles::GUIDE ? 'layouts.guide' : 'layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Ny gruppchatt</h2>
        <div class="page-subtitle">Skapa en grupp och välj vilka användare som ska delta.</div>
    </div>

    <div class="page-actions">
        <a href="{{ route('messages.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>
    </div>
</div>

<form method="POST" action="{{ route('group-chats.store') }}" data-offline-queue>
    @csrf

    <div class="group-create-layout">
        <div class="page-card">
            <div class="section-title">Gruppinformation</div>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Gruppnamn</label>
                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        value="{{ old('name') }}"
                        placeholder="Till exempel: Alla guider, Driftledning, Restaurang"
                        required
                    >
                </div>

                <div class="col-12">
                    <label class="form-label">Första meddelande</label>
                    <textarea
                        name="body"
                        class="form-control group-textarea"
                        rows="7"
                        placeholder="Valfritt första meddelande till gruppen"
                    >{{ old('body') }}</textarea>
                </div>

                <div class="col-12">
                    <label class="form-label">Deltagare</label>

                    <div class="participant-picker-grid">
                        @php
                            $selectedParticipants = collect(old('participant_ids', []))
                                ->map(fn ($id) => (string) $id)
                                ->all();
                        @endphp

                        @foreach($users as $user)
                            @php
                                $roleText = method_exists($user, 'roles')
                                    ? $user->roles->pluck('name')->implode(', ')
                                    : '';
                            @endphp

                            <label class="participant-chip-option">
                                <input
                                    type="checkbox"
                                    name="participant_ids[]"
                                    value="{{ $user->id }}"
                                    @checked(in_array((string) $user->id, $selectedParticipants, true))
                                >
                                <span class="participant-chip-pill">
                                    <span>
                                        <span class="participant-chip-name">{{ $user->name }}</span>
                                        <span class="participant-chip-meta">
                                            {{ $roleText !== '' ? $roleText : 'Ingen roll' }}
                                        </span>
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <div class="form-text">Du själv läggs alltid till automatiskt i gruppen.</div>
                </div>
            </div>
        </div>

        <div class="page-card form-side-box">
            <div class="section-title">Skapa grupp</div>

            <div class="info-item mb-3">
                <div class="fw-semibold mb-1">Gruppchatt</div>
                <div class="small-muted">Passar för team, arbetsgrupper eller rollbaserade samtal.</div>
            </div>

            <div class="toolbar-inline flex-column">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-people-fill me-2"></i>Skapa gruppchatt
                </button>

                <a href="{{ route('messages.index') }}" class="btn btn-outline-secondary w-100">
                    Avbryt
                </a>
            </div>
        </div>
    </div>
</form>

<style>
.group-create-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.7fr) 320px;
    gap: 1rem;
    align-items: start;
}

.group-textarea {
    min-height: 180px !important;
}

.participant-picker-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 0.75rem;
}

.participant-chip-option {
    position: relative;
    display: block;
    cursor: pointer;
}

.participant-chip-option input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.participant-chip-pill {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 58px;
    padding: 0.95rem 1rem;
    border-radius: 16px;
    border: 1px solid #dbe3ee;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    transition: all 0.18s ease;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.participant-chip-name {
    display: block;
    font-weight: 700;
    color: #0f172a;
}

.participant-chip-meta {
    display: block;
    font-size: 0.82rem;
    color: #64748b;
    margin-top: 0.1rem;
}

.participant-chip-option:hover .participant-chip-pill {
    border-color: #93c5fd;
    background: linear-gradient(180deg, #ffffff 0%, #eff6ff 100%);
}

.participant-chip-option input:checked + .participant-chip-pill {
    border-color: #2563eb;
    background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

@media (max-width: 1100px) {
    .group-create-layout {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection