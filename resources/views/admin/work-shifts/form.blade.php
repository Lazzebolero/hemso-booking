<div class="page-card">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Användare</label>
            <select name="user_id" class="form-select" required>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected((string) old('user_id', $workShift->user_id) === (string) $user->id)>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label">Datum</label>
            <input
                type="date"
                name="shift_date"
                class="form-control"
                value="{{ old('shift_date', optional($workShift->shift_date)->format('Y-m-d')) }}"
                required
            >
        </div>

        <div class="col-md-3">
            <label class="form-label">Roll i passet</label>
            <select name="shift_role" id="shift_role" class="form-select" required>
                @foreach($shiftRoles as $key => $label)
                    <option value="{{ $key }}" @selected(old('shift_role', $workShift->shift_role) === $key)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label">Starttid</label>
            <input
                type="time"
                name="start_time"
                class="form-control"
                value="{{ old('start_time', $workShift->start_time ? substr($workShift->start_time, 0, 5) : '') }}"
                required
            >
        </div>

        <div class="col-md-3">
            <label class="form-label">Sluttid</label>
            <input
                type="time"
                name="end_time"
                class="form-control"
                value="{{ old('end_time', $workShift->end_time ? substr($workShift->end_time, 0, 5) : '') }}"
            >
        </div>

        <div class="col-md-3" id="shift_function_wrapper">
            <label class="form-label">Funktion</label>
            <select name="shift_function" id="shift_function" class="form-select">
                <option value="">Ingen funktion</option>
                @foreach(\App\Models\WorkShift::restaurantFunctions() as $key => $label)
                    <option value="{{ $key }}" @selected(old('shift_function', $workShift->shift_function) === $key)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            <div class="form-text">Används främst för restaurangpass.</div>
        </div>

        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select" required>
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}" @selected(old('status', $workShift->status) === $key)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-12">
            <label class="form-label">Anteckning</label>
            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $workShift->notes) }}</textarea>
        </div>
    </div>
</div>

<script>
    function toggleShiftFunctionField() {
        const roleField = document.getElementById('shift_role');
        const wrapper = document.getElementById('shift_function_wrapper');
        const functionField = document.getElementById('shift_function');

        if (!roleField || !wrapper || !functionField) {
            return;
        }

        const isRestaurant = roleField.value === 'restaurant';

        wrapper.style.display = isRestaurant ? '' : 'none';

        if (!isRestaurant) {
            functionField.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        toggleShiftFunctionField();

        const roleField = document.getElementById('shift_role');
        if (roleField) {
            roleField.addEventListener('change', toggleShiftFunctionField);
        }
    });
</script>