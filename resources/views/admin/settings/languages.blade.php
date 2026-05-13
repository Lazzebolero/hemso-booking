@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Språk</h2>
        <div class="page-subtitle">Administrera vilka språk som kan väljas på bokningar.</div>
    </div>
</div>

<div class="page-card compact-card mb-3">
    <div class="section-title">Nytt språk</div>

    <form method="POST" action="{{ route('admin.languages.store') }}" class="language-form-grid">
        @csrf

        <div>
            <label class="form-label">Språk</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div>
            <label class="form-label">Kod</label>
            <input type="text" name="code" class="form-control" placeholder="sv" required>
        </div>

        <div>
            <label class="form-label">Sortering</label>
            <input type="number" name="sort_order" class="form-control" value="0" min="0">
        </div>

        <div class="d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="new_language_active" checked>
                <label class="form-check-label" for="new_language_active">Aktiv</label>
            </div>
        </div>

        <div class="d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="new_language_default">
                <label class="form-check-label" for="new_language_default">Förvald</label>
            </div>
        </div>

        <div class="d-flex align-items-end">
            <button class="btn btn-primary w-100">Spara språk</button>
        </div>
    </form>
</div>

<div class="page-card compact-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="section-title mb-0">Befintliga språk</div>
        <div class="small-muted">{{ count($languages) }} språk</div>
    </div>

    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Språk</th>
                    <th style="width: 100px;">Kod</th>
                    <th style="width: 110px;">Sortering</th>
                    <th style="width: 100px;">Aktiv</th>
                    <th style="width: 110px;">Förvald</th>
                    <th style="width: 170px;">Åtgärder</th>
                </tr>
            </thead>
            <tbody>
                @forelse($languages as $language)
                    <tr>
                        <td colspan="6">
                            <div class="inline-row-form-wrap">
                                <form method="POST" action="{{ route('admin.languages.update', $language) }}" class="inline-row-form">
                                    @csrf
                                    @method('PUT')

                                    <div>
                                        <input type="text" name="name" class="form-control" value="{{ $language->name }}" required>
                                    </div>

                                    <div>
                                        <input type="text" name="code" class="form-control" value="{{ $language->code }}" required>
                                    </div>

                                    <div>
                                        <input type="number" name="sort_order" class="form-control" value="{{ $language->sort_order }}" min="0">
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check justify-content-start">
                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active_{{ $language->id }}" @checked($language->is_active)>
                                            <label class="form-check-label" for="active_{{ $language->id }}">Aktiv</label>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check justify-content-start">
                                            <input class="form-check-input" type="checkbox" name="is_default" value="1" id="default_{{ $language->id }}" @checked($language->is_default)>
                                            <label class="form-check-label" for="default_{{ $language->id }}">Förvald</label>
                                        </div>
                                    </div>

                                    <div>
                                        <button class="btn btn-sm btn-outline-secondary w-100">Spara</button>
                                    </div>
                                </form>

                                <form method="POST" action="{{ route('admin.languages.destroy', $language) }}" onsubmit="return confirm('Ta bort språket?');" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Ta bort</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center muted py-4">Inga språk hittades.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<style>
.language-form-grid {
    display: grid;
    grid-template-columns: minmax(220px, 1.4fr) 120px 120px 110px 120px 140px;
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
    grid-template-columns: minmax(220px, 1.4fr) 100px 110px 100px 110px 120px;
    gap: 0.9rem;
    align-items: center;
}
@media (max-width: 1200px) {
    .language-form-grid,
    .inline-row-form {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .inline-row-form-wrap {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 700px) {
    .language-form-grid,
    .inline-row-form {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection