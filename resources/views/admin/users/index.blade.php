@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Användare</h2>
        <div class="page-subtitle">Hantera administratörer, värdar, guider och restaurangpersonal.</div>
    </div>

    <div class="page-actions">
        @if($includeInactive)
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                Visa bara aktiva
            </a>
        @else
            <a href="{{ route('admin.users.index', ['inactive' => 1]) }}" class="btn btn-outline-secondary">
                Visa även inaktiva
            </a>
        @endif
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus me-2"></i>Ny användare
        </a>
    </div>
</div>

<div class="page-card compact-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="section-title mb-0">Lista</div>
        <div class="small-muted">
            {{ method_exists($users, 'total') ? $users->total() : count($users) }}
            {{ $includeInactive ? 'användare' : 'aktiva användare' }}
        </div>
    </div>

    <div class="table-responsive-modern">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Namn</th>
                    <th>E-post</th>
                    <th style="width:120px;">Telefon</th>
                    <th>Roller</th>
                    <th style="width: 140px;">Guide språk</th>
                    <th style="width:100px;">Status</th>
                    <th style="width:120px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    @php
                        $roleNames = $user->roles?->pluck('name')->filter()->values() ?? collect();
                        $roleSlugs = $user->roles?->pluck('slug')->filter()->values() ?? collect();
                    @endphp

                    <tr>
                        <td class="fw-semibold">{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->phone ?: '-' }}</td>

                        <td>
                            @if($roleNames->isNotEmpty())
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($roleNames as $roleName)
                                        <span class="badge-soft badge-soft-secondary">{{ $roleName }}</span>
                                    @endforeach
                                </div>
                            @else
                                <span class="small-muted">Inga roller</span>
                            @endif
                        </td>

                        <td>
                            @if($roleSlugs->contains('guide'))
                                {{ $user->guideLanguageLabel() }}
                            @else
                                <span class="small-muted">—</span>
                            @endif
                        </td>

                        <td>
                            @if($user->is_active)
                                <span class="badge-soft badge-soft-success">Aktiv</span>
                            @else
                                <span class="badge-soft badge-soft-danger">Inaktiv</span>
                            @endif
                        </td>

                        <td>
                            <a href="{{ route('admin.users.edit', $user) }}"
                               class="btn btn-sm btn-outline-secondary">
                                Redigera
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center muted py-4">
                            {{ $includeInactive ? 'Inga användare hittades.' : 'Inga aktiva användare hittades.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($users, 'links'))
        <div class="mt-3">
            {{ $users->links() }}
        </div>
    @endif
</div>
@endsection