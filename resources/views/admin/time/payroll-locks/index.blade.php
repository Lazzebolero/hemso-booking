@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">
    <div>
        <h1 class="h3 mb-1">
            <i class="bi bi-lock me-2"></i>Låsta löneperioder
        </h1>
        <div class="text-muted">
            Personal kan inte ändra eller skicka in tider vars <strong>arbetsdatum</strong> ligger inom ett låst intervall.
            Stämpla ut fungerar fortfarande om ett pass råkar vara öppet.
        </div>
    </div>

    @if(Route::has('admin.time.index'))
        <a href="{{ route('admin.time.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-clock-history me-1"></i>Tidrapportering
        </a>
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm mb-4">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm mb-4">
        <ul class="mb-0 small ps-3">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h5 mb-3">Ny låsning</h2>
        <form method="POST" action="{{ route('admin.time.payroll-locks.store') }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-3">
                <label class="form-label" for="start_date">Från (datum)</label>
                <input type="date" name="start_date" id="start_date" class="form-control @error('start_date') is-invalid @enderror"
                       value="{{ old('start_date') }}" required>
                @error('start_date')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="end_date">Till (datum)</label>
                <input type="date" name="end_date" id="end_date" class="form-control @error('end_date') is-invalid @enderror"
                       value="{{ old('end_date') }}" required>
                @error('end_date')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-lock-fill me-1"></i>Lås intervall
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h5 mb-3">Aktiva lås</h2>

        @forelse($locks as $lock)
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 py-3 border-bottom">
                <div>
                    <div class="fw-semibold">{{ $lock->start_date->format('Y-m-d') }} – {{ $lock->end_date->format('Y-m-d') }}</div>
                    <div class="text-muted small">
                        @if($lock->lockedByUser)
                            Av {{ $lock->lockedByUser->name }}
                            @if($lock->locked_at)
                                · {{ $lock->locked_at->format('Y-m-d H:i') }}
                            @endif
                        @else
                            System
                        @endif
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.time.payroll-locks.destroy', $lock) }}" onsubmit="return confirm('Ta bort låset? Personal kan då ändra tider i intervallet igen.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-unlock me-1"></i>Ta bort
                    </button>
                </form>
            </div>
        @empty
            <p class="text-muted mb-0">Inga låsta perioder.</p>
        @endforelse
    </div>
</div>

@endsection
