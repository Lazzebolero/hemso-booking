@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Turtyper</h2>
        <div class="page-subtitle">Administrera turtyper och standardlängd för turer.</div>
    </div>
</div>

<div class="page-card compact-card mb-3">
    <div class="section-title">Ny turtyp</div>

    <form method="POST" action="{{ route('admin.tour-types.store') }}" class="tourtype-form-grid">
        @csrf

        <div>
            <label class="form-label">Namn</label>
            <input type="text" name="name" class="form-control" placeholder="Ex. Guidad visning" required>
        </div>

        <div>
            <label class="form-label">Sortering</label>
            <input type="number" name="sort_order" class="form-control" value="0" min="0">
        </div>

        <div>
            <label class="form-label">Standardlängd (min)</label>
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

        <div class="d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="new_tour_type_active" checked>
                <label class="form-check-label" for="new_tour_type_active">Aktiv</label>
            </div>
        </div>

        <div class="d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="new_tour_type_default">
                <label class="form-check-label" for="new_tour_type_default">Förvald</label>
            </div>
        </div>

        <div class="d-flex align-items-end">
            <button class="btn btn-primary w-100">Spara</button>
        </div>
    </form>
</div>

<div class="page-card compact-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="section-title mb-0">Befintliga turtyper</div>
        <div class="small-muted">{{ count($tourTypes) }} turtyper</div>
    </div>

    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Namn</th>
                    <th style="width: 110px;">Sortering</th>
                    <th style="width: 155px;">Standardlängd</th>
                    <th style="width: 100px;">Aktiv</th>
                    <th style="width: 110px;">Förvald</th>
                    <th style="width: 170px;">Åtgärder</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tourTypes as $tourType)
                    <tr>
                        <td colspan="6">
                            <div class="inline-row-form-wrap">
                                <form method="POST" action="{{ route('admin.tour-types.update', $tourType) }}" class="inline-row-form">
                                    @csrf
                                    @method('PUT')

                                    <div>
                                        <input type="text" name="name" class="form-control" value="{{ $tourType->name }}" required>
                                    </div>

                                    <div>
                                        <input type="number" name="sort_order" class="form-control" value="{{ $tourType->sort_order }}" min="0">
                                    </div>

                                    <div>
                                        <input
                                            type="number"
                                            name="default_duration_minutes"
                                            class="form-control"
                                            min="1"
                                            max="1440"
                                            value="{{ $tourType->default_duration_minutes ?? 80 }}"
                                            required
                                        >
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check justify-content-start">
                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active_{{ $tourType->id }}" @checked($tourType->is_active)>
                                            <label class="form-check-label" for="active_{{ $tourType->id }}">Aktiv</label>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check justify-content-start">
                                            <input class="form-check-input" type="checkbox" name="is_default" value="1" id="default_{{ $tourType->id }}" @checked($tourType->is_default)>
                                            <label class="form-check-label" for="default_{{ $tourType->id }}">Förvald</label>
                                        </div>
                                    </div>

                                    <div>
                                        <button class="btn btn-sm btn-outline-secondary w-100">Spara</button>
                                    </div>
                                </form>

                                <form method="POST" action="{{ route('admin.tour-types.destroy', $tourType) }}" onsubmit="return confirm('Ta bort turtypen?');" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Ta bort</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center muted py-4">Inga turtyper hittades.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<style>
.tourtype-form-grid {
    display: grid;
    grid-template-columns: minmax(260px, 1.4fr) 120px 170px 110px 120px 120px;
    gap: 0.9rem;
    align-items: end;
}
.inline-row-form-wrap {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 0.75rem;
    align-items: center;
}
.inline-row-form {
    display: grid;
    grid-template-columns: minmax(260px, 1.4fr) 110px 155px 100px 110px 120px;
    gap: 0.9rem;
    align-items: center;
}
@media (max-width: 1200px) {
    .tourtype-form-grid,
    .inline-row-form {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .inline-row-form-wrap {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 700px) {
    .tourtype-form-grid,
    .inline-row-form {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection