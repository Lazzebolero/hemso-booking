@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Felrapport-val</h2>
        <div class="page-subtitle">Administrera kategorier och prioriteter från databasen.</div>
    </div>
</div>

<div class="admin-grid-2">
    <div class="page-card compact-card">
        <div class="section-title">Kategorier</div>

        <form method="POST" action="{{ route('admin.report-options.store') }}" class="report-option-grid mb-3">
            @csrf
            <input type="hidden" name="type" value="category">

            <div>
                <label class="form-label">Namn</label>
                <input type="text" name="name" class="form-control" placeholder="Ny kategori" required>
            </div>

            <div>
                <label class="form-label">Sortering</label>
                <input type="number" name="sort_order" class="form-control" value="0" min="0">
            </div>

            <div class="d-flex align-items-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="new-category-active">
                    <label class="form-check-label" for="new-category-active">Aktiv</label>
                </div>
            </div>

            <div class="d-flex align-items-end">
                <button class="btn btn-primary w-100">Lägg till</button>
            </div>
        </form>

        <div class="table-responsive-modern">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Namn</th>
                        <th style="width: 110px;">Sortering</th>
                        <th style="width: 100px;">Aktiv</th>
                        <th style="width: 170px;">Åtgärder</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $option)
                        <tr>
                            <td colspan="4">
                                <div class="inline-row-form-wrap">
                                    <form method="POST" action="{{ route('admin.report-options.update', $option) }}" class="inline-row-form">
                                        @csrf
                                        @method('PUT')

                                        <div>
                                            <input type="text" name="name" class="form-control" value="{{ $option->name }}" required>
                                        </div>

                                        <div>
                                            <input type="number" name="sort_order" class="form-control" value="{{ $option->sort_order }}" min="0">
                                        </div>

                                        <div class="d-flex align-items-center">
                                            <div class="form-check justify-content-start">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="cat-{{ $option->id }}" @checked($option->is_active)>
                                                <label class="form-check-label" for="cat-{{ $option->id }}">Aktiv</label>
                                            </div>
                                        </div>

                                        <div>
                                            <button class="btn btn-sm btn-outline-secondary w-100">Spara</button>
                                        </div>
                                    </form>

                                    <form method="POST" action="{{ route('admin.report-options.destroy', $option) }}" onsubmit="return confirm('Ta bort alternativet?');" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Ta bort</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center muted py-4">Inga kategorier hittades.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="page-card compact-card">
        <div class="section-title">Prioriteter</div>

        <form method="POST" action="{{ route('admin.report-options.store') }}" class="report-option-grid mb-3">
            @csrf
            <input type="hidden" name="type" value="priority">

            <div>
                <label class="form-label">Namn</label>
                <input type="text" name="name" class="form-control" placeholder="Ny prioritet" required>
            </div>

            <div>
                <label class="form-label">Sortering</label>
                <input type="number" name="sort_order" class="form-control" value="0" min="0">
            </div>

            <div class="d-flex align-items-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="new-priority-active">
                    <label class="form-check-label" for="new-priority-active">Aktiv</label>
                </div>
            </div>

            <div class="d-flex align-items-end">
                <button class="btn btn-primary w-100">Lägg till</button>
            </div>
        </form>

        <div class="table-responsive-modern">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Namn</th>
                        <th style="width: 110px;">Sortering</th>
                        <th style="width: 100px;">Aktiv</th>
                        <th style="width: 170px;">Åtgärder</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($priorities as $option)
                        <tr>
                            <td colspan="4">
                                <div class="inline-row-form-wrap">
                                    <form method="POST" action="{{ route('admin.report-options.update', $option) }}" class="inline-row-form">
                                        @csrf
                                        @method('PUT')

                                        <div>
                                            <input type="text" name="name" class="form-control" value="{{ $option->name }}" required>
                                        </div>

                                        <div>
                                            <input type="number" name="sort_order" class="form-control" value="{{ $option->sort_order }}" min="0">
                                        </div>

                                        <div class="d-flex align-items-center">
                                            <div class="form-check justify-content-start">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="prio-{{ $option->id }}" @checked($option->is_active)>
                                                <label class="form-check-label" for="prio-{{ $option->id }}">Aktiv</label>
                                            </div>
                                        </div>

                                        <div>
                                            <button class="btn btn-sm btn-outline-secondary w-100">Spara</button>
                                        </div>
                                    </form>

                                    <form method="POST" action="{{ route('admin.report-options.destroy', $option) }}" onsubmit="return confirm('Ta bort alternativet?');" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Ta bort</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center muted py-4">Inga prioriteter hittades.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.report-option-grid {
    display: grid;
    grid-template-columns: minmax(220px, 1.4fr) 120px 110px 120px;
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
    grid-template-columns: minmax(220px, 1.4fr) 110px 100px 120px;
    gap: 0.9rem;
    align-items: center;
}

@media (max-width: 1100px) {
    .inline-row-form-wrap {
        grid-template-columns: 1fr;
    }

    .inline-row-form,
    .report-option-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 700px) {
    .inline-row-form,
    .report-option-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection