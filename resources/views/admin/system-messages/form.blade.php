@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">{{ $message->exists ? 'Redigera systemmeddelande' : 'Nytt systemmeddelande' }}</h2>
        <div class="page-subtitle">Skapa enkla och tydliga interna aviseringar.</div>
    </div>

    <div class="page-actions">
        <a href="{{ route('admin.system-messages.index') }}" class="btn btn-outline-secondary">
            Tillbaka
        </a>
    </div>
</div>

<form method="POST" action="{{ $message->exists ? route('admin.system-messages.update', $message) : route('admin.system-messages.store') }}">
    @csrf
    @if($message->exists)
        @method('PUT')
    @endif

    <div class="system-message-layout">
        <div class="page-card">
            <div class="section-title">Innehåll</div>

            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label">Prioritet</label>
                    <select name="priority" class="form-select">
                        <option value="1" @selected((string) old('priority', $message->priority ?? 2) === '1')>1 – Låg</option>
                        <option value="2" @selected((string) old('priority', $message->priority ?? 2) === '2')>2 – Normal</option>
                        <option value="3" @selected((string) old('priority', $message->priority ?? 2) === '3')>3 – Hög</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Rubrik</label>
                    <input
                        type="text"
                        name="title"
                        class="form-control system-message-title-input"
                        value="{{ old('title', $message->title) }}"
                        required
                    >
                </div>

                <div class="col-12">
                    <label class="form-label">Text</label>
                    <textarea
                        name="body"
                        class="form-control system-message-body-input"
                        rows="14"
                    >{{ old('body', $message->body) }}</textarea>
                </div>
            </div>

            <div class="section-title mt-4">Målgrupp</div>

            @php
                $selectedRoles = old('target_roles', $message->target_roles ?? ['all']);
            @endphp

            <div class="system-message-role-grid">
                <label class="form-check system-role-card">
                    <input class="form-check-input" type="checkbox" name="target_roles[]" value="all" {{ in_array('all', $selectedRoles ?? [], true) ? 'checked' : '' }}>
                    <span class="form-check-label">Alla</span>
                </label>

                <label class="form-check system-role-card">
                    <input class="form-check-input" type="checkbox" name="target_roles[]" value="admin" {{ in_array('admin', $selectedRoles ?? [], true) ? 'checked' : '' }}>
                    <span class="form-check-label">Admins</span>
                </label>

                <label class="form-check system-role-card">
                    <input class="form-check-input" type="checkbox" name="target_roles[]" value="host" {{ in_array('host', $selectedRoles ?? [], true) ? 'checked' : '' }}>
                    <span class="form-check-label">Värdar</span>
                </label>

                <label class="form-check system-role-card">
                    <input class="form-check-input" type="checkbox" name="target_roles[]" value="guide" {{ in_array('guide', $selectedRoles ?? [], true) ? 'checked' : '' }}>
                    <span class="form-check-label">Guider</span>
                </label>

                <label class="form-check system-role-card">
                    <input class="form-check-input" type="checkbox" name="target_roles[]" value="restaurant" {{ in_array('restaurant', $selectedRoles ?? [], true) ? 'checked' : '' }}>
                    <span class="form-check-label">Restaurang</span>
                </label>
            </div>

            <div class="section-title mt-4">Tidsstyrning</div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Start</label>
                    <input
                        type="datetime-local"
                        name="starts_at"
                        class="form-control"
                        value="{{ old('starts_at', $message->starts_at ? $message->starts_at->format('Y-m-d\TH:i') : '') }}"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">Slut</label>
                    <input
                        type="datetime-local"
                        name="ends_at"
                        class="form-control"
                        value="{{ old('ends_at', $message->ends_at ? $message->ends_at->format('Y-m-d\TH:i') : '') }}"
                    >
                </div>
            </div>
        </div>

        <div class="form-side-box">
            <div class="section-title">Inställningar</div>

            <label class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="is_important" value="1" {{ old('is_important', $message->is_important) ? 'checked' : '' }}>
                <span class="form-check-label">Markera som viktigt</span>
            </label>

            <label class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="popup_only" value="1" {{ old('popup_only', $message->popup_only) ? 'checked' : '' }}>
                <span class="form-check-label">Visa bara som popup</span>
            </label>

            <label class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="requires_ack" value="1" {{ old('requires_ack', $message->requires_ack) ? 'checked' : '' }}>
                <span class="form-check-label">Kräv kvittering</span>
            </label>

            <label class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="send_email" value="1" {{ old('send_email', $message->send_email) ? 'checked' : '' }}>
                <span class="form-check-label">Skicka e-post vid hög prioritet</span>
            </label>

            <div class="mb-3">
                <label class="form-label">Påminn var X minut</label>
                <input
                    type="number"
                    min="5"
                    max="10080"
                    name="remind_every_minutes"
                    class="form-control"
                    value="{{ old('remind_every_minutes', $message->remind_every_minutes) }}"
                >
                <div class="form-text">Används bara om kvittering krävs.</div>
            </div>

            @if($message->exists)
                <div class="small-muted mb-3">
                    Senaste påminnelse: {{ $message->last_reminder_at?->format('Y-m-d H:i') ?? '-' }}<br>
                    Nästa påminnelse: {{ $message->next_reminder_at?->format('Y-m-d H:i') ?? '-' }}
                </div>
            @endif

            <label class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $message->exists ? $message->is_active : true) ? 'checked' : '' }}>
                <span class="form-check-label">Aktivt</span>
            </label>

            <button class="btn btn-primary w-100" type="submit">
                {{ $message->exists ? 'Spara ändringar' : 'Skapa meddelande' }}
            </button>
        </div>
    </div>
</form>

<style>
.system-message-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.6fr) 320px;
    gap: 1rem;
    align-items: start;
}
.system-message-title-input {
    min-height: 52px !important;
    font-size: 1.02rem !important;
}
.system-message-body-input {
    min-height: 320px !important;
    font-size: 0.98rem !important;
    line-height: 1.55 !important;
}
.system-message-role-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.85rem;
}
.system-role-card {
    background: #f8fafc;
    border: 1px solid var(--brand-line-soft);
    border-radius: 14px;
    padding: 0.95rem 1rem;
}
@media (max-width: 1100px) {
    .system-message-layout {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection