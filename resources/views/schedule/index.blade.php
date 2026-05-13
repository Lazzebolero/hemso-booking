@extends(in_array(session('active_role'), [\App\Support\Roles::GUIDE, \App\Support\Roles::RESTAURANT], true) ? 'layouts.guide' : 'layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Mitt schema</h2>
        <div class="page-subtitle">Översikt över dina kommande arbetspass.</div>
    </div>
</div>

<div class="page-card mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Utgå från datum</label>
            <input type="date" name="date" class="form-control" value="{{ $selectedDate->toDateString() }}">
        </div>

        <div class="col-md-3">
            <button class="btn btn-outline-secondary">Visa vecka</button>
        </div>
    </form>
</div>

<div class="page-card mb-4">
    <div class="section-title mb-3">Kommande pass</div>

    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Start</th>
                    <th>Slut</th>
                    <th>Roll</th>
					<th>Funktion</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($upcomingShifts as $shift)
                    <tr>
                        <td>{{ $shift->shift_date->format('Y-m-d') }}</td>
                        <td>{{ substr($shift->start_time, 0, 5) }}</td>
                        <td>{{ $shift->end_time ? substr($shift->end_time, 0, 5) : '-' }}</td>
                        <td>{{ ucfirst($shift->shift_role) }}</td>
						<td>
        @if($shift->shift_function)
            {{ \App\Models\WorkShift::restaurantFunctions()[$shift->shift_function] ?? ucfirst($shift->shift_function) }}
        @else
            -
        @endif
    </td>
                        <td>{{ ucfirst($shift->status) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Inga kommande pass.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="page-card">
    <div class="section-title mb-3">Den här veckan</div>

    @foreach($weekDays as $day)
        @php
            $dayShifts = $shifts->filter(fn ($shift) => $shift->shift_date->toDateString() === $day->toDateString());
        @endphp

        <div class="mb-3">
            <h4>{{ $day->translatedFormat('l Y-m-d') }}</h4>

            <div class="table-responsive-modern">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Start</th>
                            <th>Slut</th>
                            <th>Roll</th>
							<th>Funktion</th>
                            <th>Status</th>
                            <th>Anteckning</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dayShifts as $shift)
                            <tr>
                                <td>{{ substr($shift->start_time, 0, 5) }}</td>
                                <td>{{ $shift->end_time ? substr($shift->end_time, 0, 5) : '-' }}</td>
                                <td>{{ ucfirst($shift->shift_role) }}</td>
								<td>
        @if($shift->shift_function)
            {{ \App\Models\WorkShift::restaurantFunctions()[$shift->shift_function] ?? ucfirst($shift->shift_function) }}
        @else
            -
        @endif
    </td>
                                <td>{{ ucfirst($shift->status) }}</td>
                                <td>{{ $shift->notes ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">Inga pass denna dag.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>
@endsection