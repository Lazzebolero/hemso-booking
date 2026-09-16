@php
    $defaultVisitDate = $defaultVisitDate ?? now()->format('Y-m-d');
    $dog = $dog ?? null;
    $isEdit = $dog !== null;
    $photoCompletionOnly = $photoCompletionOnly ?? false;
    $formAction = $formAction ?? route('visitor-dogs.store');
    $cancelUrl = $cancelUrl ?? null;
    $photoUrl = $photoUrl ?? null;
    $tourStartValue = old('tour_start_time');
    if ($tourStartValue === null && $dog?->tour_start_time) {
        $tourStartValue = \Illuminate\Support\Str::of((string) $dog->tour_start_time)->substr(0, 5);
    }
@endphp

<form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="visitor-dog-form">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    @include('partials.visitor-dogs.navigation-hidden-fields', ['navigationQuery' => $navigationQuery ?? []])

    @if($photoCompletionOnly)
        <div class="alert alert-light border mb-4">
            <div class="fw-semibold mb-1">{{ $dog->dog_name }}</div>
            <div class="small-muted mb-1">
                Besök {{ $dog->visit_date?->format('Y-m-d') }}
                @if($dog->tour_start_time)
                    · Turstart {{ \Illuminate\Support\Str::of((string) $dog->tour_start_time)->substr(0, 5) }}
                @endif
            </div>
            @if($dog->breed)
                <div class="small-muted">Ras: {{ $dog->breed }}</div>
            @endif
            @if($dog->owner_phone)
                <div class="small-muted">Telefon: {{ $dog->owner_phone }}</div>
            @endif
        </div>

        @include('partials.visitor-dogs.care-flags-fields', ['dog' => $dog])
    @else
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

    @include('partials.visitor-dogs.care-flags-fields', ['dog' => $dog])
    @endif

    <div class="mb-4">
        <label for="photo" class="form-label fw-semibold">Bild</label>
        @if($isEdit && $photoUrl && ! $photoCompletionOnly)
            <div class="mb-2">
                <img src="{{ $photoUrl }}" alt="Nuvarande bild" class="img-fluid rounded border" style="max-height: 200px; object-fit: contain;">
            </div>
            <div class="form-check mb-2">
                <input type="checkbox" name="remove_photo" id="remove_photo" value="1" class="form-check-input"
                       @checked(old('remove_photo'))>
                <label class="form-check-label" for="remove_photo">Ta bort nuvarande bild</label>
            </div>
        @endif
        <div class="visitor-dog-camera mb-3">
            <div class="d-flex flex-wrap gap-2 mb-2">
                <button type="button" id="visitor-dog-camera-start" class="btn btn-outline-primary">
                    <i class="bi bi-camera-video me-2"></i>Starta kamera
                </button>
                <button type="button" id="visitor-dog-camera-capture" class="btn btn-outline-secondary" disabled>
                    <i class="bi bi-camera me-2"></i>Ta bild
                </button>
                <button type="button" id="visitor-dog-camera-retake" class="btn btn-outline-secondary d-none">
                    Ta om
                </button>
            </div>

            <div id="visitor-dog-camera-status" class="form-text mb-2">
                Kameran öppnas i sidan och bilden minskas innan den sparas i formuläret.
            </div>

            <div id="visitor-dog-camera-frame" class="visitor-dog-camera-frame d-none">
                <video id="visitor-dog-camera-preview" class="visitor-dog-camera-media" autoplay playsinline muted></video>
                <canvas id="visitor-dog-camera-canvas" class="d-none"></canvas>
                <img id="visitor-dog-camera-photo-preview" class="visitor-dog-camera-media d-none" alt="Förhandsvisning av hundbild">
            </div>
        </div>

        <label for="photo" class="form-label fw-semibold">Eller välj befintlig bild</label>
        <input type="file" name="photo" id="photo" class="form-control"
               accept="image/jpeg,image/png,image/gif,image/webp,image/heic,image/heif,.heic,.heif">
        <div class="form-text">
            @if($photoCompletionOnly)
                Ta ett foto med kameran eller välj en bild för att komplettera rapporten. Max 10 MB.
            @elseif($isEdit)
                Valfritt. Ladda upp en ny bild för att ersätta den befintliga. Max 10 MB.
            @else
                Valfritt. Max 10 MB. Använd kameraknappen ovan eller välj en redan sparad bild.
            @endif
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary btn-lg flex-grow-1">
            <i class="bi bi-check2-circle me-2"></i>{{ $photoCompletionOnly ? 'Spara bild' : ($isEdit ? 'Spara ändringar' : 'Spara') }}
        </button>
        @if($cancelUrl)
            <a href="{{ $cancelUrl }}" class="btn btn-outline-secondary btn-lg">Avbryt</a>
        @endif
    </div>
</form>

<style>
.visitor-dog-camera-frame {
    background: #0f172a;
    border-radius: 1rem;
    overflow: hidden;
}

.visitor-dog-camera-media {
    display: block;
    width: 100%;
    max-height: 60vh;
    object-fit: contain;
}
</style>

<script>
(() => {
    const fileInput = document.getElementById('photo');
    const startButton = document.getElementById('visitor-dog-camera-start');
    const captureButton = document.getElementById('visitor-dog-camera-capture');
    const retakeButton = document.getElementById('visitor-dog-camera-retake');
    const status = document.getElementById('visitor-dog-camera-status');
    const frame = document.getElementById('visitor-dog-camera-frame');
    const video = document.getElementById('visitor-dog-camera-preview');
    const canvas = document.getElementById('visitor-dog-camera-canvas');
    const preview = document.getElementById('visitor-dog-camera-photo-preview');
    let stream = null;

    const setStatus = (message) => {
        status.textContent = message;
    };

    const stopCamera = () => {
        if (stream) {
            stream.getTracks().forEach((track) => track.stop());
            stream = null;
        }
    };

    const startCamera = async () => {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setStatus('Den här webbläsaren stöder inte kamerafunktionen. Välj en befintlig bild i stället.');
            return;
        }

        try {
            stopCamera();
            stream = await navigator.mediaDevices.getUserMedia({
                audio: false,
                video: {
                    facingMode: { ideal: 'environment' },
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                },
            });

            video.srcObject = stream;
            frame.classList.remove('d-none');
            video.classList.remove('d-none');
            preview.classList.add('d-none');
            captureButton.disabled = false;
            retakeButton.classList.add('d-none');
            setStatus('Kameran är igång. Tryck på "Ta bild" när hunden syns.');
        } catch (error) {
            setStatus('Kameran kunde inte startas. Kontrollera kamerabehörighet eller välj en befintlig bild.');
        }
    };

    const capturePhoto = () => {
        if (!video.videoWidth || !video.videoHeight) {
            setStatus('Kameran är inte redo ännu.');
            return;
        }

        const maxSize = 1280;
        const scale = Math.min(1, maxSize / Math.max(video.videoWidth, video.videoHeight));
        const width = Math.round(video.videoWidth * scale);
        const height = Math.round(video.videoHeight * scale);

        canvas.width = width;
        canvas.height = height;
        canvas.getContext('2d').drawImage(video, 0, 0, width, height);

        canvas.toBlob((blob) => {
            if (!blob || typeof DataTransfer === 'undefined') {
                setStatus('Bilden kunde inte skapas. Välj en befintlig bild i stället.');
                return;
            }

            const file = new File([blob], 'hundbild.jpg', { type: 'image/jpeg' });
            const transfer = new DataTransfer();
            transfer.items.add(file);
            fileInput.files = transfer.files;

            preview.src = URL.createObjectURL(blob);
            preview.classList.remove('d-none');
            video.classList.add('d-none');
            captureButton.disabled = true;
            retakeButton.classList.remove('d-none');
            stopCamera();
            setStatus(`Bild vald: ${width} x ${height}px, ${Math.round(blob.size / 1024)} KB.`);
        }, 'image/jpeg', 0.82);
    };

    startButton.addEventListener('click', startCamera);
    captureButton.addEventListener('click', capturePhoto);
    retakeButton.addEventListener('click', startCamera);
    window.addEventListener('pagehide', stopCamera);
})();
</script>
