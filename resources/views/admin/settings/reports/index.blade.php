@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Inställningar för felrapporter</h2>
        <div class="page-subtitle">Hantera kategorier, prioriteringar, statusar och platser för felrapporter.</div>
    </div>
</div>

<div class="admin-grid-2">
    <div>
        <div class="page-card mb-4">
            <div class="section-title">Kategorier</div>

            <form method="POST" action="{{ route('admin.settings.reports.categories.store') }}" class="mb-4">
                @csrf
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Namn</label>
                        <input type="text" name="name" class="form-control" placeholder="Till exempel Byggnad" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Kod</label>
                        <input type="text" name="code" class="form-control" placeholder="building" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sortering</label>
                        <input type="number" name="sort_order" class="form-control" value="0">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="new-category-active">
                            <label class="form-check-label" for="new-category-active">Aktiv</label>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button class="btn btn-primary">Lägg till kategori</button>
                </div>
            </form>

            <div class="table-responsive-modern">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Namn</th>
                            <th>Kod</th>
                            <th>Sortering</th>
                            <th>Aktiv</th>
                            <th>Åtgärder</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr>
                                <td colspan="5">
                                    <div class="inline-row-form-wrap">
                                        <form method="POST" action="{{ route('admin.settings.reports.categories.update', $category) }}" class="inline-row-form">
                                            @csrf
                                            @method('PUT')

                                            <div>
                                                <input type="text" name="name" class="form-control" value="{{ $category->name }}" required>
                                            </div>

                                            <div>
                                                <input type="text" name="code" class="form-control" value="{{ $category->code }}" required>
                                            </div>

                                            <div>
                                                <input type="number" name="sort_order" class="form-control" value="{{ $category->sort_order }}">
                                            </div>

                                            <div class="d-flex align-items-center">
                                                <div class="form-check justify-content-start">
                                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="cat-{{ $category->id }}" @checked($category->is_active)>
                                                    <label class="form-check-label" for="cat-{{ $category->id }}">Aktiv</label>
                                                </div>
                                            </div>

                                            <div>
                                                <button class="btn btn-sm btn-outline-secondary w-100">Spara</button>
                                            </div>
                                        </form>

                                        <form method="POST" action="{{ route('admin.settings.reports.categories.destroy', $category) }}" onsubmit="return confirm('Ta bort kategorin?');" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Ta bort</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center muted py-4">Inga kategorier hittades.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="page-card mb-4">
            <div class="section-title">Prioriteringar</div>

            <form method="POST" action="{{ route('admin.settings.reports.priorities.store') }}" class="mb-4">
                @csrf
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Namn</label>
                        <input type="text" name="name" class="form-control" placeholder="Till exempel Hög" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Kod</label>
                        <input type="text" name="code" class="form-control" placeholder="high" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sortering</label>
                        <input type="number" name="sort_order" class="form-control" value="0">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="new-priority-active">
                            <label class="form-check-label" for="new-priority-active">Aktiv</label>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button class="btn btn-primary">Lägg till prioritet</button>
                </div>
            </form>

            <div class="table-responsive-modern">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Namn</th>
                            <th>Kod</th>
                            <th>Sortering</th>
                            <th>Aktiv</th>
                            <th>Åtgärder</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($priorities as $priority)
                            <tr>
                                <td colspan="5">
                                    <div class="inline-row-form-wrap">
                                        <form method="POST" action="{{ route('admin.settings.reports.priorities.update', $priority) }}" class="inline-row-form">
                                            @csrf
                                            @method('PUT')

                                            <div>
                                                <input type="text" name="name" class="form-control" value="{{ $priority->name }}" required>
                                            </div>

                                            <div>
                                                <input type="text" name="code" class="form-control" value="{{ $priority->code }}" required>
                                            </div>

                                            <div>
                                                <input type="number" name="sort_order" class="form-control" value="{{ $priority->sort_order }}">
                                            </div>

                                            <div class="d-flex align-items-center">
                                                <div class="form-check justify-content-start">
                                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="prio-{{ $priority->id }}" @checked($priority->is_active)>
                                                    <label class="form-check-label" for="prio-{{ $priority->id }}">Aktiv</label>
                                                </div>
                                            </div>

                                            <div>
                                                <button class="btn btn-sm btn-outline-secondary w-100">Spara</button>
                                            </div>
                                        </form>

                                        <form method="POST" action="{{ route('admin.settings.reports.priorities.destroy', $priority) }}" onsubmit="return confirm('Ta bort prioriteten?');" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Ta bort</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center muted py-4">Inga prioriteter hittades.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="page-card">
            <div class="section-title">Statusar</div>

            <form method="POST" action="{{ route('admin.settings.reports.statuses.store') }}" class="mb-4">
                @csrf
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Namn</label>
                        <input type="text" name="name" class="form-control" placeholder="Till exempel Öppen" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Kod</label>
                        <input type="text" name="code" class="form-control" placeholder="open" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sortering</label>
                        <input type="number" name="sort_order" class="form-control" value="0">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="new-status-active">
                            <label class="form-check-label" for="new-status-active">Aktiv</label>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button class="btn btn-primary">Lägg till status</button>
                </div>
            </form>

            <div class="table-responsive-modern">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Namn</th>
                            <th>Kod</th>
                            <th>Sortering</th>
                            <th>Aktiv</th>
                            <th>Åtgärder</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($statuses as $status)
                            <tr>
                                <td colspan="5">
                                    <div class="inline-row-form-wrap">
                                        <form method="POST" action="{{ route('admin.settings.reports.statuses.update', $status) }}" class="inline-row-form">
                                            @csrf
                                            @method('PUT')

                                            <div>
                                                <input type="text" name="name" class="form-control" value="{{ $status->name }}" required>
                                            </div>

                                            <div>
                                                <input type="text" name="code" class="form-control" value="{{ $status->code }}" required>
                                            </div>

                                            <div>
                                                <input type="number" name="sort_order" class="form-control" value="{{ $status->sort_order }}">
                                            </div>

                                            <div class="d-flex align-items-center">
                                                <div class="form-check justify-content-start">
                                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="status-{{ $status->id }}" @checked($status->is_active)>
                                                    <label class="form-check-label" for="status-{{ $status->id }}">Aktiv</label>
                                                </div>
                                            </div>

                                            <div>
                                                <button class="btn btn-sm btn-outline-secondary w-100">Spara</button>
                                            </div>
                                        </form>

                                        <form method="POST" action="{{ route('admin.settings.reports.statuses.destroy', $status) }}" onsubmit="return confirm('Ta bort statusen?');" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Ta bort</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center muted py-4">Inga statusar hittades.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div>
        <div class="page-card">
            <div class="section-title">Platser</div>

            <form method="POST" action="{{ route('admin.settings.reports.locations.store') }}" class="mb-4">
                @csrf
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Namn</label>
                        <input type="text" name="name" class="form-control" placeholder="Till exempel Matsalen i berget" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kod</label>
                        <input type="text" name="code" class="form-control" placeholder="dining_hall">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Sortering</label>
                        <input type="number" name="sort_order" class="form-control" value="0">
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="new-location-active">
                            <label class="form-check-label" for="new-location-active">Aktiv</label>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button class="btn btn-primary">Lägg till plats</button>
                </div>
            </form>

            <div class="table-responsive-modern">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Namn</th>
                            <th>Kod</th>
                            <th>Sortering</th>
                            <th>Aktiv</th>
                            <th>Åtgärder</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($locations as $location)
                            <tr>
                                <td colspan="5">
                                    <div class="inline-row-form-wrap">
                                        <form method="POST" action="{{ route('admin.settings.reports.locations.update', $location) }}" class="inline-row-form">
                                            @csrf
                                            @method('PUT')

                                            <div>
                                                <input type="text" name="name" class="form-control" value="{{ $location->name }}" required>
                                            </div>

                                            <div>
                                                <input type="text" name="code" class="form-control" value="{{ $location->code }}">
                                            </div>

                                            <div>
                                                <input type="number" name="sort_order" class="form-control" value="{{ $location->sort_order }}">
                                            </div>

                                            <div class="d-flex align-items-center">
                                                <div class="form-check justify-content-start">
                                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="location-{{ $location->id }}" @checked($location->is_active)>
                                                    <label class="form-check-label" for="location-{{ $location->id }}">Aktiv</label>
                                                </div>
                                            </div>

                                            <div>
                                                <button class="btn btn-sm btn-outline-secondary w-100">Spara</button>
                                            </div>
                                        </form>

                                        <form method="POST" action="{{ route('admin.settings.reports.locations.destroy', $location) }}" onsubmit="return confirm('Ta bort platsen?');" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Ta bort</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center muted py-4">Inga platser hittades.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.inline-row-form-wrap {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 0.75rem;
    align-items: center;
}

.inline-row-form {
    display: grid;
    grid-template-columns: minmax(220px, 1.4fr) 140px 120px 100px 120px;
    gap: 0.9rem;
    align-items: center;
}

@media (max-width: 1100px) {
    .inline-row-form-wrap {
        grid-template-columns: 1fr;
    }

    .inline-row-form {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 700px) {
    .inline-row-form {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection