@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Meddelanden</h2>
        <div class="page-subtitle">Gruppchattar och konversationer.</div>
    </div>
</div>

<div class="page-card">
    <div class="staff-list">
        @forelse($threads as $thread)
            <div class="staff-message-card">
                <div class="fw-semibold">{{ $thread->title ?? $thread->name ?? 'Konversation' }}</div>

                @if(isset($thread->unread_count) && $thread->unread_count > 0)
                    <div class="small-muted mb-2">Olästa: {{ $thread->unread_count }}</div>
                @endif

                @if(!empty($thread->updated_at))
                    <div class="small-muted">
                        Senast uppdaterad: {{ \Carbon\Carbon::parse($thread->updated_at)->format('Y-m-d H:i') }}
                    </div>
                @endif
            </div>
        @empty
            <div class="small-muted">Inga meddelanden hittades.</div>
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