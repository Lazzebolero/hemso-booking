<form method="GET" action="{{ route('admin.work-shifts.template') }}" class="d-flex flex-wrap gap-2 align-items-end">
    <div>
        <label class="form-label mb-1" for="{{ $fromId }}">Från</label>
        <input
            id="{{ $fromId }}"
            class="form-control"
            type="date"
            name="from"
            value="{{ $templateFrom }}"
            required
        >
    </div>
    <div>
        <label class="form-label mb-1" for="{{ $toId }}">Till</label>
        <input
            id="{{ $toId }}"
            class="form-control"
            type="date"
            name="to"
            value="{{ $templateTo }}"
            required
        >
    </div>
    <button class="btn btn-outline-secondary" type="submit">Ladda ner mall</button>
</form>
