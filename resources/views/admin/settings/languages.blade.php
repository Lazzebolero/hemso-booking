@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="mb-1">Språk</h2>
        <div class="muted">Administrera vilka språk som kan väljas på bokningar.</div>
    </div>
</div>

<div class="page-card">
    <div class="section-title">Nytt språk</div>

    <form method="POST" action="{{ route('admin.languages.store') }}" class="row g-3 mb-4">
        @csrf

        <div class="col-md-4">
            <label class="form-label">Språk</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="col-md-2">
            <label class="form-label">Kod</label>
            <input type="text" name="code" class="form-control" placeholder="sv" required>
        </div>

        <div class="col-md-2">
            <label class="form-label">Sortering</label>
            <input type="number" name="sort_order" class="form-control" value="0" min="0">
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="new_language_active" checked>
                <label class="form-check-label" for="new_language_active">Aktiv</label>
            </div>
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="new_language_default">
                <label class="form-check-label" for="new_language_default">Förvald</label>
            </div>
        </div>

        <div class="col-12">
            <button class="btn btn-primary">Spara språk</button>
        </div>
    </form>

    <div class="section-title">Befintliga språk</div>

    @forelse($languages as $language)
        <form method="POST" action="{{ route('admin.languages.update', $language) }}" class="row g-3 mb-3">
            @csrf
            @method('PUT')

            <div class="col-md-4">
                <input type="text" name="name" class="form-control" value="{{ $language->name }}" required>
            </div>

            <div class="col-md-2">
                <input type="text" name="code" class="form-control" value="{{ $language->code }}" required>
            </div>

            <div class="col-md-2">
                <input type="number" name="sort_order" class="form-control" value="{{ $language->sort_order }}" min="0">
            </div>

            <div class="col-md-2 d-flex align-items-center">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active_{{ $language->id }}" @checked($language->is_active)>
                    <label class="form-check-label" for="active_{{ $language->id }}">Aktiv</label>
                </div>
            </div>

            <div class="col-md-2 d-flex align-items-center">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_default" value="1" id="default_{{ $language->id }}" @checked($language->is_default)>
                    <label class="form-check-label" for="default_{{ $language->id }}">Förvald</label>
                </div>
            </div>

            <div class="col-12 d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm">Spara</button>
        </form>

                <form method="POST" action="{{ route('admin.languages.destroy', $language) }}" onsubmit="return confirm('Ta bort språket?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm">Ta bort</button>
                </form>
            </div>
    @empty
        <div class="muted">Inga språk hittades.</div>
    @endforelse
</div>
@endsection