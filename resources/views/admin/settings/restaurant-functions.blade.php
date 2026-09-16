@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Restaurangfunktioner</h2>
        <div class="page-subtitle">
            Hantera alternativen i dropplistan för restaurangpass i arbetsschemat.
            Nyckeln (t.ex. <code>kok</code>) sparas i passen — namnet (t.ex. Kök) visas i listor.
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="page-card compact-card mb-3">
    <div class="section-title">Ny funktion</div>

    <form method="POST" action="{{ route('admin.restaurant-functions.store') }}" class="restaurant-function-form-grid">
        @csrf

        <div>
            <label class="form-label">Namn</label>
            <input type="text" name="name" class="form-control" placeholder="Kök" required>
        </div>

        <div>
            <label class="form-label">Nyckel</label>
            <input type="text" name="slug" class="form-control" placeholder="kok" pattern="[a-z0-9_]+" required>
            <div class="form-text">Små bokstäver, siffror och understreck.</div>
        </div>

        <div>
            <label class="form-label">Sortering</label>
            <input type="number" name="sort_order" class="form-control" value="0" min="0">
        </div>

        <div class="d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="new_restaurant_function_active" checked>
                <label class="form-check-label" for="new_restaurant_function_active">Aktiv</label>
            </div>
        </div>

        <div class="d-flex align-items-end">
            <button class="btn btn-primary w-100">Spara funktion</button>
        </div>
    </form>
</div>

<div class="page-card compact-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="section-title mb-0">Befintliga funktioner</div>
        <div class="small-muted">{{ count($functions) }} funktioner</div>
    </div>

    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Namn</th>
                    <th style="width: 140px;">Nyckel</th>
                    <th style="width: 110px;">Sortering</th>
                    <th style="width: 100px;">Aktiv</th>
                    <th style="width: 170px;">Åtgärder</th>
                </tr>
            </thead>
            <tbody>
                @forelse($functions as $function)
                    <tr>
                        <td colspan="5">
                            <div class="inline-row-form-wrap">
                                <form method="POST" action="{{ route('admin.restaurant-functions.update', $function) }}" class="inline-row-form">
                                    @csrf
                                    @method('PUT')

                                    <div>
                                        <input type="text" name="name" class="form-control" value="{{ $function->name }}" required>
                                    </div>

                                    <div>
                                        <input type="text" name="slug" class="form-control" value="{{ $function->slug }}" pattern="[a-z0-9_]+" required>
                                    </div>

                                    <div>
                                        <input type="number" name="sort_order" class="form-control" value="{{ $function->sort_order }}" min="0">
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check justify-content-start">
                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active_{{ $function->id }}" @checked($function->is_active)>
                                            <label class="form-check-label" for="active_{{ $function->id }}">Aktiv</label>
                                        </div>
                                    </div>

                                    <div>
                                        <button class="btn btn-sm btn-outline-secondary w-100">Spara</button>
                                    </div>
                                </form>

                                <form method="POST" action="{{ route('admin.restaurant-functions.destroy', $function) }}" onsubmit="return confirm('Ta bort funktionen?');" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Ta bort</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center muted py-4">Inga restaurangfunktioner hittades.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<style>
.restaurant-function-form-grid {
    display: grid;
    grid-template-columns: minmax(220px, 1.4fr) 160px 120px 110px 140px;
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
    grid-template-columns: minmax(220px, 1.4fr) 140px 110px 100px 120px;
    gap: 0.9rem;
    align-items: center;
}
@media (max-width: 1200px) {
    .restaurant-function-form-grid,
    .inline-row-form {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .inline-row-form-wrap {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 700px) {
    .restaurant-function-form-grid,
    .inline-row-form {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection
