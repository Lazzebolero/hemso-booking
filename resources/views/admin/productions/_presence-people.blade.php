<div class="mb-3">
    <div class="small-muted mb-1">{{ $title }} ({{ $people->count() }})</div>
    @forelse($people as $person)
        <div class="d-flex justify-content-between gap-2 py-1 border-bottom">
            <span>{{ $person->name }}</span>
            <span class="small-muted">{{ $person->kindLabel() }}</span>
        </div>
    @empty
        <div class="small-muted">{{ $empty }}</div>
    @endforelse
</div>
