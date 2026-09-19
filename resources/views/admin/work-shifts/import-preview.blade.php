@extends('layouts.app')

@section('content')
@php
    $formatTime = function (array $row, string $startKey, string $endKey): string {
        $start = $row[$startKey] ?? '';
        $end = $row[$endKey] ?? '';

        return $end ? $start.' – '.$end : $start;
    };
    $roleName = fn (?string $slug) => \App\Support\Roles::labels()[$slug] ?? ($slug ?: '–');
    $functionName = fn (?string $slug) => $slug ? \App\Models\RestaurantFunction::label($slug) : '–';
    $canImport = count($ready) > 0 || count($changes) > 0 || count($removals) > 0;
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Förhandsgranska import</h2>
        <div class="page-subtitle">
            {{ count($ready) }} nya pass
            @if(count($changes) > 0)
                · {{ count($changes) }} redan bokade
            @endif
            @if(count($removals) > 0)
                · {{ count($removals) }} att ta bort
            @endif
            @if($skipped > 0)
                · {{ $skipped }} oförändrade
            @endif
            @if(count($rowErrors) > 0)
                · {{ count($rowErrors) }} rader med fel
            @endif
        </div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.work-shifts.import') }}" class="btn btn-outline-secondary">Avbryt</a>
    </div>
</div>

@if(count($rowErrors) > 0)
    <div class="alert alert-danger mb-4">
        <div class="fw-semibold mb-1">Dessa rader importeras inte.</div>
        <ul class="mb-0">
            @foreach($rowErrors as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(count($changes) > 0)
    <div class="alert alert-warning mb-4">
        {{ count($changes) }} {{ count($changes) === 1 ? 'person är' : 'personer är' }}
        redan bokade den dagen. Två pass samma dag skapas inte. Bocka i dem du vill uppdatera.
    </div>
@endif

@if(count($removals) > 0)
    <div class="alert alert-warning mb-4">
        {{ count($removals) }} {{ count($removals) === 1 ? 'sparat pass saknas' : 'sparade pass saknas' }}
        i filen (tom cell). Bocka i dem du vill ta bort.
    </div>
@endif

<form method="POST" action="{{ route('admin.work-shifts.import.confirm') }}">
    @csrf

    <div class="page-card mb-4">
        <h3 class="h6 mb-3">Nya pass</h3>
        @if(count($ready) === 0)
            <div class="muted py-3">Inga nya arbetspass att skapa.</div>
        @else
            <div class="table-responsive-modern">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Person</th>
                            <th>Roll</th>
                            <th>Funktion</th>
                            <th>Tid</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice($ready, 0, 50) as $row)
                            <tr>
                                <td>{{ $row['shift_date'] }}</td>
                                <td>{{ $row['person_name'] }}</td>
                                <td>{{ $roleName($row['shift_role']) }}</td>
                                <td>{{ $functionName($row['shift_function'] ?? null) }}</td>
                                <td>{{ $formatTime($row, 'start_time', 'end_time') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if(count($ready) > 50)
                <div class="small-muted mt-2">Visar de 50 första av {{ count($ready) }} pass.</div>
            @endif
        @endif
    </div>

    @if(count($changes) > 0)
        <div class="page-card mb-4">
            <h3 class="h6 mb-3">Redan bokade – vill du ändra?</h3>
            <div class="table-responsive-modern">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Datum</th>
                            <th>Person</th>
                            <th>Nuvarande</th>
                            <th>Nytt i filen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice($changes, 0, 50) as $row)
                            <tr>
                                <td>
                                    <div class="form-check mb-0">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="update[]"
                                            id="update-{{ $row['work_shift_id'] }}"
                                            value="{{ $row['work_shift_id'] }}"
                                            checked
                                        >
                                    </div>
                                </td>
                                <td>{{ $row['shift_date'] }}</td>
                                <td>
                                    <label class="form-check-label" for="update-{{ $row['work_shift_id'] }}">
                                        {{ $row['person_name'] }}
                                    </label>
                                </td>
                                <td>
                                    {{ $roleName($row['current_shift_role'] ?? null) }}
                                    @if(! empty($row['current_shift_function']))
                                        · {{ $functionName($row['current_shift_function']) }}
                                    @endif
                                    · {{ $formatTime($row, 'current_start_time', 'current_end_time') }}
                                </td>
                                <td>
                                    {{ $roleName($row['shift_role']) }}
                                    @if(! empty($row['shift_function']))
                                        · {{ $functionName($row['shift_function']) }}
                                    @endif
                                    · {{ $formatTime($row, 'start_time', 'end_time') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if(count($changes) > 50)
                <div class="small-muted mt-2">Visar de 50 första av {{ count($changes) }} ändringar.</div>
            @endif
        </div>
    @endif

    @if(count($removals) > 0)
        <div class="page-card mb-4">
            <h3 class="h6 mb-3">Sparade pass saknas i filen – vill du ta bort?</h3>
            <div class="table-responsive-modern">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Datum</th>
                            <th>Person</th>
                            <th>Nuvarande pass</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice($removals, 0, 50) as $row)
                            <tr>
                                <td>
                                    <div class="form-check mb-0">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="remove[]"
                                            id="remove-{{ $row['work_shift_id'] }}"
                                            value="{{ $row['work_shift_id'] }}"
                                            checked
                                        >
                                    </div>
                                </td>
                                <td>{{ $row['shift_date'] }}</td>
                                <td>
                                    <label class="form-check-label" for="remove-{{ $row['work_shift_id'] }}">
                                        {{ $row['person_name'] }}
                                    </label>
                                </td>
                                <td>
                                    {{ $roleName($row['current_shift_role'] ?? null) }}
                                    @if(! empty($row['current_shift_function']))
                                        · {{ $functionName($row['current_shift_function']) }}
                                    @endif
                                    · {{ $formatTime($row, 'current_start_time', 'current_end_time') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if(count($removals) > 50)
                <div class="small-muted mt-2">Visar de 50 första av {{ count($removals) }} borttagningar.</div>
            @endif
        </div>
    @endif

    @if($canImport)
        <button class="btn btn-primary" type="submit">
            @if(count($ready) > 0 && count($changes) > 0 && count($removals) > 0)
                Importera {{ count($ready) }} nya, uppdatera valda och ta bort valda pass
            @elseif(count($ready) > 0 && count($changes) > 0)
                Importera {{ count($ready) }} nya och uppdatera valda pass
            @elseif(count($ready) > 0 && count($removals) > 0)
                Importera {{ count($ready) }} nya och ta bort valda pass
            @elseif(count($changes) > 0 && count($removals) > 0)
                Uppdatera valda och ta bort valda pass
            @elseif(count($changes) > 0)
                Uppdatera valda pass
            @elseif(count($removals) > 0)
                Ta bort valda pass
            @else
                Importera {{ count($ready) }} arbetspass
            @endif
        </button>
    @endif
</form>
@endsection
