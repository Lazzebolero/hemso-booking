@props([
    'action',
    'fromDate',
    'toDate',
    'resetUrl',
])

<form method="GET" action="{{ $action }}" class="row g-3 align-items-end">
    <div class="col-md-3">
        <label class="form-label">Från datum</label>
        <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Till datum</label>
        <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
    </div>
    <div class="col-md-6">
        <button type="submit" class="btn btn-primary me-2">
            <i class="bi bi-funnel me-1"></i>Visa
        </button>
        <a href="{{ $resetUrl }}" class="btn btn-outline-secondary">Idag</a>
    </div>
</form>
