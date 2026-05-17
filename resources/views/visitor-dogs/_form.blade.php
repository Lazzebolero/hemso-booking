@php
    $defaultVisitDate = $defaultVisitDate ?? now()->format('Y-m-d');
    $dog = $dog ?? null;
    $isEdit = $dog !== null;
    $formAction = $formAction ?? route('visitor-dogs.store');
    $cancelUrl = $cancelUrl ?? null;
    $photoUrl = $photoUrl ?? null;
    $tourStartValue = old('tour_start_time');
    if ($tourStartValue === null && $dog?->tour_start_time) {
        $tourStartValue = \Illuminate\Support\Str::of((string) $dog->tour_start_time)->substr(0, 5);
    }
@endphp

<form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="visitor-dog-form" data-offline-ignore>
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    @include('partials.visitor-dogs.navigation-hidden-fields', ['navigationQuery' => $navigationQuery ?? []])

    <div class="mb-3">
        <label for="dog_name" class="form-label fw-semibold">Hundens namn <span class="text-danger">*</span></label>
        <input type="text" name="dog_name" id="dog_name" class="form-control form-control-lg" required maxlength="120"
               value="{{ old('dog_name', $dog?->dog_name) }}" autocomplete="off" placeholder="t.ex. Rex">
    </div>

    <div class="mb-3">
        <label for="breed" class="form-label fw-semibold">Ras</label>
        <input type="text" name="breed" id="breed" class="form-control" maxlength="120"
               value="{{ old('breed', $dog?->breed) }}" placeholder="Valfritt">
    </div>

    <div class="mb-3">
        <label for="owner_phone" class="form-label fw-semibold">Telefon till ägare</label>
        <input type="tel" name="owner_phone" id="owner_phone" class="form-control" maxlength="40"
               value="{{ old('owner_phone', $dog?->owner_phone) }}" inputmode="tel" autocomplete="tel" placeholder="Valfritt">
    </div>

    <div class="mb-3">
        <label for="visit_date" class="form-label fw-semibold">Datum <span class="text-danger">*</span></label>
        <input type="date" name="visit_date" id="visit_date" class="form-control form-control-lg" required
               value="{{ old('visit_date', $dog?->visit_date?->format('Y-m-d') ?? $defaultVisitDate) }}">
    </div>

    <div class="mb-3">
        <label for="tour_start_time" class="form-label fw-semibold">Turstart</label>
        <input type="time" name="tour_start_time" id="tour_start_time" class="form-control"
               value="{{ $tourStartValue }}">
        <div class="form-text">Valfritt — om du vet vilken tid turen börjar.</div>
    </div>

    <div class="mb-4">
        <label for="photo" class="form-label fw-semibold">Bild</label>
        @if($isEdit && $photoUrl)
            <div class="mb-2">
                <img src="{{ $photoUrl }}" alt="Nuvarande bild" class="img-fluid rounded border" style="max-height: 200px; object-fit: contain;">
            </div>
            <div class="form-check mb-2">
                <input type="checkbox" name="remove_photo" id="remove_photo" value="1" class="form-check-input"
                       @checked(old('remove_photo'))>
                <label class="form-check-label" for="remove_photo">Ta bort nuvarande bild</label>
            </div>
        @endif
        <input type="file" name="photo" id="photo" class="form-control"
               accept="image/jpeg,image/png,image/gif,image/webp,image/heic,image/heif,.heic,.heif"
               capture="environment">
        <div class="form-text">
            @if($isEdit)
                Valfritt. Ladda upp en ny bild för att ersätta den befintliga. Max 10 MB.
            @else
                Valfritt. Max 10 MB. På mobil kan du ta foto direkt (kamera), som vid felrapport.
            @endif
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary btn-lg flex-grow-1">
            <i class="bi bi-check2-circle me-2"></i>{{ $isEdit ? 'Spara ändringar' : 'Spara' }}
        </button>
        @if($cancelUrl)
            <a href="{{ $cancelUrl }}" class="btn btn-outline-secondary btn-lg">Avbryt</a>
        @endif
    </div>
</form>
