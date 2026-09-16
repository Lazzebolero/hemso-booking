@extends('layouts.app')

@section('content')
@php
    $prefix = \App\Support\ActiveRole::routePrefix();
    $isAdmin = session('active_role') === \App\Support\Roles::ADMIN;
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Guide på språk</h2>
        <div class="page-subtitle">
            Översikt över vilka språk varje guide kan guida på.
            @if($isAdmin)
                Språken redigeras under Användare.
            @endif
        </div>
    </div>

    <div class="page-actions">
        @if($isAdmin)
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-people me-2"></i>Användare
            </a>
        @endif
    </div>
</div>

<div class="page-card">
    @if($guides->isEmpty())
        <div class="text-center muted py-4">
            Inga guider hittades.
        </div>
    @else
        <div class="table-responsive-modern">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Guide</th>
                        @foreach($languages as $language)
                            <th class="text-center" style="width: 90px;">{{ strtoupper((string) ($language->code ?? '')) }}</th>
                        @endforeach
                        <th style="width: 140px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($guides as $guide)
                        @php
                            $guideLanguageIds = $guide->guideLanguages
                                ->pluck('id')
                                ->map(fn ($id) => (int) $id)
                                ->all();
                        @endphp

                        <tr>
                            <td class="fw-semibold">{{ $guide->name }}</td>

                            @foreach($languages as $language)
                                <td class="text-center">
                                    @if(in_array((int) $language->id, $guideLanguageIds, true))
                                        <span class="badge-soft badge-soft-success">Ja</span>
                                    @else
                                        <span class="small-muted">—</span>
                                    @endif
                                </td>
                            @endforeach

                            <td>
                                @if($isAdmin)
                                    <a href="{{ route('admin.users.edit', $guide) }}" class="btn btn-sm btn-outline-secondary">
                                        Redigera
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
