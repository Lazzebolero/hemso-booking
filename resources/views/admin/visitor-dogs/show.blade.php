@extends('layouts.app')

@section('content')
@php
    $vPrefix = \App\Support\ActiveRole::visitorDogsRoutePrefix();
    $visitSubtitle = 'Besök '.$dog->visit_date?->format('Y-m-d');
    if ($dog->tour_start_time) {
        $visitSubtitle .= ' · Turstart '.\Illuminate\Support\Str::of((string) $dog->tour_start_time)->substr(0, 5);
    }
@endphp

<div class="staff-page-stack">
    @include('partials.ui.flash-messages')

    <x-ui.page-header :title="$dog->dog_name" :subtitle="$visitSubtitle" icon="bi-heart-pulse">
        <x-slot:actions>
            <a href="{{ \App\Support\VisitorDogSupport::routeForDog($vPrefix . '.visitor-dogs.edit', $dog, $navQuery ?? []) }}" class="btn btn-primary">
                <i class="bi bi-pencil me-1"></i>Redigera
            </a>
            <a href="{{ $backNav['url'] ?? route($vPrefix . '.visitor-dogs.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>{{ $backNav['label'] ?? 'Till listan' }}
            </a>
            <form method="POST"
                  action="{{ route($vPrefix . '.visitor-dogs.destroy', $dog) }}"
                  class="d-inline"
                  onsubmit="return confirm('Ta bort denna hundregistrering? Eventuell bild raderas också.')">
                @csrf
                @method('DELETE')
                @include('partials.visitor-dogs.navigation-hidden-fields', ['navigationQuery' => $navQuery ?? []])
                <button type="submit" class="btn btn-outline-danger">
                    <i class="bi bi-trash me-1"></i>Ta bort
                </button>
            </form>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="admin-grid-2">
        <div class="page-card">
            <div class="section-title mb-3">Uppgifter</div>
            <dl class="row mb-0">
                <dt class="col-sm-4 small-muted">Namn</dt>
                <dd class="col-sm-8 fw-semibold">{{ $dog->dog_name }}</dd>

                <dt class="col-sm-4 small-muted">Ras</dt>
                <dd class="col-sm-8">{{ $dog->breed ?: '—' }}</dd>

                <dt class="col-sm-4 small-muted">Ägarens telefon</dt>
                <dd class="col-sm-8">{{ $dog->owner_phone ?: '—' }}</dd>

                <dt class="col-sm-4 small-muted">Datum</dt>
                <dd class="col-sm-8">{{ $dog->visit_date?->format('Y-m-d') }}</dd>

                <dt class="col-sm-4 small-muted">Turstart</dt>
                <dd class="col-sm-8">
                    @if($dog->tour_start_time)
                        {{ \Illuminate\Support\Str::of((string) $dog->tour_start_time)->substr(0, 5) }}
                    @else
                        —
                    @endif
                </dd>

                <dt class="col-sm-4 small-muted">Registrerad</dt>
                <dd class="col-sm-8">{{ $dog->created_at?->format('Y-m-d H:i') }}</dd>

                <dt class="col-sm-4 small-muted">Som roll</dt>
                <dd class="col-sm-8">{{ $dog->registered_as_role }}</dd>

                <dt class="col-sm-4 small-muted">Av användare</dt>
                <dd class="col-sm-8">{{ $dog->registrar?->name ?? '—' }} ({{ $dog->registrar?->email ?? '—' }})</dd>
            </dl>
        </div>

        <div class="page-card">
            <div class="section-title mb-3">Bild</div>
            @if($dog->photo_path)
                <img src="{{ route($vPrefix . '.visitor-dogs.photo', $dog) }}"
                     alt="Bild på {{ $dog->dog_name }}"
                     class="img-fluid rounded"
                     style="max-height: 420px; object-fit: contain;">
            @else
                <p class="small-muted mb-0">Ingen bild bifogad.</p>
            @endif
        </div>
    </div>

    @if(isset($activityLogs))
        <div class="page-card">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div class="section-title mb-0">Aktivitetslogg</div>
                @if(Route::has('admin.activity-logs.entity-history'))
                    <a href="{{ route('admin.activity-logs.entity-history', ['entityType' => \App\Support\VisitorDogActivityLogger::ENTITY_TYPE, 'entityId' => $dog->id]) }}"
                       class="btn btn-sm btn-outline-secondary">
                        Visa full historik
                    </a>
                @endif
            </div>

            @if($activityLogs->isEmpty())
                <p class="small-muted mb-0">Inga loggade händelser ännu.</p>
            @else
                <div class="table-responsive-modern">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Tid</th>
                                <th>Användare</th>
                                <th>Händelse</th>
                                <th>Beskrivning</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activityLogs as $log)
                                @php
                                    $actionClass = match ($log->action) {
                                        'created' => 'badge-soft badge-soft-success',
                                        'updated' => 'badge-soft badge-soft-warning',
                                        'deleted' => 'badge-soft badge-soft-danger',
                                        default => 'badge-soft badge-soft-secondary',
                                    };
                                @endphp
                                <tr>
                                    <td class="text-nowrap">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                                    <td>{{ $log->user?->name ?? '—' }}</td>
                                    <td><span class="{{ $actionClass }}">{{ $log->action }}</span></td>
                                    <td>{{ $log->description }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
