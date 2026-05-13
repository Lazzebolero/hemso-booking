@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Systemmeddelanden</h2>
        <div class="page-subtitle">Information som är relevant för din roll.</div>
    </div>
</div>

<div class="page-card">
    <div class="staff-list">
        @forelse($messages as $message)
            <div class="staff-message-card">
                <div class="fw-semibold">{{ $message->title ?? 'Systemmeddelande' }}</div>

                @if(!empty($message->starts_at) || !empty($message->created_at))
                    <div class="small-muted mb-2">
                        {{ \Carbon\Carbon::parse($message->starts_at ?? $message->created_at)->format('Y-m-d H:i') }}
                    </div>
                @endif

                <div>{{ $message->body ?? '' }}</div>
            </div>
        @empty
            <div class="small-muted">Inga aktuella systemmeddelanden.</div>
        @endforelse
    </div>
</div>

<style>
.staff-list {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
}

.staff-message-card {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 0.95rem;
    background: #f8fafc;
}
</style>
@endsection