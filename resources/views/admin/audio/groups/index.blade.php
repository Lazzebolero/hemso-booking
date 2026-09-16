@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Ljudgrupper</h2>
        <div class="page-subtitle">Dela in anläggningen i zoner som kan styras tillsammans.</div>
    </div>

    <div class="page-actions">
        <a href="{{ route('admin.audio.index') }}" class="btn btn-outline-secondary">Till ljud</a>
    </div>
</div>

<div class="page-card compact-card">
    <div class="section-title">Skapa grupp</div>

    <form method="POST" action="{{ route('admin.audio.groups.store') }}" class="row g-3 align-items-end">
        @csrf
        <div class="col-md-4">
            <label class="form-label" for="name">Namn</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" placeholder="Norra delen" required>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="description">Beskrivning</label>
            <input type="text" name="description" id="description" class="form-control" value="{{ old('description') }}" placeholder="Entré och foajé">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="sort_order">Sortering</label>
            <input type="number" name="sort_order" id="sort_order" class="form-control" min="0" value="{{ old('sort_order', 0) }}">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Skapa</button>
        </div>
    </form>
</div>

<div class="page-card compact-card mt-4">
    <div class="section-title">Befintliga grupper</div>

    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Namn</th>
                    <th>Beskrivning</th>
                    <th>Enheter</th>
                    <th>Sortering</th>
                    <th>Aktiv</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($groups as $group)
                    <tr>
                        <td>
                            <form method="POST" action="{{ route('admin.audio.groups.update', $group) }}" id="group-form-{{ $group->id }}">
                                @csrf
                                @method('PUT')
                                <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $group->name) }}" required>
                        </td>
                        <td>
                                <input type="text" name="description" class="form-control form-control-sm" value="{{ old('description', $group->description) }}">
                        </td>
                        <td>{{ $group->devices_count }}</td>
                        <td>
                                <input type="number" name="sort_order" class="form-control form-control-sm" min="0" value="{{ old('sort_order', $group->sort_order) }}">
                        </td>
                        <td>
                                <input type="checkbox" name="is_active" class="form-check-input" value="1" @checked(old('is_active', $group->is_active))>
                        </td>
                        <td class="text-nowrap">
                                <button type="submit" class="btn btn-sm btn-outline-secondary" form="group-form-{{ $group->id }}">Spara</button>
                            </form>
                            <a href="{{ route('admin.audio.groups.show', $group) }}" class="btn btn-sm btn-primary">Styr</a>
                            <form method="POST" action="{{ route('admin.audio.groups.destroy', $group) }}" class="d-inline" onsubmit="return confirm('Ta bort gruppen? Enheterna behålls men blir grupplösa.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Ta bort</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Inga grupper ännu. Skapa t.ex. "Entré", "Södra delen", "Källare".</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
