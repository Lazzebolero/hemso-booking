<div class="page-card">
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label">Titel</label>
            <input type="text" name="title" class="form-control" value="{{ old('title', $document->title) }}" required>
        </div>

        <div class="col-md-4">
            <label class="form-label">Sortering</label>
            <input type="number" name="sort_order" min="0" class="form-control" value="{{ old('sort_order', $document->sort_order ?? 0) }}">
        </div>

        <div class="col-12">
            <label class="form-label">Beskrivning</label>
            <textarea name="description" class="form-control" rows="4">{{ old('description', $document->description) }}</textarea>
        </div>

        <div class="col-md-4">
            <label class="form-label">Målgrupp</label>
            <select name="audience_scope" id="audience_scope" class="form-select" required>
                @foreach($audienceScopes as $key => $label)
                    <option value="{{ $key }}" @selected(old('audience_scope', $document->audience_scope) === $key)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4" id="role_slug_wrapper">
            <label class="form-label">Roll</label>
            <select name="role_slug" class="form-select">
                <option value="">Välj roll</option>
                @foreach($roleOptions as $key => $label)
                    <option value="{{ $key }}" @selected(old('role_slug', $document->role_slug) === $key)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4" id="shift_function_wrapper">
            <label class="form-label">Funktion</label>
            <select name="shift_function" class="form-select">
                <option value="">Välj funktion</option>
                @foreach($restaurantFunctions as $key => $label)
                    <option value="{{ $key }}" @selected(old('shift_function', $document->shift_function) === $key)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-8">
            <label class="form-label">Fil</label>
            <input type="file" name="document_file" class="form-control" {{ empty($document->id) ? 'required' : '' }}>
            @if(!empty($document->original_name))
                <div class="form-text">Nuvarande fil: {{ $document->original_name }}</div>
            @endif
        </div>

        <div class="col-md-4 d-flex align-items-end">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $document->is_active ?? true))>
                <label class="form-check-label" for="is_active">
                    Aktivt dokument
                </label>
            </div>
        </div>

        <div class="col-12">
            <button class="btn btn-primary">
                <i class="bi bi-save me-2"></i>Spara dokument
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const audience = document.getElementById('audience_scope');
    const roleWrap = document.getElementById('role_slug_wrapper');
    const functionWrap = document.getElementById('shift_function_wrapper');

    function updateVisibility() {
        const value = audience ? audience.value : 'all';

        if (roleWrap) {
            roleWrap.style.display = value === 'role' ? '' : 'none';
        }

        if (functionWrap) {
            functionWrap.style.display = value === 'function' ? '' : 'none';
        }
    }

    if (audience) {
        audience.addEventListener('change', updateVisibility);
        updateVisibility();
    }
});
</script>