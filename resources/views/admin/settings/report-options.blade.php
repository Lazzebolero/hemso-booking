@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="mb-1">Felrapport-val</h2>
        <div class="muted">Administrera kategorier och prioriteter från databasen.</div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="page-card h-100">
            <div class="section-title">Kategorier</div>

            <form method="POST" action="{{ route('admin.report-options.store') }}" class="row g-2 mb-4">
                @csrf
                <input type="hidden" name="type" value="category">
                <div class="col-5"><input type="text" name="name" class="form-control" placeholder="Ny kategori" required></div>
                <div class="col-3"><input type="number" name="sort_order" class="form-control" value="0" min="0"></div>
                <div class="col-2 d-flex align-items-center">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="new-category-active">
                        <label class="form-check-label" for="new-category-active">Aktiv</label>
                    </div>
                </div>
                <div class="col-2"><button class="btn btn-primary w-100">Lägg till</button></div>
            </form>

            @foreach($categories as $option)
                <form method="POST" action="{{ route('admin.report-options.update', $option) }}" class="row g-2 mb-2">
                    @csrf
                    @method('PUT')
                    <div class="col-5"><input type="text" name="name" class="form-control" value="{{ $option->name }}" required></div>
                    <div class="col-3"><input type="number" name="sort_order" class="form-control" value="{{ $option->sort_order }}" min="0"></div>
                    <div class="col-2 d-flex align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="cat-{{ $option->id }}" @checked($option->is_active)>
                            <label class="form-check-label" for="cat-{{ $option->id }}">Aktiv</label>
                        </div>
                    </div>
                    <div class="col-2"><button class="btn btn-outline-secondary btn-sm w-100">Spara</button></div>
                </form>
                <form method="POST" action="{{ route('admin.report-options.destroy', $option) }}" onsubmit="return confirm('Ta bort alternativet?');" class="mb-3">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger">Ta bort</button>
                </form>
            @endforeach
        </div>
    </div>

    <div class="col-lg-6">
        <div class="page-card h-100">
            <div class="section-title">Prioriteter</div>

            <form method="POST" action="{{ route('admin.report-options.store') }}" class="row g-2 mb-4">
                @csrf
                <input type="hidden" name="type" value="priority">
                <div class="col-5"><input type="text" name="name" class="form-control" placeholder="Ny prioritet" required></div>
                <div class="col-3"><input type="number" name="sort_order" class="form-control" value="0" min="0"></div>
                <div class="col-2 d-flex align-items-center">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="new-priority-active">
                        <label class="form-check-label" for="new-priority-active">Aktiv</label>
                    </div>
                </div>
                <div class="col-2"><button class="btn btn-primary w-100">Lägg till</button></div>
            </form>

            @foreach($priorities as $option)
                <form method="POST" action="{{ route('admin.report-options.update', $option) }}" class="row g-2 mb-2">
                    @csrf
                    @method('PUT')
                    <div class="col-5"><input type="text" name="name" class="form-control" value="{{ $option->name }}" required></div>
                    <div class="col-3"><input type="number" name="sort_order" class="form-control" value="{{ $option->sort_order }}" min="0"></div>
                    <div class="col-2 d-flex align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="prio-{{ $option->id }}" @checked($option->is_active)>
                            <label class="form-check-label" for="prio-{{ $option->id }}">Aktiv</label>
                        </div>
                    </div>
                    <div class="col-2"><button class="btn btn-outline-secondary btn-sm w-100">Spara</button></div>
                </form>
                <form method="POST" action="{{ route('admin.report-options.destroy', $option) }}" onsubmit="return confirm('Ta bort alternativet?');" class="mb-3">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger">Ta bort</button>
                </form>
            @endforeach
        </div>
    </div>
</div>
@endsection
