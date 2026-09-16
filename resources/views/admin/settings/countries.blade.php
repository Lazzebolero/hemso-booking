@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Länder</h2>
        <div class="page-subtitle">Styr snabbval i bokningssekvensen och hantera föreslagna länder.</div>
    </div>
</div>

@include('partials.ui.flash-messages')

<div class="page-card compact-card mb-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <div class="section-title mb-0">Snabbval i bokningssekvensen</div>
        <form method="POST" action="{{ route('admin.countries.quick-pick-limit') }}" class="country-limit-form">
            @csrf
            @method('PUT')
            <label class="form-label mb-0 me-2" for="country_quick_pick_max">Max antal</label>
            <input
                type="number"
                id="country_quick_pick_max"
                name="country_quick_pick_max"
                class="form-control form-control-sm"
                min="1"
                max="{{ \App\Support\CountryCatalog::ABSOLUTE_MAX_QUICK_PICKS }}"
                value="{{ old('country_quick_pick_max', $maxQuickPicks) }}"
            >
            <button class="btn btn-sm btn-outline-secondary">Spara</button>
        </form>
    </div>

    <div class="small-muted mb-2">
        Visar högst {{ $maxQuickPicks }} länder som snabbknappar. Fler kan markeras som snabbval men visas bara upp till gränsen.
    </div>

    @if($quickPickCountries->isEmpty())
        <div class="small-muted">Inga snabbval valda ännu.</div>
    @else
        <div class="country-quick-preview">
            @foreach($quickPickCountries as $country)
                <span class="country-quick-preview-item">
                    <img src="{{ $country->flagUrl() }}" alt="" class="country-quick-preview-flag" loading="lazy">
                    {{ $country->name }}
                </span>
            @endforeach
        </div>
    @endif
</div>

<div class="page-card compact-card mb-3">
    <form method="GET" action="{{ route('admin.countries.index') }}" class="country-admin-toolbar">
        <div>
            <label class="form-label">Sök</label>
            <input type="text" name="q" class="form-control" value="{{ $search }}" placeholder="Land eller kod">
        </div>

        <div>
            <label class="form-label">Filter</label>
            <select name="filter" class="form-select">
                <option value="all" @selected($filter === 'all')>Alla</option>
                <option value="active" @selected($filter === 'active')>Aktiva</option>
                <option value="quick" @selected($filter === 'quick')>Snabbval</option>
                <option value="proposed" @selected($filter === 'proposed')>Föreslagna</option>
                <option value="inactive" @selected($filter === 'inactive')>Inaktiva</option>
            </select>
        </div>

        <div class="d-flex align-items-end gap-2">
            <button class="btn btn-outline-secondary">Filtrera</button>
            <a href="{{ route('admin.countries.index') }}" class="btn btn-link">Rensa</a>
        </div>
    </form>
</div>

<div class="page-card compact-card mb-3">
    <div class="section-title mb-3">Nytt land</div>

    <form method="POST" action="{{ route('admin.countries.store') }}" class="country-admin-toolbar">
        @csrf

        <div>
            <label class="form-label">Land</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div>
            <label class="form-label">Kod</label>
            <input type="text" name="code" class="form-control" placeholder="se" required>
        </div>

        <div>
            <label class="form-label">Sortering</label>
            <input type="number" name="sort_order" class="form-control" value="0" min="0">
        </div>

        <div class="d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="new_country_active" checked>
                <label class="form-check-label" for="new_country_active">Aktiv</label>
            </div>
        </div>

        <div class="d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_quick_pick" value="1" id="new_country_quick_pick">
                <label class="form-check-label" for="new_country_quick_pick">Snabbval</label>
            </div>
        </div>

        <div class="d-flex align-items-end">
            <button class="btn btn-primary w-100">Spara</button>
        </div>
    </form>
</div>

<div class="page-card compact-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="section-title mb-0">Alla länder</div>
        <div class="small-muted">{{ $countries->count() }} träffar</div>
    </div>

    <div class="table-responsive-modern">
        <table class="table-modern country-admin-table">
            <thead>
                <tr>
                    <th>Land</th>
                    <th>Kod</th>
                    <th>Sort</th>
                    <th>Aktiv</th>
                    <th>Snabb</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($countries as $country)
                    @php
                        $isEditingThisCountry = (string) old('editing_country_id') === (string) $country->id;
                    @endphp
                    <tr @class(['country-row-proposed' => $country->is_proposed])>
                        <td colspan="7">
                            <form method="POST" action="{{ route('admin.countries.update', $country) }}" class="country-admin-row">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="editing_country_id" value="{{ $country->id }}">

                                <div class="country-admin-name">
                                    <img src="{{ $country->flagUrl() }}" alt="" class="country-admin-flag" loading="lazy">
                                    <input type="text" name="name" class="form-control form-control-sm" value="{{ $isEditingThisCountry ? old('name', $country->name) : $country->name }}" required>
                                </div>

                                <div>
                                    <input type="text" name="code" class="form-control form-control-sm" value="{{ $isEditingThisCountry ? old('code', $country->code) : $country->code }}" required>
                                </div>

                                <div>
                                    <input type="number" name="sort_order" class="form-control form-control-sm" value="{{ $isEditingThisCountry ? old('sort_order', $country->sort_order) : $country->sort_order }}" min="0">
                                </div>

                                <div class="text-center">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($isEditingThisCountry ? old('is_active', $country->is_active) : $country->is_active)>
                                </div>

                                <div class="text-center">
                                    <input class="form-check-input" type="checkbox" name="is_quick_pick" value="1" @checked($isEditingThisCountry ? old('is_quick_pick', $country->is_quick_pick) : $country->is_quick_pick)>
                                </div>

                                <div>
                                    @if($country->is_proposed)
                                        <span class="badge text-bg-warning">Föreslaget</span>
                                    @else
                                        <span class="badge text-bg-light">Godkänt</span>
                                    @endif
                                </div>

                                <div class="country-admin-actions">
                                    @if($country->is_proposed)
                                        <label class="form-check-label small me-2">
                                            <input class="form-check-input" type="checkbox" name="approve" value="1" @checked($isEditingThisCountry && old('approve'))>
                                            Godkänn
                                        </label>
                                    @endif
                                    <button class="btn btn-sm btn-outline-secondary">Spara</button>
                                </div>
                            </form>

                            @if(! $country->is_proposed)
                                <form method="POST" action="{{ route('admin.countries.destroy', $country) }}" onsubmit="return confirm('Ta bort landet?');" class="country-admin-delete">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Ta bort</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center muted py-4">Inga länder hittades.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<style>
.country-quick-preview {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.country-quick-preview-item {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: #f8fafc;
    border: 1px solid var(--brand-line-soft);
    border-radius: 999px;
    padding: 0.35rem 0.7rem;
    font-size: 0.9rem;
}
.country-quick-preview-flag {
    width: 18px;
    height: 13px;
    object-fit: cover;
    border-radius: 2px;
}
.country-limit-form {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.country-limit-form .form-control {
    width: 80px;
}
.country-admin-toolbar {
    display: grid;
    grid-template-columns: minmax(180px, 1.4fr) 120px 120px auto auto auto;
    gap: 0.75rem;
    align-items: end;
}
.country-admin-table tbody td {
    padding-top: 0.45rem;
    padding-bottom: 0.45rem;
}
.country-admin-row {
    display: grid;
    grid-template-columns: minmax(180px, 1.6fr) 90px 70px 50px 50px 90px 80px;
    gap: 0.6rem;
    align-items: center;
}
.country-admin-name {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.country-admin-flag {
    width: 22px;
    height: 16px;
    object-fit: cover;
    border-radius: 2px;
    flex: 0 0 auto;
}
.country-admin-actions {
    display: flex;
    justify-content: flex-end;
}
.country-admin-delete {
    margin-top: 0.35rem;
    text-align: right;
}
.country-row-proposed {
    background: rgba(255, 193, 7, 0.08);
}
@media (max-width: 1100px) {
    .country-admin-toolbar,
    .country-admin-row {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 700px) {
    .country-admin-toolbar,
    .country-admin-row {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection
