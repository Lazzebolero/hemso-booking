<div class="form-layout">
    <div class="page-card">
        <div class="section-title">Grunduppgifter</div>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Rubrik</label>
                <input
                    type="text"
                    name="title"
                    class="form-control"
                    value="{{ old('title', $report->title ?? '') }}"
                    required
                >
            </div>

            <div class="col-md-6">
                <label class="form-label">Plats</label>
                <select name="location_id" class="form-select">
                    <option value="">Välj plats</option>
                    @foreach(($locations ?? collect()) as $location)
                        <option value="{{ $location->id }}"
                            {{ (string) old('location_id', $report->location_id ?? '') === (string) $location->id ? 'selected' : '' }}>
                            {{ $location->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">Fri platsbeskrivning</label>
                <input
                    type="text"
                    name="location_text"
                    class="form-control"
                    value="{{ old('location_text', $report->location_text ?? '') }}"
                    placeholder="Använd om platsen inte finns i listan"
                >
            </div>

            <div class="col-md-6">
                <label class="form-label">Kategori</label>
                <select name="category_id" class="form-select" required>
                    <option value="">Välj kategori</option>
                    @foreach(($categories ?? collect()) as $category)
                        <option value="{{ $category->id }}"
                            {{ (string) old('category_id', $report->category_id ?? '') === (string) $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Klassning / prioritet</label>
                <select name="priority_id" class="form-select" required>
                    <option value="">Välj klassning</option>
                    @foreach(($priorities ?? collect()) as $priority)
                        <option value="{{ $priority->id }}"
                            {{ (string) old('priority_id', $report->priority_id ?? '') === (string) $priority->id ? 'selected' : '' }}>
                            {{ $priority->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Status</label>
                <select name="status_id" class="form-select" required>
                    <option value="">Välj status</option>
                    @foreach(($statuses ?? collect()) as $status)
                        <option value="{{ $status->id }}"
                            {{ (string) old('status_id', $report->status_id ?? '') === (string) $status->id ? 'selected' : '' }}>
                            {{ $status->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Tilldelad till</label>
                <select name="assigned_to" class="form-select">
                    <option value="">Ingen tilldelning</option>
                    @foreach(($users ?? collect()) as $user)
                        <option value="{{ $user->id }}"
                            {{ (string) old('assigned_to', $report->assigned_to ?? '') === (string) $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">Beskrivning</label>
                <textarea
                    name="description"
                    class="form-control"
                    rows="10"
                    required
                >{{ old('description', $report->description ?? '') }}</textarea>
            </div>
        </div>
    </div>

    <div class="form-side-box">
        <div class="section-title">Spara</div>

        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-check2-circle me-2"></i>Spara ändringar
        </button>
    </div>
</div>