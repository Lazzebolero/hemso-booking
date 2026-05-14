@extends('layouts.app')

@section('content')
@php
    $vPrefix = $visitorDogsRoutePrefix ?? 'admin';
@endphp
<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3">
    <div>
        <h2 class="page-title">Besökshundar</h2>
        <div class="page-subtitle">
            Standard: dagens registreringar. Välj datumintervall för historik.
        </div>
    </div>
    <a href="{{ route($vPrefix . '.visitor-dogs.gallery', request()->only(['from_date', 'to_date'])) }}"
       class="btn btn-outline-primary">
        <i class="bi bi-images me-1"></i>Hundbilder
    </a>
</div>

<div class="page-card mb-4">
    <form method="GET" action="{{ route($vPrefix . '.visitor-dogs.index') }}" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Från datum</label>
            <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Till datum</label>
            <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
        </div>
        <div class="col-md-6">
            <button type="submit" class="btn btn-primary me-2">
                <i class="bi bi-funnel me-1"></i>Visa
            </button>
            <a href="{{ route($vPrefix . '.visitor-dogs.index') }}" class="btn btn-outline-secondary">Idag</a>
        </div>
    </form>
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
                                <a href="{{ route($vPrefix . '.visitor-dogs.show', $dog) }}"
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
                            <a href="{{ route($vPrefix . '.visitor-dogs.show', $dog) }}" class="btn btn-sm btn-outline-primary">Visa</a>
                            <form method="POST"
                                  action="{{ route($vPrefix . '.visitor-dogs.destroy', $dog) }}"
                                  class="d-inline"
                                  onsubmit="return confirm('Ta bort denna hundregistrering? Eventuell bild raderas också.')">
                                @csrf
                                @method('DELETE')
                                @foreach(request()->only(['from_date', 'to_date']) as $name => $value)
                                    @if(is_string($value) && $value !== '')
                                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                                    @endif
                                @endforeach
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

<style>
.visitor-dog-thumb {
    width: 56px;
    height: 56px;
    line-height: 0;
}
</style>
@endsection
