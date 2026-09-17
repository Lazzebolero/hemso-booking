@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Förhandsgranska import</h2>
        <div class="page-subtitle">
            {{ count($ready) }} pass skapas
            @if($skipped > 0)
                · {{ $skipped }} hoppas över
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

<div class="page-card mb-4">
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
                            <td>{{ \App\Support\Roles::labels()[$row['shift_role']] ?? $row['shift_role'] }}</td>
                            <td>{{ $row['shift_function'] ? \App\Models\RestaurantFunction::label($row['shift_function']) : '–' }}</td>
                            <td>
                                {{ $row['start_time'] }}
                                @if($row['end_time'])
                                    – {{ $row['end_time'] }}
                                @endif
                            </td>
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

@if(count($ready) > 0)
    <form method="POST" action="{{ route('admin.work-shifts.import.confirm') }}">
        @csrf
        <button class="btn btn-primary" type="submit">Importera {{ count($ready) }} arbetspass</button>
    </form>
@endif
@endsection
