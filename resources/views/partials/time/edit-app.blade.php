@props(['entry'])
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
@endphp

<div class="container py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div class="d-flex align-items-center gap-2">
            <span class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center" style="width: 2.25rem; height: 2.25rem;">
                <i class="bi bi-pencil-square"></i>
            </span>
            <div>
                <h1 class="h3 mb-0">Redigera tid</h1>
                <div class="text-muted small">Originalstämplingen sparas alltid oförändrad.</div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('time.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Tillbaka
            </a>
            <a href="{{ route($prefix . '.dashboard') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-speedometer2 me-1"></i>Dashboard
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1"><i class="bi bi-exclamation-octagon me-1"></i>Kontrollera formuläret</div>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('time.update', $entry) }}" class="card shadow-sm border-0">
                @csrf
                @method('PATCH')

                <div class="card-header bg-white">
                    <div class="fw-semibold"><i class="bi bi-clock-history me-2"></i>Rapporterad tid</div>
                    <div class="text-muted small">Justera den tid som ska redovisas.</div>
                </div>

                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="start_at" class="form-label">Start</label>
                            <input type="datetime-local" name="start_at" id="start_at" class="form-control" required
                                   value="{{ old('start_at', optional($entry->start_at)->format('Y-m-d\\TH:i')) }}">
                        </div>
                        <div class="col-md-6">
                            <label for="end_at" class="form-label">Slut</label>
                            <input type="datetime-local" name="end_at" id="end_at" class="form-control"
                                   value="{{ old('end_at', optional($entry->end_at)->format('Y-m-d\\TH:i')) }}">
                        </div>
                        <div class="col-md-4">
                            <label for="break_minutes" class="form-label">Rast</label>
                            <div class="input-group">
                                <input type="number" name="break_minutes" id="break_minutes" class="form-control" min="0" max="1440"
                                       value="{{ old('break_minutes', (int) $entry->break_minutes) }}">
                                <span class="input-group-text">min</span>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Beräknad arbetad tid</label>
                            <div class="form-control bg-light fw-semibold">{{ $entry->worked_hours_formatted }}</div>
                            <div class="form-text">Uppdateras efter att du sparat ändringen.</div>
                        </div>
                        <div class="col-12">
                            <label for="user_comment" class="form-label">Kommentar</label>
                            <textarea name="user_comment" id="user_comment" class="form-control" rows="4" placeholder="Exempel: glömde stämpla ut, arbetade längre vid bokning...">{{ old('user_comment', $entry->user_comment) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-white d-flex justify-content-end gap-2">
                    <a href="{{ route('time.index') }}" class="btn btn-outline-secondary">Avbryt</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Spara ändringar
                    </button>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-shield-lock me-2"></i>Originalstämpling
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="text-muted small">Stämplat in</div>
                            <div class="fw-semibold">{{ optional($entry->clock_in_at_original)->format('Y-m-d H:i') ?? '-' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Stämplat ut</div>
                            <div class="fw-semibold">{{ optional($entry->clock_out_at_original)->format('Y-m-d H:i') ?? '-' }}</div>
                        </div>
                        <div class="col-12 mt-2">
                            <div class="text-muted small">Status</div>
                            <span class="badge {{ $entry->status_badge_class }}">{{ $entry->status_label }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-journal-text me-2"></i>Ändringslogg
                </div>
                <div class="card-body">
                    @forelse($entry->audits as $audit)
                        <div class="border-start border-3 ps-3 pb-3 mb-3">
                            <div class="small text-muted">{{ $audit->created_at->format('Y-m-d H:i') }}</div>
                            <div class="fw-semibold">{{ $audit->field }}</div>
                            <div class="small">Från: {{ $audit->old_value ?: '-' }}</div>
                            <div class="small">Till: {{ $audit->new_value ?: '-' }}</div>
                            <div class="small text-muted">Av: {{ optional($audit->changedBy)->name ?? 'System' }}</div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-journal-check display-6 d-block mb-2"></i>
                            Inga ändringar ännu.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
