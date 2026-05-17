@extends('layouts.guide')

@section('content')
<div class="page-card mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h2 class="page-title mb-1">Kameratest</h2>
            <div class="page-subtitle">
                Testar kamera via webbsidans videoström, utan vanligt filfält med capture.
            </div>
        </div>

        <a href="{{ route('guide.dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>
    </div>
</div>

<div class="guide-report-layout">
    <div class="guide-focus-card">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @error('photo')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror

        <div class="section-title">Ta testbild</div>

        <p class="small-muted">
            Den här sidan öppnar kameran med <code>getUserMedia</code>, tar en bild från videoströmmen och skalar den till max 1280 pixlar innan uppladdning.
        </p>

        <div class="camera-test-frame mb-3">
            <video id="camera-preview" class="camera-test-media" autoplay playsinline muted></video>
            <canvas id="camera-canvas" class="d-none"></canvas>
            <img id="camera-photo-preview" class="camera-test-media d-none" alt="Förhandsvisning av testbild">
        </div>

        <div id="camera-status" class="form-text mb-3">
            Tryck på "Starta kamera" för att ge sidan kameratillgång.
        </div>

        <div class="d-flex flex-wrap gap-2 mb-4">
            <button type="button" id="camera-start" class="btn btn-primary">
                <i class="bi bi-camera-video me-2"></i>Starta kamera
            </button>
            <button type="button" id="camera-capture" class="btn btn-outline-primary" disabled>
                <i class="bi bi-camera me-2"></i>Ta bild
            </button>
            <button type="button" id="camera-retake" class="btn btn-outline-secondary d-none">
                Ta om
            </button>
        </div>

        <form method="POST" action="{{ route('guide.camera-test.store') }}" enctype="multipart/form-data" id="camera-test-form">
            @csrf
            <input type="file" name="photo" id="camera-photo-file" class="d-none" accept="image/jpeg,image/png,image/webp">

            <button type="submit" id="camera-submit" class="btn btn-success btn-lg w-100" disabled>
                Skicka testbild
            </button>
        </form>
    </div>

    <div class="page-card guide-side-panel">
        <div class="section-title">Vad testas?</div>
        <div class="small-muted">
            Om detta fungerar på telefonen men vanligt kamerafält ger minnesfel, har vi bevis för att problemet ligger i Androids direkta <code>capture</code>-överlämning.
        </div>
    </div>
</div>

<style>
.camera-test-frame {
    background: #0f172a;
    border-radius: 1rem;
    overflow: hidden;
}

.camera-test-media {
    display: block;
    width: 100%;
    max-height: 70vh;
    object-fit: contain;
}
</style>

<script>
(() => {
    const startButton = document.getElementById('camera-start');
    const captureButton = document.getElementById('camera-capture');
    const retakeButton = document.getElementById('camera-retake');
    const submitButton = document.getElementById('camera-submit');
    const status = document.getElementById('camera-status');
    const video = document.getElementById('camera-preview');
    const canvas = document.getElementById('camera-canvas');
    const preview = document.getElementById('camera-photo-preview');
    const fileInput = document.getElementById('camera-photo-file');
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
            setStatus('Den här webbläsaren stöder inte kameratestet.');
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
            video.classList.remove('d-none');
            preview.classList.add('d-none');
            captureButton.disabled = false;
            submitButton.disabled = true;
            retakeButton.classList.add('d-none');
            setStatus('Kameran är igång. Ta en bild när motivet syns.');
        } catch (error) {
            setStatus('Kameran kunde inte startas. Kontrollera behörighet och att sidan körs via HTTPS.');
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
            if (!blob) {
                setStatus('Bilden kunde inte skapas.');
                return;
            }

            const file = new File([blob], 'kameratest.jpg', { type: 'image/jpeg' });
            const transfer = new DataTransfer();
            transfer.items.add(file);
            fileInput.files = transfer.files;

            preview.src = URL.createObjectURL(blob);
            preview.classList.remove('d-none');
            video.classList.add('d-none');
            captureButton.disabled = true;
            submitButton.disabled = false;
            retakeButton.classList.remove('d-none');
            stopCamera();
            setStatus(`Testbild skapad: ${width} x ${height}px, ${Math.round(blob.size / 1024)} KB.`);
        }, 'image/jpeg', 0.82);
    };

    startButton.addEventListener('click', startCamera);
    captureButton.addEventListener('click', capturePhoto);
    retakeButton.addEventListener('click', startCamera);
    window.addEventListener('pagehide', stopCamera);
})();
</script>
@endsection
