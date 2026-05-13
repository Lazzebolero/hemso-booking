@extends(session('active_role') === \App\Support\Roles::GUIDE ? 'layouts.guide' : 'layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">{{ $document->title }}</h2>
        <div class="page-subtitle">{{ $document->original_name }}</div>
    </div>

    <div class="page-actions d-flex gap-2 flex-wrap">
        <a href="{{ route('staff.documents.index') }}" class="btn btn-outline-secondary">
            Tillbaka
        </a>

        <a href="{{ route('staff.documents.download', $document) }}" class="btn btn-primary">
            Ladda ner
        </a>
    </div>
</div>

<div class="page-card">
    @if($document->description)
        <div class="mb-3">{{ nl2br(e($document->description)) }}</div>
    @endif

    <div class="small-muted mb-3">
        Filtyp: {{ $document->mime_type ?: 'Okänd' }}
    </div>

    @php
        $isPdf = ($document->mime_type === 'application/pdf')
            || \Illuminate\Support\Str::endsWith(strtolower($document->original_name), '.pdf');
    @endphp

    @if($isPdf && Route::has('staff.documents.preview'))
        <div class="pdf-preview-wrap">
            <iframe
                src="{{ route('staff.documents.preview', $document) }}"
                class="pdf-preview-frame"
                title="PDF-förhandsvisning"
            ></iframe>
        </div>

        <div class="form-text mt-2">
            Om förhandsvisningen inte visas i din mobil eller webbläsare, använd knappen “Ladda ner”.
        </div>
    @else
        <div class="document-preview-fallback">
            Förhandsvisning finns inte för denna filtyp. Ladda ner dokumentet för att öppna det.
        </div>
    @endif
</div>

<style>
.pdf-preview-wrap {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    background: #f8fafc;
}

.pdf-preview-frame {
    width: 100%;
    height: 75vh;
    min-height: 520px;
    border: 0;
    display: block;
    background: #fff;
}

.document-preview-fallback {
    border: 1px dashed #cbd5e1;
    border-radius: 12px;
    padding: 1rem;
    color: #64748b;
    background: #f8fafc;
}

@media (max-width: 768px) {
    .pdf-preview-frame {
        height: 68vh;
        min-height: 420px;
    }
}
</style>
@endsection