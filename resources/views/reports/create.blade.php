@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="mb-1">Ny felrapport</h2>
        <div class="muted">Skapa en felrapport med databaskopplad kategori och prioritet.</div>
    </div>
    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Tillbaka
    </a>
</div>

<form method="POST" action="{{ auth()->user()->role === 'guide' ? route('guide.reports.store') : route('admin.reports.store') }}">
    @csrf

    <div class="page-card">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="mb-3">
                    <label class="form-label">Rubrik</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Beskrivning</label>
                    <textarea name="description" class="form-control" rows="6" required>{{ old('description') }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Plats</label>
                    <input type="text" name="location" class="form-control" value="{{ old('location') }}">
                </div>
            </div>

            <div class="col-lg-4">
                <div class="page-card h-100">
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="category" class="form-select" required>
                            <option value="">Välj kategori</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->name }}" @selected(old('category') === $category->name)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Prioritet</label>
                        <select name="priority" class="form-select" required>
                            <option value="">Välj prioritet</option>
                            @foreach($priorities as $priority)
                                <option value="{{ $priority->name }}" @selected(old('priority') === $priority->name)>{{ $priority->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if(auth()->user()->role !== 'guide')
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                @foreach(['open' => 'Öppen', 'in_progress' => 'Pågår', 'resolved' => 'Löst', 'closed' => 'Stängd'] as $key => $label)
                                    <option value="{{ $key }}" @selected(old('status', 'open') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <button class="btn btn-primary w-100">
                        <i class="bi bi-save me-2"></i>Spara felrapport
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
