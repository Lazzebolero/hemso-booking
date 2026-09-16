@extends('layouts.app')

@section('content')
@php
    $fp = $routePrefix ?? 'admin';
@endphp

<div class="staff-page-stack">
    @include('partials.ui.flash-messages')

    <x-ui.page-header
        title="Anläggningsminnen"
        :subtitle="$canAdministrate
            ? 'Granska och administrera alla insamlade minnen från guider och värdar.'
            : 'Granska arkivet och ta bort avvisade eller ointressanta minnen.'"
        icon="bi-journal-text"
    >
        <x-slot:actions>
            @if(($newCount ?? 0) > 0)
                <span class="badge bg-danger align-self-center">{{ $newCount }} nya</span>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="page-card">
        <form method="GET" action="{{ route($fp . '.facility-memories.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="status">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="all" @selected($status === 'all')>Alla</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label" for="type">Typ</label>
                <select name="type" id="type" class="form-select">
                    <option value="all" @selected($type === 'all')>Alla</option>
                    <option value="text" @selected($type === 'text')>Text</option>
                    <option value="audio" @selected($type === 'audio')>Ljud</option>
                </select>
            </div>

            <div class="col-md-5">
                <label class="form-label" for="q">Sök</label>
                <input
                    type="search"
                    name="q"
                    id="q"
                    class="form-control"
                    value="{{ $search }}"
                    placeholder="Berättelse, plats, insamlare, namn…"
                >
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Filtrera</button>
                <a href="{{ route($fp . '.facility-memories.index') }}" class="btn btn-outline-secondary">Rensa</a>
            </div>
        </form>
    </div>

    <div class="page-card">
        <div class="table-responsive-modern">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Typ</th>
                        <th>Status</th>
                        <th>Insamlad av</th>
                        <th>Plats / tid</th>
                        <th>Sammanfattning</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($memories as $memory)
                        <tr>
                            <td>{{ $memory->created_at?->format('Y-m-d H:i') }}</td>
                            <td>
                                {{ $memory->typeLabel() }}
                                @if($memory->isAudio() && $memory->formattedAudioDuration())
                                    <span class="small-muted">({{ $memory->formattedAudioDuration() }})</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $memory->status === 'submitted' ? 'bg-danger' : 'bg-secondary' }}">
                                    {{ $memory->statusLabel() }}
                                </span>
                            </td>
                            <td>{{ $memory->collectedBy?->name ?? '—' }}</td>
                            <td>
                                {{ $memory->location_text ?: '—' }}
                                @if($memory->era_text)
                                    <div class="small-muted">{{ $memory->era_text }}</div>
                                @endif
                            </td>
                            <td>{{ $memory->summaryText(120) }}</td>
                            <td class="text-end">
                                <a href="{{ route($fp . '.facility-memories.show', $memory) }}" class="btn btn-sm btn-outline-primary">
                                    Öppna
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">Inga minnen hittades.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $memories->links() }}
        </div>
    </div>
</div>
@endsection
