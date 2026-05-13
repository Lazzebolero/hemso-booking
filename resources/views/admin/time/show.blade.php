@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">
    <div>
        <h1 class="h3 mb-1">
            <i class="bi bi-clock-history me-2"></i>Tidrapport
        </h1>

        <div class="text-muted">
            Granska och korrigera arbetstid
        </div>
    </div>

    <a href="{{ route('admin.time.index') }}"
       class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Tillbaka
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(!empty($deviations))
    <div class="alert alert-warning border-0 shadow-sm mb-4">
        <div class="fw-semibold mb-2">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>Avvikelser
        </div>

        <div class="d-flex flex-column gap-2">
            @foreach($deviations as $deviation)
                <div>
                    <span class="fw-semibold">{{ $deviation['label'] }}</span>
                    <span class="text-muted">— {{ $deviation['description'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="row g-4">

    <div class="col-lg-8">

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">

                <div class="row g-3">

                    <div class="col-md-6">
                        <div class="border rounded-3 p-3">
                            <div class="text-muted small mb-1">Personal</div>
                            <div class="fw-semibold">
                                {{ $entry->user->name ?? 'Okänd användare' }}
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="border rounded-3 p-3">
                            <div class="text-muted small mb-1">Status</div>

                            <div class="d-flex flex-column gap-2">
                                <div>
                                    <span class="badge {{ $entry->status_badge_class }}">
                                        {{ $entry->status_label }}
                                    </span>
                                </div>

                                @include('admin.time.partials.deviation-badges', ['deviations' => $deviations])
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="border rounded-3 p-3">
                            <div class="text-muted small mb-1">Original in</div>
                            <div class="fw-semibold">
                                {{ optional($entry->clock_in_at_original)->format('Y-m-d H:i') ?? '-' }}
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="border rounded-3 p-3">
                            <div class="text-muted small mb-1">Original ut</div>
                            <div class="fw-semibold">
                                {{ optional($entry->clock_out_at_original)->format('Y-m-d H:i') ?? '-' }}
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="border rounded-3 p-3">
                            <div class="text-muted small mb-1">Rapporterad tid</div>
                            <div class="fw-semibold">
                                {{ optional($entry->start_at)->format('H:i') ?? '-' }}
                                -
                                {{ optional($entry->end_at)->format('H:i') ?? '-' }}
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="border rounded-3 p-3">
                            <div class="text-muted small mb-1">Arbetad tid</div>
                            <div class="fw-semibold">
                                {{ $entry->worked_hours_formatted }}
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">

                <h2 class="h5 mb-4">
                    Korrigera tid
                </h2>

                <form method="POST"
                      action="{{ route('admin.time.correct', $entry) }}">

                    @csrf
                    @method('PATCH')

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Start</label>

                            <input type="datetime-local"
                                   name="start_at"
                                   class="form-control"
                                   required
                                   value="{{ old('start_at', optional($entry->start_at)->format('Y-m-d\TH:i')) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Slut</label>

                            <input type="datetime-local"
                                   name="end_at"
                                   class="form-control"
                                   value="{{ old('end_at', optional($entry->end_at)->format('Y-m-d\TH:i')) }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Rast</label>

                            <input type="number"
                                   name="break_minutes"
                                   class="form-control"
                                   value="{{ old('break_minutes', $entry->break_minutes) }}">
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Admin-kommentar</label>

                            <input type="text"
                                   name="admin_comment"
                                   class="form-control"
                                   value="{{ old('admin_comment', $entry->admin_comment) }}">
                        </div>

                    </div>

                    <div class="d-flex justify-content-end align-items-center mt-4 flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>Spara korrigering
                        </button>
                    </div>

                </form>

                @if($entry->status !== \App\Models\TimeEntry::STATUS_APPROVED)
                    <div class="border-top mt-4 pt-4">
                        <form method="POST"
                              action="{{ route('admin.time.approve', $entry) }}">
                            @csrf
                            @method('PATCH')

                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check2-circle me-1"></i>Godkänn tid
                            </button>
                        </form>
                    </div>
                @else
                    <div class="alert alert-success border-0 mt-4 mb-0">
                        <i class="bi bi-check2-circle me-1"></i>Tiden är godkänd.
                    </div>
                @endif

            </div>
        </div>

    </div>

    <div class="col-lg-4">

        <div class="card border-0 shadow-sm">
            <div class="card-body">

                <h2 class="h5 mb-4">
                    Ändringslogg
                </h2>

                @forelse($entry->audits as $audit)

                    <div class="border-bottom pb-3 mb-3">

                        <div class="d-flex justify-content-between gap-2 mb-2">
                            <div class="fw-semibold">
                                {{ $audit->field }}
                            </div>

                            <div class="small text-muted">
                                {{ optional($audit->created_at)->format('Y-m-d H:i') }}
                            </div>
                        </div>

                        <div class="small">
                            <div>
                                <strong>Från:</strong>
                                {{ $audit->old_value ?: '-' }}
                            </div>

                            <div>
                                <strong>Till:</strong>
                                {{ $audit->new_value ?: '-' }}
                            </div>
                        </div>

                        <div class="small text-muted mt-2">
                            {{ $audit->changedBy->name ?? 'System' }}
                        </div>

                    </div>

                @empty

                    <div class="text-muted">
                        Ingen ändringslogg finns ännu.
                    </div>

                @endforelse

            </div>
        </div>

    </div>

</div>

@endsection
