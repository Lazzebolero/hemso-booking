@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Restaurangfunktioner</h2>
        <div class="page-subtitle">
            Hantera dropplistan för restaurangpass och standardtider för importmallen.
            Nyckeln (t.ex. <code>kok</code>) sparas i passen — namnet (t.ex. Kök) visas i listor.
            Tom tid i Excel använder standardtiden här: på kök per station, annars per roll.
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
    <div class="section-title">Standardtider för roller</div>
    <p class="small-muted mb-3">
        Används i arbetsschemamallen för guide, värd, admin och trainee när tiden inte är ifylld.
    </p>

    <form method="POST" action="{{ route('admin.restaurant-functions.role-defaults') }}" class="role-default-form">
        @csrf
        @method('PUT')

        <div class="table-responsive-modern">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Roll</th>
                        <th style="width: 160px;">Start</th>
                        <th style="width: 160px;">Slut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($scheduleRoles as $slug => $role)
                        <tr>
                            <td>{{ $role['label'] }}</td>
                            <td>
                                <input
                                    type="time"
                                    name="roles[{{ $slug }}][default_start_time]"
                                    class="form-control"
                                    value="{{ $role['default_start_time'] }}"
                                    required
                                >
                            </td>
                            <td>
                                <input
                                    type="time"
                                    name="roles[{{ $slug }}][default_end_time]"
                                    class="form-control"
                                    value="{{ $role['default_end_time'] }}"
                                >
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            <button class="btn btn-primary">Spara rolltider</button>
        </div>
    </form>
</div>

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
            <label class="form-label">Start</label>
            <input type="time" name="default_start_time" class="form-control" value="10:00">
        </div>

        <div>
            <label class="form-label">Slut</label>
            <input type="time" name="default_end_time" class="form-control" value="16:00">
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
                    <th style="width: 130px;">Start</th>
                    <th style="width: 130px;">Slut</th>
                    <th style="width: 110px;">Sortering</th>
                    <th style="width: 100px;">Aktiv</th>
                    <th style="width: 170px;">Åtgärder</th>
                </tr>
            </thead>
            <tbody>
                @forelse($functions as $function)
                    @php
                        $start = \App\Support\ShiftDefaultTimes::normalize($function->default_start_time) ?? '10:00';
                        $end = \App\Support\ShiftDefaultTimes::normalize($function->default_end_time);
                    @endphp
                    <tr>
                        <td colspan="7">
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
                                        <input type="time" name="default_start_time" class="form-control" value="{{ $start }}">
                                    </div>

                                    <div>
                                        <input type="time" name="default_end_time" class="form-control" value="{{ $end }}">
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
                        <td colspan="7" class="text-center muted py-4">Inga restaurangfunktioner hittades.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<style>
.restaurant-function-form-grid {
    display: grid;
    grid-template-columns: minmax(180px, 1.2fr) 140px 130px 130px 110px 110px 140px;
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
    grid-template-columns: minmax(160px, 1.2fr) 120px 120px 120px 100px 90px 110px;
    gap: 0.75rem;
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
