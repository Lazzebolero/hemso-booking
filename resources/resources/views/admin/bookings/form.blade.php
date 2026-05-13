@csrf
<div class="page-card">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="section-title">Bokningsuppgifter</div>

            <div class="mb-3">
                <label class="form-label">Tur</label>
                <select name="tour_id" id="tour_id" class="form-select" required>
                    @foreach($tours as $tour)
                        <option value="{{ $tour->id }}" data-max="{{ $tour->max_participants }}" data-booked="{{ $tour->booked_count }}" @selected(old('tour_id', $booking->tour_id ?? '') == $tour->id)>
                            {{ $tour->tour_date }} {{ $tour->start_time }} - {{ $tour->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Bokningsnamn</label>
                    <input type="text" name="booking_name" class="form-control" value="{{ old('booking_name', $booking->booking_name ?? '') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Kontaktperson</label>
                    <input type="text" name="contact_name" class="form-control" value="{{ old('contact_name', $booking->contact_name ?? '') }}">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Telefon</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $booking->phone ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">E-post</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $booking->email ?? '') }}">
                </div>
            </div>

            <div class="section-title mt-4">Deltagare</div>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Män</label>
                    <input type="number" min="0" name="men_count" class="form-control booked-field" value="{{ old('men_count', $booking->men_count ?? 0) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kvinnor</label>
                    <input type="number" min="0" name="women_count" class="form-control booked-field" value="{{ old('women_count', $booking->women_count ?? 0) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ungdomar</label>
                    <input type="number" min="0" name="youth_count" class="form-control booked-field" value="{{ old('youth_count', $booking->youth_count ?? 0) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Barn</label>
                    <input type="number" min="0" name="child_count" class="form-control booked-field" value="{{ old('child_count', $booking->child_count ?? 0) }}">
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Kommentar</label>
                    <textarea name="notes" class="form-control" rows="4">{{ old('notes', $booking->notes ?? '') }}</textarea>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Bokningsstatus</label>
                    <select name="status" class="form-select">
                        @foreach(['preliminary', 'confirmed', 'cancelled', 'completed'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $booking->status ?? 'confirmed') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Ankomststatus</label>
                    <select name="arrival_status" class="form-select">
                        @foreach(['booked', 'arrived', 'no_show', 'late_cancel'] as $status)
                            <option value="{{ $status }}" @selected(old('arrival_status', $booking->arrival_status ?? 'booked') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="page-card h-100">
                <div class="section-title">Sammanfattning</div>

                <div class="stats-card mb-3">
                    <div class="stats-label">Bokade totalt</div>
                    <div class="stats-value" id="booked_total_preview">0</div>
                </div>

                <div id="capacity_info" class="alert alert-info mb-3">Lediga platser visas här.</div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" value="1" id="is_walk_in" name="is_walk_in" @checked(old('is_walk_in', $booking->is_walk_in ?? false))>
                    <label class="form-check-label" for="is_walk_in">Sista-minuten-besökare (walk-in)</label>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-save me-2"></i>Spara bokning
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const bookedFields = document.querySelectorAll('.booked-field');
    const bookedPreview = document.getElementById('booked_total_preview');
    const tourSelect = document.getElementById('tour_id');
    const capacityInfo = document.getElementById('capacity_info');

    function sumFields(fields) {
        let total = 0;
        fields.forEach(field => total += parseInt(field.value || 0, 10));
        return total;
    }

    function updatePreview() {
        const bookedTotal = sumFields(bookedFields);
        bookedPreview.textContent = bookedTotal;

        const selected = tourSelect.options[tourSelect.selectedIndex];
        const max = parseInt(selected?.dataset.max || 0, 10);
        const booked = parseInt(selected?.dataset.booked || 0, 10);
        const remaining = max - booked;

        if (bookedTotal > remaining) {
            capacityInfo.className = 'alert alert-warning';
            capacityInfo.textContent = `Turen är full eller nästan full. Bokningen kan hamna i väntelista. Lediga platser just nu: ${remaining}.`;
        } else {
            capacityInfo.className = 'alert alert-info';
            capacityInfo.textContent = `Lediga platser kvar: ${remaining}. Bokningen använder ${bookedTotal}.`;
        }
    }

    bookedFields.forEach(field => field.addEventListener('input', updatePreview));
    tourSelect?.addEventListener('change', updatePreview);
    updatePreview();
});
</script>