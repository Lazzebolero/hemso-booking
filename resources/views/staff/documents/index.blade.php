@extends(session('active_role') === \App\Support\Roles::GUIDE ? 'layouts.guide' : 'layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Dokument</h2>
        <div class="page-subtitle">Dokument som gäller för dig och din roll/funktion.</div>
    </div>
</div>

<div class="staff-doc-grid">
    @forelse($documents as $document)
        <div class="page-card">
            <div class="section-title mb-2">{{ $document->title }}</div>

            @if($document->description)
                <div class="small-muted mb-3">{{ $document->description }}</div>
            @endif

            <div class="small-muted mb-3">{{ $document->original_name }}</div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('staff.documents.show', $document) }}" class="btn btn-outline-secondary btn-sm">
                    Läs
                </a>
                <a href="{{ route('staff.documents.download', $document) }}" class="btn btn-primary btn-sm">
                    Ladda ner
                </a>
            </div>
        </div>
    @empty
        <div class="page-card">
            <div class="small-muted">Inga dokument tillgängliga just nu.</div>
        </div>
    @endforelse
</div>

<style>
.staff-doc-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 1rem;
}
</style>
@endsection