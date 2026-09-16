@extends('layouts.guide')

@section('content')
<div class="guide-card mb-3">
    <div class="page-header mb-0 pb-3" style="border-bottom: 1px solid var(--brand-line-soft);">
        <div>
            <h2 class="page-title" style="font-size: 1.15rem;">Ladda upp turbild</h2>
            <p class="page-subtitle mb-0" style="font-size: 0.88rem;">
                {{ $tour->tourType?->name ?? 'Tur' }} {{ $tour->tour_date?->format('Y-m-d') }} {{ ! empty($tour->start_time) ? substr($tour->start_time, 0, 5) : '' }}
            </p>
        </div>
    </div>
</div>

<div class="guide-card">
    @include('partials.ui.flash-messages', ['guide' => true])

    <form method="POST" action="{{ route('guide.tours.photos.store', $tour, false) }}" enctype="multipart/form-data">
        @csrf

        <div class="mb-4">
            <label class="form-label fw-semibold">Bild</label>
            <div class="tour-photo-camera mb-3">
                <div class="d-flex flex-wrap gap-2 mb-2">
                    <button type="button" id="tour-photo-camera-start" class="btn btn-outline-primary">
                        <i class="bi bi-camera-video me-2"></i>Starta kamera
                    </button>
                    <button type="button" id="tour-photo-camera-capture" class="btn btn-outline-secondary" disabled>
                        <i class="bi bi-camera me-2"></i>Ta bild
                    </button>
                    <button type="button" id="tour-photo-camera-retake" class="btn btn-outline-secondary d-none">
                        Ta om
                    </button>
                </div>

                <div id="tour-photo-camera-status" class="form-text mb-2">
                    Kameran öppnas i sidan och bilden minskas innan den sparas i formuläret.
                </div>

                <div id="tour-photo-camera-frame" class="tour-photo-camera-frame d-none">
                    <video id="tour-photo-camera-preview" class="tour-photo-camera-media" autoplay playsinline muted></video>
                    <canvas id="tour-photo-camera-canvas" class="d-none"></canvas>
                    <img id="tour-photo-camera-photo-preview" class="tour-photo-camera-media d-none" alt="Förhandsvisning av turbild">
                </div>
            </div>

            <label for="photo" class="form-label fw-semibold">Eller välj befintlig bild</label>
            <input
                type="file"
                name="photo"
                id="photo"
                class="form-control"
                accept="image/jpeg,image/png,image/gif,image/webp"
                required
            >
            <div class="form-text">JPG, PNG, GIF eller WebP. Max 10 MB.</div>

            @error('photo')
                <div class="text-danger mt-2">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label for="caption" class="form-label fw-semibold">Bildtext (valfritt)</label>
            <input
                type="text"
                name="caption"
                id="caption"
                class="form-control"
                maxlength="255"
                value="{{ old('caption') }}"
                placeholder="Ex. Företagsgrupp vid kanonen"
            >

            @error('caption')
                <div class="text-danger mt-2">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary btn-lg flex-grow-1">
                <i class="bi bi-camera me-2"></i>Ladda upp bild
            </button>
            <a href="{{ route('guide.tours.show', $tour, false) }}" class="btn btn-outline-secondary btn-lg">Avbryt</a>
        </div>
    </form>
</div>

<style>
.tour-photo-camera-frame {
    background: #0f172a;
    border-radius: 1rem;
    overflow: hidden;
}

.tour-photo-camera-media {
    display: block;
    width: 100%;
    max-height: 60vh;
    object-fit: contain;
}
</style>

<script>
(() => {
    const fileInput = document.getElementById('photo');
    const startButton = document.getElementById('tour-photo-camera-start');
    const captureButton = document.getElementById('tour-photo-camera-capture');
    const retakeButton = document.getElementById('tour-photo-camera-retake');
    const status = document.getElementById('tour-photo-camera-status');
    const frame = document.getElementById('tour-photo-camera-frame');
    const video = document.getElementById('tour-photo-camera-preview');
    const canvas = document.getElementById('tour-photo-camera-canvas');
    const preview = document.getElementById('tour-photo-camera-photo-preview');
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
            setStatus('Kameran är igång. Tryck på "Ta bild" när motivet syns.');
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

            const file = new File([blob], 'turbild.jpg', { type: 'image/jpeg' });
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
@endsection
