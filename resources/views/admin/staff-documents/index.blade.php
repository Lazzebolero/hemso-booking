@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Personaldokument</h2>
        <div class="page-subtitle">Ladda upp och hantera dokument för personal, roller och funktioner.</div>
    </div>

    <div class="page-actions">
        <a href="{{ route('admin.staff-documents.create') }}" class="btn btn-primary">
            <i class="bi bi-upload me-2"></i>Nytt dokument
        </a>
    </div>
</div>

<div class="page-card">
    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Titel</th>
                    <th>Målgrupp</th>
                    <th>Aktiv</th>
                    <th>Sortering</th>
                    <th>Fil</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($documents as $document)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $document->title }}</div>
                            @if($document->description)
                                <div class="small-muted">{{ \Illuminate\Support\Str::limit($document->description, 100) }}</div>
                            @endif
                        </td>
                        <td>
                            @if($document->audience_scope === 'all')
                                Alla
                            @elseif($document->audience_scope === 'role')
                                Roll: {{ ucfirst($document->role_slug) }}
                            @elseif($document->audience_scope === 'function')
                                Funktion: {{ $restaurantFunctions[$document->shift_function] ?? ucfirst($document->shift_function) }}
                            @endif
                        </td>
                        <td>{{ $document->is_active ? 'Ja' : 'Nej' }}</td>
                        <td>{{ $document->sort_order }}</td>
                        <td>{{ $document->original_name }}</td>
                        <td class="text-nowrap">
                            <a href="{{ route('admin.staff-documents.edit', $document) }}" class="btn btn-sm btn-outline-secondary">
                                Redigera
                            </a>

                            <form method="POST" action="{{ route('admin.staff-documents.destroy', $document) }}" class="d-inline" onsubmit="return confirm('Ta bort dokumentet?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Ta bort</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Inga dokument uppladdade ännu.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection