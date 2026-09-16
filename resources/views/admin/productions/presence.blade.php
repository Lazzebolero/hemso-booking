@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Närvaro i berget</h2>
        <div class="page-subtitle">
            Vem som är inne och ute i aktuella projekt. Stämpling sker på /berget.
        </div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.productions.index') }}" class="btn btn-outline-secondary">Projekt</a>
        <a href="{{ route('admin.productions.log') }}" class="btn btn-outline-secondary">In/ut-logg</a>
    </div>
</div>

@forelse($boards as $board)
    @php
        $production = $board['production'];
    @endphp
    <div class="page-card mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div>
                <div class="section-title mb-1">{{ $production->name }}</div>
                <div class="small-muted">
                    @if($production->siteListLabel() !== '')
                        {{ $production->siteListLabel() }} ·
                    @endif
                    {{ $board['insideCount'] }} inne just nu
                </div>
            </div>
            <a href="{{ route('admin.productions.show', $production) }}" class="btn btn-sm btn-outline-secondary">Öppna projekt</a>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <h3 class="h6 fw-bold">Inne</h3>
                @include('admin.productions._presence-people', [
                    'title' => 'Deltagare',
                    'people' => $board['participantsInside'],
                    'empty' => 'Inga deltagare inne.',
                ])
                @include('admin.productions._presence-people', [
                    'title' => 'Personal',
                    'people' => $board['crewInside'],
                    'empty' => 'Ingen personal inne.',
                ])
            </div>
            <div class="col-md-6">
                <h3 class="h6 fw-bold">Ute</h3>
                @include('admin.productions._presence-people', [
                    'title' => 'Deltagare',
                    'people' => $board['participantsOutside'],
                    'empty' => 'Inga deltagare ute.',
                ])
                @include('admin.productions._presence-people', [
                    'title' => 'Personal',
                    'people' => $board['crewOutside'],
                    'empty' => 'Ingen personal ute.',
                ])
            </div>
        </div>

        @if($board['departedParticipants']->isNotEmpty())
            <div class="mt-3">
                @include('admin.productions._presence-people', [
                    'title' => 'Åkt ut',
                    'people' => $board['departedParticipants'],
                    'empty' => '',
                ])
            </div>
        @endif
    </div>
@empty
    <div class="page-card">
        <div class="muted py-3">
            Ingen aktiv produktion just nu.
            <a href="{{ route('admin.productions.index') }}">Skapa ett projekt</a>.
        </div>
    </div>
@endforelse
@endsection
