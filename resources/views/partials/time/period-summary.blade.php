@props(['from', 'to', 'totalFormatted', 'filter', 'guide' => false])

<div class="row g-3 mb-4">
    <div class="col-md-4">
        @if($guide)
            <div class="page-card h-100">
                <div class="small-muted mb-1">Vald period</div>
                <div class="fw-semibold">{{ $from->format('Y-m-d') }} – {{ $to->format('Y-m-d') }}</div>
            </div>
        @else
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Vald period</div>
                    <div class="fw-semibold">{{ $from->format('Y-m-d') }} – {{ $to->format('Y-m-d') }}</div>
                </div>
            </div>
        @endif
    </div>

    <div class="col-md-4">
        @if($guide)
            <div class="page-card h-100">
                <div class="small-muted mb-1">Totalt arbetad tid</div>
                <div class="fs-4 fw-bold">{{ $totalFormatted }}</div>
            </div>
        @else
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Totalt arbetad tid</div>
                    <div class="fs-4 fw-bold">{{ $totalFormatted }}</div>
                </div>
            </div>
        @endif
    </div>

    <div class="col-md-4">
        @if($guide)
            <div class="page-card h-100">
                <div class="small-muted mb-2">Snabbfilter</div>
                <div class="toolbar-inline">
                    @include('partials.time.filter-buttons', ['filter' => $filter])
                </div>
            </div>
        @else
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-2">Snabbfilter</div>
                    <div class="d-flex gap-2 flex-wrap">
                        @include('partials.time.filter-buttons', ['filter' => $filter])
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
