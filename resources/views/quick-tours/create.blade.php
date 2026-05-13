@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="mb-1">Starta snabbtur</h2>
        <div class="muted">Skapa en extra tur direkt och starta den omedelbart.</div>
    </div>

    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Tillbaka
    </a>
</div>

<form method="POST" action="{{ route('quick-tours.store') }}">
    @csrf

    <div class="page-card">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="section-title">Deltagare</div>

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Män</label>
                        <input type="number" min="0" name="men_count" class="form-control" value="{{ old('men_count', 0) }}" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Kvinnor</label>
                        <input type="number" min="0" name="women_count" class="form-control" value="{{ old('women_count', 0) }}" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Ungdomar</label>
                        <input type="number" min="0" name="youth_count" class="form-control" value="{{ old('youth_count', 0) }}" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Barn</label>
                        <input type="number" min="0" name="child_count" class="form-control" value="{{ old('child_count', 0) }}" required>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="form-label">Anteckning</label>
                    <textarea name="notes" class="form-control" rows="4">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="page-card h-100">
                    <div class="section-title">Inställningar</div>

                    @if(auth()->user()->role !== 'guide')
                        <div class="mb-3">
                            <label class="form-label">Guide</label>
                            <select name="guide_id" class="form-select">
                                <option value="">Ej tilldelad</option>
                                @foreach($guides as $guide)
                                    <option value="{{ $guide->id }}" @selected(old('guide_id') == $guide->id)>
                                        {{ $guide->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="alert alert-info">
                        <div class="fw-semibold mb-1">Detta händer när du sparar</div>
                        <div class="small">
                            Systemet skapar automatiskt:
                            <br>• en tur med status <strong>startad</strong>
                            <br>• en grupp/bokning kopplad till turen
                            <br>• namn: <strong>Snabbtur + datum/tid</strong>
                        </div>
                    </div>

                    <button class="btn btn-primary w-100">
                        <i class="bi bi-play-circle me-2"></i>Starta snabbtur
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection