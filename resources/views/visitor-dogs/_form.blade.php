@php
    $defaultVisitDate = $defaultVisitDate ?? now()->format('Y-m-d');
@endphp

<form method="POST" action="{{ route('visitor-dogs.store') }}" enctype="multipart/form-data" class="visitor-dog-form">
    @csrf

    <div class="mb-3">
        <label for="dog_name" class="form-label fw-semibold">Hundens namn <span class="text-danger">*</span></label>
        <input type="text" name="dog_name" id="dog_name" class="form-control form-control-lg" required maxlength="120"
               value="{{ old('dog_name') }}" autocomplete="off" placeholder="t.ex. Rex">
    </div>

    <div class="mb-3">
        <label for="breed" class="form-label fw-semibold">Ras</label>
        <input type="text" name="breed" id="breed" class="form-control" maxlength="120"
               value="{{ old('breed') }}" placeholder="Valfritt">
    </div>

    <div class="mb-3">
        <label for="owner_phone" class="form-label fw-semibold">Telefon till ägare</label>
        <input type="tel" name="owner_phone" id="owner_phone" class="form-control" maxlength="40"
               value="{{ old('owner_phone') }}" inputmode="tel" autocomplete="tel" placeholder="Valfritt">
    </div>

    <div class="mb-3">
        <label for="visit_date" class="form-label fw-semibold">Datum <span class="text-danger">*</span></label>
        <input type="date" name="visit_date" id="visit_date" class="form-control form-control-lg" required
               value="{{ old('visit_date', $defaultVisitDate) }}">
    </div>

    <div class="mb-3">
        <label for="tour_start_time" class="form-label fw-semibold">Turstart</label>
        <input type="time" name="tour_start_time" id="tour_start_time" class="form-control"
               value="{{ old('tour_start_time') }}">
        <div class="form-text">Valfritt — om du vet vilken tid turen börjar.</div>
    </div>

    <div class="mb-4">
        <label for="photo" class="form-label fw-semibold">Bild</label>
        <input type="file" name="photo" id="photo" class="form-control"
               accept="image/jpeg,image/png,image/gif,image/webp,image/heic,image/heif,.heic,.heif"
               capture="environment">
        <div class="form-text">Valfritt. Max 10 MB. På mobil kan du ta foto direkt (kamera), som vid felrapport.</div>
    </div>

    <button type="submit" class="btn btn-primary btn-lg w-100">
        <i class="bi bi-check2-circle me-2"></i>Spara
    </button>
</form>
