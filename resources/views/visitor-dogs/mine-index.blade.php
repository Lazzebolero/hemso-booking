@extends($useGuideLayout ? 'layouts.guide' : 'layouts.app')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
    <div>
        <h2 class="page-title" @if($useGuideLayout) style="font-size: 1.15rem;" @endif>Mina besökshundar</h2>
        <p class="page-subtitle mb-0" @if($useGuideLayout) style="font-size: 0.88rem;" @endif>
            Registreringar du har lagt in. Standard: dagens datum.
        </p>
    </div>
    <a href="{{ route('visitor-dogs.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Ny registrering
    </a>
</div>

@if($useGuideLayout)
    <div class="guide-card mb-3">
@else
    <div class="page-card mb-4">
@endif
    <form method="GET" action="{{ route('visitor-dogs.index') }}" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Från datum</label>
            <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Till datum</label>
            <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary me-2">Visa</button>
            <a href="{{ route('visitor-dogs.index') }}" class="btn btn-outline-secondary">Idag</a>
        </div>
    </form>
</div>

@if($useGuideLayout)
    <div class="guide-card">
@else
    <div class="page-card">
@endif
    @if($dogs->isEmpty())
        <p class="text-muted mb-0">Inga registreringar i valt intervall.</p>
    @else
        <div class="table-responsive-modern">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Namn</th>
                        <th>Ras</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dogs as $dog)
                        <tr>
                            <td>{{ $dog->visit_date?->format('Y-m-d') }}</td>
                            <td class="fw-semibold">{{ $dog->dog_name }}</td>
                            <td>{{ $dog->breed ?: '—' }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('visitor-dogs.show', $dog) }}" class="btn btn-sm btn-outline-primary">Visa</a>
                                <a href="{{ route('visitor-dogs.edit', $dog) }}" class="btn btn-sm btn-outline-secondary">Redigera</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $dogs->links() }}</div>
    @endif
</div>
@endsection
