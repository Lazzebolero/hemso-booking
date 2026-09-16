@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">In/ut-logg</h2>
        <div class="page-subtitle">
            Alla in- och utstämplingar i berget, senaste först.
        </div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.productions.presence') }}" class="btn btn-outline-secondary">Närvaro i berget</a>
        <a href="{{ route('admin.productions.index') }}" class="btn btn-outline-secondary">Projekt</a>
    </div>
</div>

<div class="page-card">
    <form method="GET" action="{{ route('admin.productions.log') }}" class="row g-2 align-items-end mb-3">
        <div class="col-md-4">
            <label class="form-label" for="production_id">Projekt</label>
            <select id="production_id" name="production_id" class="form-select" onchange="this.form.submit()">
                <option value="">Alla projekt</option>
                @foreach($productions as $production)
                    <option value="{{ $production->id }}" @selected($selectedProductionId === $production->id)>
                        {{ $production->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Tid</th>
                    <th>Projekt</th>
                    <th>Person</th>
                    <th>Händelse</th>
                    <th>Av</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->occurred_at->format('Y-m-d H:i') }}</td>
                        <td>{{ $log->production?->name ?? '—' }}</td>
                        <td>{{ $log->personName() }}</td>
                        <td>
                            {{ $log->directionLabel() }}
                            @if($log->with_group)
                                <span class="small-muted">· grupp</span>
                            @endif
                        </td>
                        <td>{{ $log->recorder?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="muted">Inga stämplingar ännu.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div class="mt-3">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
