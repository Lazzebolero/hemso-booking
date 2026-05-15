@extends('layouts.app')

@section('content')
@php
    $vPrefix = \App\Support\ActiveRole::visitorDogsRoutePrefix();
@endphp

<div class="staff-page-stack">
    @include('partials.ui.flash-messages')

    <x-ui.page-header
        title="Besökshundar"
        subtitle="Standard: dagens registreringar. Välj datumintervall för historik."
        icon="bi-heart-pulse"
    >
        <x-slot:actions>
            <a href="{{ route($vPrefix . '.visitor-dogs.gallery', request()->only(['from_date', 'to_date'])) }}"
               class="btn btn-outline-primary">
                <i class="bi bi-images me-1"></i>Hundbilder
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="page-card">
        @include('partials.visitor-dogs.date-filter', [
            'action' => route($vPrefix . '.visitor-dogs.index'),
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'resetUrl' => route($vPrefix . '.visitor-dogs.index'),
        ])
    </div>

    <div class="page-card">
        <div class="table-responsive-modern">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="text-center" style="width:76px;">Bild</th>
                        <th>Datum</th>
                        <th>Namn</th>
                        <th>Ras</th>
                        <th>Telefon</th>
                        <th>Turstart</th>
                        <th>Registrerad av</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dogs as $dog)
                        <tr>
                            <td class="text-center align-middle">
                                @if($dog->photo_path)
                                    <a href="{{ \App\Support\VisitorDogSupport::routeForDog($vPrefix . '.visitor-dogs.show', $dog, \App\Support\VisitorDogSupport::linkQueryForReturn(request(), \App\Support\VisitorDogSupport::RETURN_INDEX)) }}"
                                       class="visitor-dog-thumb d-inline-block rounded overflow-hidden border"
                                       title="Visa detaljer">
                                        <img src="{{ route($vPrefix . '.visitor-dogs.photo', $dog) }}"
                                             alt=""
                                             width="56"
                                             height="56"
                                             loading="lazy"
                                             style="object-fit:cover;display:block;">
                                    </a>
                                @else
                                    <span class="small-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $dog->visit_date?->format('Y-m-d') }}</td>
                            <td class="fw-semibold">{{ $dog->dog_name }}</td>
                            <td>{{ $dog->breed ?: '—' }}</td>
                            <td>{{ $dog->owner_phone ?: '—' }}</td>
                            <td>
                                @if($dog->tour_start_time)
                                    {{ \Illuminate\Support\Str::of((string) $dog->tour_start_time)->substr(0, 5) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <span class="small-muted">{{ $dog->registrar?->name ?? '—' }}</span>
                                <span class="badge bg-light text-dark ms-1">{{ $dog->registered_as_role }}</span>
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ \App\Support\VisitorDogSupport::routeForDog($vPrefix . '.visitor-dogs.show', $dog, \App\Support\VisitorDogSupport::linkQueryForReturn(request(), \App\Support\VisitorDogSupport::RETURN_INDEX)) }}" class="btn btn-sm btn-outline-primary">Visa</a>
                                <a href="{{ \App\Support\VisitorDogSupport::routeForDog($vPrefix . '.visitor-dogs.edit', $dog, \App\Support\VisitorDogSupport::linkQueryForReturn(request(), \App\Support\VisitorDogSupport::RETURN_INDEX)) }}" class="btn btn-sm btn-outline-secondary">Redigera</a>
                                <form method="POST"
                                      action="{{ route($vPrefix . '.visitor-dogs.destroy', $dog) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('Ta bort denna hundregistrering? Eventuell bild raderas också.')">
                                    @csrf
                                    @method('DELETE')
                                    @include('partials.visitor-dogs.navigation-hidden-fields', [
                                        'navigationQuery' => \App\Support\VisitorDogSupport::linkQueryForReturn(request(), \App\Support\VisitorDogSupport::RETURN_INDEX),
                                    ])
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Ta bort</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">Inga registreringar i valt intervall.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $dogs->links() }}
        </div>
    </div>
</div>
@endsection
