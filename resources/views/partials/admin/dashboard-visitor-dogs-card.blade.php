@props([
    'prefix',
    'todayVisitorDogs' => collect(),
    'defaultVisitDate' => now()->format('Y-m-d'),
])

@php
    $activeRole = \App\Support\ActiveRole::slug();
    $canQuickRegister = Route::has($prefix . '.visitor-dogs.quick-store');
@endphp

@if($canQuickRegister)
    <div class="page-card compact-card mb-3">
        <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-3">
            <div>
                <div class="section-title mb-1">Besökshundar idag</div>
                <div class="small-muted">
                    Registrera hund utan bild här. Värden kan lägga till bild senare i appen.
                </div>
            </div>

            @if(Route::has($prefix . '.visitor-dogs.index'))
                <a href="{{ route($prefix . '.visitor-dogs.index') }}" class="btn btn-sm btn-outline-secondary">
                    Alla hundar
                </a>
            @endif
        </div>

        <form method="POST" action="{{ route($prefix . '.visitor-dogs.quick-store') }}" class="row g-2 align-items-end mb-3">
            @csrf
            <input type="hidden" name="visit_date" value="{{ $defaultVisitDate }}">

            <div class="col-md-4">
                <label for="dashboard_dog_name" class="form-label small muted filter-label">Hundens namn</label>
                <input
                    type="text"
                    name="dog_name"
                    id="dashboard_dog_name"
                    class="form-control form-control-sm"
                    required
                    maxlength="120"
                    placeholder="t.ex. Rex"
                    value="{{ old('dog_name') }}"
                    autocomplete="off"
                >
            </div>

            <div class="col-md-3">
                <label for="dashboard_dog_breed" class="form-label small muted filter-label">Ras</label>
                <input
                    type="text"
                    name="breed"
                    id="dashboard_dog_breed"
                    class="form-control form-control-sm"
                    maxlength="120"
                    placeholder="Valfritt"
                    value="{{ old('breed') }}"
                >
            </div>

            <div class="col-md-2">
                <label for="dashboard_dog_phone" class="form-label small muted filter-label">Telefon ägare</label>
                <input
                    type="tel"
                    name="owner_phone"
                    id="dashboard_dog_phone"
                    class="form-control form-control-sm"
                    maxlength="40"
                    placeholder="Valfritt"
                    value="{{ old('owner_phone') }}"
                    inputmode="tel"
                >
            </div>

            <div class="col-md-2">
                <label for="dashboard_dog_tour_start" class="form-label small muted filter-label">Turstart</label>
                <input
                    type="time"
                    name="tour_start_time"
                    id="dashboard_dog_tour_start"
                    class="form-control form-control-sm"
                    value="{{ old('tour_start_time') }}"
                >
            </div>

            <div class="col-md-1 d-grid">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg"></i>
                </button>
            </div>
        </form>

        @error('dog_name')
            <div class="text-danger small mb-2">{{ $message }}</div>
        @enderror
        @error('breed')
            <div class="text-danger small mb-2">{{ $message }}</div>
        @enderror
        @error('owner_phone')
            <div class="text-danger small mb-2">{{ $message }}</div>
        @enderror
        @error('tour_start_time')
            <div class="text-danger small mb-2">{{ $message }}</div>
        @enderror

        @if($todayVisitorDogs->isNotEmpty())
            <div class="table-responsive-modern">
                <table class="table-modern dashboard-table">
                    <thead>
                        <tr>
                            <th>Hund</th>
                            <th style="width: 90px;">Turstart</th>
                            <th style="width: 120px;">Bild</th>
                            <th style="width: 130px;">Åtgärd</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($todayVisitorDogs as $dog)
                            @php
                                $photoEditUrl = null;

                                if ($dog->needsPhoto()) {
                                    if ($activeRole === \App\Support\Roles::HOST && Route::has('visitor-dogs.edit')) {
                                        $photoEditUrl = route('visitor-dogs.edit', $dog);
                                    } elseif (Route::has($prefix . '.visitor-dogs.edit')) {
                                        $photoEditUrl = route($prefix . '.visitor-dogs.edit', $dog);
                                    }
                                }
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $dog->dog_name }}</div>
                                    @if($dog->breed)
                                        <div class="small-muted">{{ $dog->breed }}</div>
                                    @endif
                                    @if($dog->owner_phone)
                                        <div class="small-muted">{{ $dog->owner_phone }}</div>
                                    @endif
                                </td>
                                <td>
                                    {{ $dog->tour_start_time ? substr((string) $dog->tour_start_time, 0, 5) : '—' }}
                                </td>
                                <td>
                                    @if($dog->needsPhoto())
                                        <span class="badge-soft badge-soft-warning">Saknar bild</span>
                                    @else
                                        <span class="badge-soft badge-soft-success">Har bild</span>
                                    @endif
                                </td>
                                <td>
                                    @if($photoEditUrl)
                                        <a href="{{ $photoEditUrl }}" class="btn btn-sm btn-outline-secondary">
                                            Lägg till bild
                                        </a>
                                    @elseif(Route::has($prefix . '.visitor-dogs.show'))
                                        <a href="{{ route($prefix . '.visitor-dogs.show', $dog) }}" class="btn btn-sm btn-outline-secondary">
                                            Visa
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="small-muted">Inga besökshundar registrerade idag ännu.</div>
        @endif
    </div>
@endif
