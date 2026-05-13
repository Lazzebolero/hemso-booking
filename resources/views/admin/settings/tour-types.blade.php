@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="mb-1">Turtyper</h2>
        <div class="muted">Administrera valbara turtyper och vilket alternativ som ska vara förvalt i systemet.</div>
    </div>
</div>

<div class="page-card">
    <div class="section-title">Ny turtyp</div>

    <form method="POST" action="{{ route('admin.tour-types.store') }}" class="row g-3 mb-4">
        @csrf
        <div class="col-md-5">
            <label class="form-label">Namn</label>
            <input type="text" name="name" class="form-control" placeholder="Ex. Guidad visning" required>
        </div>

        <div class="col-md-2">
            <label class="form-label">Sortering</label>
            <input type="number" name="sort_order" class="form-control" value="0" min="0">
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="new_tour_type_active" checked>
                <label class="form-check-label" for="new_tour_type_active">Aktiv</label>
            </div>
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="new_tour_type_default">
                <label class="form-check-label" for="new_tour_type_default">Förvald</label>
            </div>
        </div>

        <div class="col-md-1 d-flex align-items-end">
            <button class="btn btn-primary w-100">Spara</button>
        </div>
    </form>

    <div class="section-title">Befintliga turtyper</div>

    @forelse($tourTypes as $tourType)
        <form method="POST" action="{{ route('admin.tour-types.update', $tourType) }}" class="row g-3 mb-3">
            @csrf
            @method('PUT')

            <div class="col-md-5">
                <input type="text" name="name" class="form-control" value="{{ $tourType->name }}" required>
            </div>

            <div class="col-md-2">
                <input type="number" name="sort_order" class="form-control" value="{{ $tourType->sort_order }}" min="0">
            </div>

            <div class="col-md-2 d-flex align-items-center">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active_{{ $tourType->id }}" @checked($tourType->is_active)>
                    <label class="form-check-label" for="active_{{ $tourType->id }}">Aktiv</label>
                </div>
            </div>

            <div class="col-md-2 d-flex align-items-center">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_default" value="1" id="default_{{ $tourType->id }}" @checked($tourType->is_default)>
                    <label class="form-check-label" for="default_{{ $tourType->id }}">Förvald</label>
                </div>
            </div>

            <div class="col-md-1 d-flex align-items-center">
                <button class="btn btn-outline-secondary btn-sm w-100">Spara</button>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.tour-types.destroy', $tourType) }}" onsubmit="return confirm('Ta bort turtypen?');" class="mb-4">
            @csrf
            @method('DELETE')
            <button class="btn btn-sm btn-outline-danger">Ta bort</button>
        </form>
    @empty
        <div class="muted">Inga turtyper hittades.</div>
    @endforelse
</div>
@endsection
