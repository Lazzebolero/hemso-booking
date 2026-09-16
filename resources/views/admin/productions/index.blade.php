@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Projekt</h2>
        <div class="page-subtitle">
            Externa TV-produktioner: skapa projekt, lägg till admin och personer.
            Närvaro stämplas på /berget.
        </div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.productions.presence') }}" class="btn btn-outline-secondary">Närvaro i berget</a>
        <a href="{{ route('admin.productions.log') }}" class="btn btn-outline-secondary">In/ut-logg</a>
    </div>
</div>

<div class="page-card mb-4">
    <div class="section-title mb-3">Ny produktion</div>
    <form method="POST" action="{{ route('admin.productions.store') }}">
        @csrf
        @include('admin.productions._details-fields', ['fieldId' => 'new_production'])
        @include('admin.productions._admin-fields', ['fieldId' => 'new_production'])
        <div class="mt-3">
            <button class="btn btn-primary" type="submit">Skapa</button>
        </div>
    </form>
</div>

<div class="page-card">
    <div class="section-title mb-3">Produktioner</div>
    @forelse($productions as $production)
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 py-3 border-bottom">
            <div>
                <div class="fw-bold">{{ $production->name }}</div>
                <div class="small-muted">
                    {{ $production->starts_on->format('Y-m-d') }} – {{ $production->ends_on->format('Y-m-d') }}
                    @if($production->siteListLabel() !== '')
                        · {{ $production->siteListLabel() }}
                    @endif
                    · {{ $production->people_count }} personer
                    @if($current && $current->id === $production->id)
                        · aktiv nu
                    @endif
                </div>
            </div>
            <a class="btn btn-outline-primary" href="{{ route('admin.productions.show', $production) }}">Öppna</a>
        </div>
    @empty
        <div class="muted py-3">Ingen produktion skapad ännu.</div>
    @endforelse
</div>
@endsection
