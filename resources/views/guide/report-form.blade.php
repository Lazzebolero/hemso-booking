@extends('layouts.guide')

@section('content')
@php
    $maxAttachments = (int) ($maxAttachments ?? 5);
@endphp
<div class="page-card mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h2 class="page-title mb-1">Ny felrapport</h2>
            <div class="page-subtitle">
                Rapportera fel och avvikelser snabbt och tydligt direkt från guidevyn.
            </div>
        </div>

        <a href="{{ route('guide.dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Tillbaka
        </a>
    </div>
</div>

<form method="POST" action="{{ route('guide.reports.store') }}" enctype="multipart/form-data">
    @csrf

    <div class="guide-report-layout">
        <div class="guide-focus-card">
            <div class="section-title">Grunduppgifter</div>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Rubrik</label>
                    <input
                        type="text"
                        name="title"
                        class="form-control"
                        value="{{ old('title') }}"
                        placeholder="Kort rubrik för problemet"
                        required
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">Kategori</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">Välj kategori</option>
                        @foreach(($categories ?? collect()) as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Klassning</label>
                    <select name="priority_id" class="form-select" required>
                        <option value="">Välj klassning</option>
                        @foreach(($priorities ?? collect()) as $priority)
                            <option value="{{ $priority->id }}" @selected((string) old('priority_id') === (string) $priority->id)>
                                {{ $priority->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Plats</label>
                    <select name="location_id" class="form-select">
                        <option value="">Välj plats</option>
                        @foreach(($locations ?? collect()) as $location)
                            <option value="{{ $location->id }}" @selected((string) old('location_id') === (string) $location->id)>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Fri platsbeskrivning</label>
                    <input
                        type="text"
                        name="location_text"
                        class="form-control"
                        value="{{ old('location_text') }}"
                        placeholder="Om platsen inte finns i listan"
                    >
                </div>

                <div class="col-12">
                    <label class="form-label">Bilder (valfritt, högst {{ $maxAttachments }})</label>
                    <div class="facility-report-camera mb-3">
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <button type="button" id="facility-report-camera-start" class="btn btn-outline-primary">
                                <i class="bi bi-camera-video me-2"></i>Starta kamera
                            </button>
                            <button type="button" id="facility-report-camera-capture" class="btn btn-outline-secondary" disabled>
                                <i class="bi bi-camera me-2"></i>Ta bild
                            </button>
                            <button type="button" id="facility-report-camera-clear" class="btn btn-outline-secondary d-none">
                                Rensa bilder
                            </button>
                        </div>

                        <div id="facility-report-camera-status" class="form-text mb-2">
                            Du kan ta flera bilder med kameran eller välja flera filer. Högst {{ $maxAttachments }} bilder.
                        </div>

                        <div id="facility-report-camera-frame" class="facility-report-camera-frame d-none">
                            <video id="facility-report-camera-preview" class="facility-report-camera-media" autoplay playsinline muted></video>
                            <canvas id="facility-report-camera-canvas" class="d-none"></canvas>
                        </div>

                        <div id="facility-report-photo-list" class="facility-report-photo-list mt-2"></div>
                    </div>

                    <label class="form-label fw-semibold">Eller välj befintliga bilder</label>
                    <input
                        type="file"
                        name="attachments[]"
                        id="attachments"
                        class="form-control"
                        accept="image/jpeg,image/png,image/gif,image/webp"
                        multiple
                    >
                    <div class="form-text">JPG, PNG, GIF eller WebP. Högst 10 MB per bild. Max {{ $maxAttachments }} bilder.</div>
                </div>

                <div class="col-12">
                    <label class="form-label">Beskrivning</label>
                    <textarea
                        name="description"
                        class="form-control guide-textarea-xl"
                        rows="10"
                        placeholder="Beskriv vad som hänt, var problemet finns och hur allvarligt det är."
                        required
                    >{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        <div class="page-card guide-side-panel">
            <div class="section-title">Skicka rapport</div>

            <div class="info-item mb-3">
                <div class="fw-semibold mb-1">Rapporten går direkt till admin</div>
                <div class="small-muted">
                    Använd tydlig rubrik, välj rätt kategori och beskriv platsen så konkret som möjligt.
                </div>
            </div>

            <div class="info-item mb-3">
                <div class="small-muted mb-1">Inloggad användare</div>
                <div class="fw-semibold">{{ auth()->user()->name }}</div>
            </div>

            <div class="guide-primary-actions">
                <button type="submit" class="btn btn-primary btn-lg w-100">
                    <i class="bi bi-send-check me-2"></i>Skapa felrapport
                </button>

                <a href="{{ route('guide.dashboard') }}" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-x-circle me-2"></i>Avbryt
                </a>
            </div>
        </div>
    </div>
</form>

<style>
.guide-report-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.7fr) 320px;
    gap: 1rem;
    align-items: start;
}

.guide-side-panel {
    align-self: start;
}

.guide-textarea-xl {
    min-height: 260px !important;
}

.facility-report-camera-frame {
    background: #0f172a;
    border-radius: 1rem;
    overflow: hidden;
}

.facility-report-camera-media {
    display: block;
    width: 100%;
    max-height: 60vh;
    object-fit: contain;
}

.facility-report-photo-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: 0.75rem;
}

.facility-report-photo-list img {
    width: 100%;
    height: 90px;
    object-fit: cover;
    border-radius: 0.75rem;
    border: 1px solid #cbd5e1;
}

@media (max-width: 1100px) {
    .guide-report-layout {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
(() => {
    const maxAttachments = {{ $maxAttachments }};
    const fileInput = document.getElementById('attachments');
    const startButton = document.getElementById('facility-report-camera-start');
    const captureButton = document.getElementById('facility-report-camera-capture');
    const clearButton = document.getElementById('facility-report-camera-clear');
    const status = document.getElementById('facility-report-camera-status');
    const frame = document.getElementById('facility-report-camera-frame');
    const video = document.getElementById('facility-report-camera-preview');
    const canvas = document.getElementById('facility-report-camera-canvas');
    const photoList = document.getElementById('facility-report-photo-list');
    let stream = null;
    let selectedFiles = [];

    const setStatus = (message) => {
        status.textContent = message;
    };

    const stopCamera = () => {
        if (stream) {
            stream.getTracks().forEach((track) => track.stop());
            stream = null;
        }
    };

    const syncFileInput = () => {
        if (typeof DataTransfer === 'undefined') {
            return;
        }

        const transfer = new DataTransfer();
        selectedFiles.forEach((file) => transfer.items.add(file));
        fileInput.files = transfer.files;
        clearButton.classList.toggle('d-none', selectedFiles.length === 0);
        renderPreviews();
    };

    const renderPreviews = () => {
        photoList.innerHTML = '';
        selectedFiles.forEach((file) => {
            const img = document.createElement('img');
            img.alt = file.name;
            img.src = URL.createObjectURL(file);
            photoList.appendChild(img);
        });
    };

    const addFiles = (files) => {
        const incoming = Array.from(files || []);
        if (!incoming.length) {
            return;
        }

        const remaining = maxAttachments - selectedFiles.length;
        if (remaining <= 0) {
            setStatus(`Du kan högst bifoga ${maxAttachments} bilder.`);
            return;
        }

        const accepted = incoming.slice(0, remaining);
        selectedFiles = selectedFiles.concat(accepted);
        syncFileInput();

        if (incoming.length > remaining) {
            setStatus(`Endast ${maxAttachments} bilder sparas. Överskjutande filer hoppades över.`);
            return;
        }

        setStatus(`${selectedFiles.length} bild${selectedFiles.length === 1 ? '' : 'er'} valda.`);
    };

    const startCamera = async () => {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setStatus('Den här webbläsaren stöder inte kamerafunktionen. Välj befintliga bilder i stället.');
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
            captureButton.disabled = false;
            setStatus('Kameran är igång. Tryck på "Ta bild" för varje foto du vill lägga till.');
        } catch (error) {
            setStatus('Kameran kunde inte startas. Kontrollera kamerabehörighet eller välj befintliga bilder.');
        }
    };

    const capturePhoto = () => {
        if (!video.videoWidth || !video.videoHeight) {
            setStatus('Kameran är inte redo ännu.');
            return;
        }

        if (selectedFiles.length >= maxAttachments) {
            setStatus(`Du kan högst bifoga ${maxAttachments} bilder.`);
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
                setStatus('Bilden kunde inte skapas. Välj befintliga bilder i stället.');
                return;
            }

            const file = new File([blob], `felrapport-${selectedFiles.length + 1}.jpg`, { type: 'image/jpeg' });
            addFiles([file]);
            setStatus(`Bild tillagd (${width} x ${height}px). Du kan ta fler bilder.`);
        }, 'image/jpeg', 0.82);
    };

    const clearPhotos = () => {
        selectedFiles = [];
        syncFileInput();
        setStatus('Bilderna rensades. Du kan ta nya eller välja filer.');
    };

    fileInput.addEventListener('change', () => {
        selectedFiles = [];
        addFiles(fileInput.files);
    });

    startButton.addEventListener('click', startCamera);
    captureButton.addEventListener('click', capturePhoto);
    clearButton.addEventListener('click', clearPhotos);
    window.addEventListener('pagehide', stopCamera);
})();
</script>
@endsection
