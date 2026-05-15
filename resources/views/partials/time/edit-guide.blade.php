@props(['entry'])
<div class="page-header">
    <div>
        <div class="section-title mb-1">
            <i class="bi bi-pencil-square me-2"></i>Redigera tid
        </div>
        <div class="small-muted">
            Originalstämplingen sparas alltid oförändrad.
        </div>
    </div>

    <a href="{{ route('time.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Tillbaka
    </a>
</div>

@if($errors->any())
    <div class="system-message-banner system-message-important mb-3">
        <div class="system-message-title">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>Kontrollera formuläret
        </div>
        <div class="system-message-body">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('time.update', $entry) }}" class="page-card">
            @csrf
            @method('PATCH')

            <div class="section-title mb-3">Rapporterad tid</div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="start_at" class="form-label">Start</label>
                    <input type="datetime-local"
                           name="start_at"
                           id="start_at"
                           class="form-control"
                           required
                           value="{{ old('start_at', optional($entry->start_at)->format('Y-m-d\\TH:i')) }}">
                </div>

                <div class="col-md-6">
                    <label for="end_at" class="form-label">Slut</label>
                    <input type="datetime-local"
                           name="end_at"
                           id="end_at"
                           class="form-control"
                           value="{{ old('end_at', optional($entry->end_at)->format('Y-m-d\\TH:i')) }}">
                </div>

                <div class="col-md-4">
                    <label for="break_minutes" class="form-label">Rast i minuter</label>
                    <input type="number"
                           name="break_minutes"
                           id="break_minutes"
                           class="form-control"
                           min="0"
                           max="1440"
                           value="{{ old('break_minutes', (int) $entry->break_minutes) }}">
                </div>

                <div class="col-md-8">
                    <label class="form-label">Beräknad arbetad tid</label>
                    <div class="form-control bg-light">{{ $entry->worked_hours_formatted }}</div>
                </div>

                <div class="col-12">
                    <label for="user_comment" class="form-label">Kommentar</label>
                    <textarea name="user_comment"
                              id="user_comment"
                              class="form-control"
                              rows="4">{{ old('user_comment', $entry->user_comment) }}</textarea>
                </div>
            </div>

            <div class="toolbar-inline justify-content-end mt-4">
                <a href="{{ route('time.index') }}" class="btn btn-outline-secondary">Avbryt</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Spara
                </button>
            </div>
        </form>
    </div>

    <div class="col-lg-4">
        <div class="page-card mb-3">
            <div class="section-title mb-3">Originalstämpling</div>

            <div class="info-item mb-2">
                <div class="small-muted mb-1">Stämplat in</div>
                <div class="fw-semibold">{{ optional($entry->clock_in_at_original)->format('Y-m-d H:i') ?? '-' }}</div>
            </div>

            <div class="info-item mb-2">
                <div class="small-muted mb-1">Stämplat ut</div>
                <div class="fw-semibold">{{ optional($entry->clock_out_at_original)->format('Y-m-d H:i') ?? '-' }}</div>
            </div>

            <div class="info-item">
                <div class="small-muted mb-1">Status</div>
                <span class="{{ $entry->status === \App\Models\TimeEntry::STATUS_OPEN ? 'badge-soft badge-soft-warning' : ($entry->status === \App\Models\TimeEntry::STATUS_SUBMITTED ? 'badge-soft badge-soft-success' : 'badge-soft badge-soft-secondary') }}">
                    {{ $entry->status_label }}
                </span>
            </div>
        </div>

        <div class="page-card">
            <div class="section-title mb-3">Ändringslogg</div>

            @forelse($entry->audits as $audit)
                <div class="guide-audit-row">
                    <div class="small-muted">{{ $audit->created_at->format('Y-m-d H:i') }}</div>
                    <div class="fw-semibold">{{ $audit->field }}</div>
                    <div class="small">Från: {{ $audit->old_value ?: '-' }}</div>
                    <div class="small">Till: {{ $audit->new_value ?: '-' }}</div>
                    <div class="small-muted">Av: {{ optional($audit->changedBy)->name ?? 'System' }}</div>
                </div>
            @empty
                <div class="small-muted">Inga ändringar ännu.</div>
            @endforelse
        </div>
    </div>
</div>

