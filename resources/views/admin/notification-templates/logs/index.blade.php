@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="mb-1">Notifieringslogg</h2>
        <div class="muted">Visar skickade och felade e-postnotifieringar.</div>
    </div>
</div>

<div class="page-card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Typ</th>
                    <th>Mottagare</th>
                    <th>Ämne</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ $log->template_key }}</td>
                        <td>{{ $log->recipient_email }}</td>
                        <td>{{ $log->subject }}</td>
                        <td>{{ $log->status }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.notification-logs.resend', $log) }}">
                                @csrf
                                <button class="btn btn-outline-secondary btn-sm">Skicka om</button>
                            </form>
                        </td>
                    </tr>
                    @if($log->error_message)
                        <tr>
                            <td colspan="6" class="text-danger small">
                                Fel: {{ $log->error_message }}
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">Inga loggar hittades.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $logs->links() }}
    </div>
</div>
@endsection