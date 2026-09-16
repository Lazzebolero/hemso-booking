@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Turtyper</h2>
        <div class="page-subtitle">Turlängd, bokningssekvens och auto-avslut styrs här per turtyp.</div>
    </div>
</div>

<div class="page-card compact-card mb-3">
    <div class="section-title">Ny turtyp</div>

    <div class="tourtype-table-wrap">
        <div class="tourtype-grid tourtype-grid-head">
            <div>Namn</div>
            <div>Sortering</div>
            <div>Standardlängd</div>
            <div>Aktiv</div>
            <div>Förvald</div>
            <div>Bokningssekvens</div>
            <div>Auto-avslut</div>
            <div>Marginal</div>
            <div>Spara</div>
            <div></div>
        </div>

        <form method="POST" action="{{ route('admin.tour-types.store') }}" class="tourtype-grid tourtype-grid-row">
            @csrf
            <div>
                <input type="text" name="name" class="form-control" placeholder="Ex. Guidad visning" required>
            </div>
            <div>
                <input type="number" name="sort_order" class="form-control" value="0" min="0">
            </div>
            <div>
                <input
                    type="number"
                    name="default_duration_minutes"
                    class="form-control"
                    min="1"
                    max="1440"
                    value="75"
                    required
                >
            </div>
            <div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="new_tour_type_active" checked>
                    <label class="form-check-label" for="new_tour_type_active">Aktiv</label>
                </div>
            </div>
            <div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_default" value="1" id="new_tour_type_default">
                    <label class="form-check-label" for="new_tour_type_default">Förvald</label>
                </div>
            </div>
            <div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="include_in_booking_sequence" value="1" id="new_tour_type_booking_sequence">
                    <label class="form-check-label" for="new_tour_type_booking_sequence">Ja</label>
                </div>
            </div>
            <div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="auto_complete_enabled" value="1" id="new_tour_type_auto_complete" checked>
                    <label class="form-check-label" for="new_tour_type_auto_complete">Ja</label>
                </div>
            </div>
            <div>
                <input
                    type="number"
                    name="auto_complete_grace_minutes"
                    class="form-control"
                    min="0"
                    max="120"
                    value="15"
                    required
                    title="Minuter efter planerad sluttid"
                >
            </div>
            <div>
                <button class="btn btn-primary w-100">Spara</button>
            </div>
            <div></div>
        </form>
    </div>
    <div class="form-text mt-2">Marginal = minuter efter planerad sluttid innan turen avslutas automatiskt. Förlängda turer avslutas inte automatiskt.</div>
</div>

<div class="page-card compact-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="section-title mb-0">Befintliga turtyper</div>
        <div class="small-muted">{{ count($tourTypes) }} turtyper</div>
    </div>

    <div class="tourtype-table-wrap">
        <div class="tourtype-grid tourtype-grid-head">
            <div>Namn</div>
            <div>Sortering</div>
            <div>Standardlängd</div>
            <div>Aktiv</div>
            <div>Förvald</div>
            <div>Bokningssekvens</div>
            <div>Auto-avslut</div>
            <div>Marginal</div>
            <div>Spara</div>
            <div>Ta bort</div>
        </div>

        @forelse($tourTypes as $tourType)
            @php($updateFormId = 'tour-type-update-'.$tourType->id)

            <form id="{{ $updateFormId }}" method="POST" action="{{ route('admin.tour-types.update', $tourType) }}" class="d-none">
                @csrf
                @method('PUT')
            </form>

            <div class="tourtype-grid tourtype-grid-row">
                <div>
                    <input type="text" name="name" form="{{ $updateFormId }}" class="form-control" value="{{ $tourType->name }}" required>
                </div>
                <div>
                    <input type="number" name="sort_order" form="{{ $updateFormId }}" class="form-control" value="{{ $tourType->sort_order }}" min="0">
                </div>
                <div>
                    <input
                        type="number"
                        name="default_duration_minutes"
                        form="{{ $updateFormId }}"
                        class="form-control"
                        min="1"
                        max="1440"
                        value="{{ $tourType->default_duration_minutes ?? 80 }}"
                        required
                    >
                </div>
                <div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" form="{{ $updateFormId }}" value="1" id="active_{{ $tourType->id }}" @checked($tourType->is_active)>
                        <label class="form-check-label" for="active_{{ $tourType->id }}">Aktiv</label>
                    </div>
                </div>
                <div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_default" form="{{ $updateFormId }}" value="1" id="default_{{ $tourType->id }}" @checked($tourType->is_default)>
                        <label class="form-check-label" for="default_{{ $tourType->id }}">Förvald</label>
                    </div>
                </div>
                <div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="include_in_booking_sequence" form="{{ $updateFormId }}" value="1" id="booking_sequence_{{ $tourType->id }}" @checked($tourType->include_in_booking_sequence)>
                        <label class="form-check-label" for="booking_sequence_{{ $tourType->id }}">Ja</label>
                    </div>
                </div>
                <div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="auto_complete_enabled" form="{{ $updateFormId }}" value="1" id="auto_complete_{{ $tourType->id }}" @checked($tourType->auto_complete_enabled ?? true)>
                        <label class="form-check-label" for="auto_complete_{{ $tourType->id }}">Ja</label>
                    </div>
                </div>
                <div>
                    <input
                        type="number"
                        name="auto_complete_grace_minutes"
                        form="{{ $updateFormId }}"
                        class="form-control"
                        min="0"
                        max="120"
                        value="{{ $tourType->auto_complete_grace_minutes ?? 15 }}"
                        required
                    >
                </div>
                <div>
                    <button type="submit" form="{{ $updateFormId }}" class="btn btn-sm btn-outline-secondary w-100">Spara</button>
                </div>
                <div>
                    <form method="POST" action="{{ route('admin.tour-types.destroy', $tourType) }}" onsubmit="return confirm('Ta bort turtypen?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">Ta bort</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="text-center muted py-4">Inga turtyper hittades.</div>
        @endforelse
    </div>
</div>

<style>
.tourtype-table-wrap {
    overflow-x: auto;
}

.tourtype-grid {
    display: grid;
    grid-template-columns: minmax(170px, 1.5fr) 90px 120px 80px 80px 120px 95px 90px 95px 95px;
    gap: 0.75rem;
    align-items: center;
    min-width: 1120px;
}

.tourtype-grid-head {
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--text-soft);
    font-weight: 800;
    padding-bottom: 0.65rem;
    border-bottom: 1px solid var(--brand-line-soft);
    margin-bottom: 0.75rem;
}

.tourtype-grid-row {
    margin-bottom: 0.75rem;
}

.tourtype-grid .form-check {
    margin-bottom: 0;
    min-height: 1.5rem;
}

@media (max-width: 1200px) {
    .tourtype-grid {
        min-width: 0;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .tourtype-grid-head {
        display: none;
    }

    .tourtype-grid-row {
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--brand-line-soft);
    }
}
</style>
@endsection
