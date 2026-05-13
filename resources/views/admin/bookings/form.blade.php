@csrf

<div class="page-card">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="section-title">Bokningsinformation</div>

            <div class="mb-3">
                <label class="form-label">Bokningsnamn</label>
                <input
                    type="text"
                    name="booking_name"
                    class="form-control"
                    value="{{ old('booking_name', $booking->booking_name ?? '') }}"
                >
                <div class="small muted mt-2">
                    Lämna tomt för att låta systemet skapa ett unikt boknings-ID automatiskt.
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Tur</label>
                <select name="tour_id" class="form-select" required>
                    <option value="">Välj tur</option>
                    @foreach($tours as $tour)
                        <option value="{{ $tour->id }}"
                            @selected(old('tour_id', $booking->tour_id ?? '') == $tour->id)>
                            {{ $tour->tour_date ? \Carbon\Carbon::parse($tour->tour_date)->format('Y-m-d') : '-' }}
                            {{ !empty($tour->start_time) ? substr($tour->start_time, 0, 5) : '' }}
                            - {{ $tour->title }}
                            @if($tour->tourType)
                                ({{ $tour->tourType->name }})
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Kontaktperson</label>
                    <input
                        type="text"
                        name="contact_name"
                        class="form-control"
                        value="{{ old('contact_name', $booking->contact_name ?? '') }}"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">Telefon</label>
                    <input
                        type="text"
                        name="phone"
                        class="form-control"
                        value="{{ old('phone', $booking->phone ?? '') }}"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">E-post</label>
                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        value="{{ old('email', $booking->email ?? '') }}"
                        placeholder="namn@exempel.se"
                    >
                    <div class="small muted mt-2">
                        Om e-postadress anges kan systemet skicka bokningsbekräftelse, uppdateringar och avbokningsmeddelanden.
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="section-title">Antal deltagare</div>

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Män</label>
                    <input
                        type="number"
                        min="0"
                        name="men_count"
                        class="form-control"
                        value="{{ old('men_count', $booking->men_count ?? 0) }}"
                        required
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label">Kvinnor</label>
                    <input
                        type="number"
                        min="0"
                        name="women_count"
                        class="form-control"
                        value="{{ old('women_count', $booking->women_count ?? 0) }}"
                        required
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label">Ungdomar</label>
                    <input
                        type="number"
                        min="0"
                        name="youth_count"
                        class="form-control"
                        value="{{ old('youth_count', $booking->youth_count ?? 0) }}"
                        required
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label">Barn</label>
                    <input
                        type="number"
                        min="0"
                        name="child_count"
                        class="form-control"
                        value="{{ old('child_count', $booking->child_count ?? 0) }}"
                        required
                    >
                </div>
            </div>

            <hr class="my-4">

            <div class="section-title">Språk</div>

            @php
                $selectedLanguages = old(
                    'languages',
                    isset($booking) && method_exists($booking, 'languages')
                        ? $booking->languages->pluck('id')->all()
                        : []
                );

                $defaultLanguageId = \App\Models\Language::where('is_default', true)->value('id');

                if (empty($selectedLanguages) && $defaultLanguageId) {
                    $selectedLanguages = [$defaultLanguageId];
                }
            @endphp

            <div class="row g-2">
                @forelse($languages as $language)
                    <div class="col-md-4">
                        <div class="form-check">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="languages[]"
                                value="{{ $language->id }}"
                                id="language_{{ $language->id }}"
                                @checked(in_array($language->id, $selectedLanguages))
                            >
                            <label class="form-check-label" for="language_{{ $language->id }}">
                                {{ $language->name }}
                                <span class="small muted">({{ strtoupper($language->code) }})</span>
                            </label>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="muted">Inga aktiva språk finns upplagda.</div>
                    </div>
                @endforelse
            </div>

            <div class="mt-4">
                <label class="form-label">Anteckningar</label>
                <textarea name="notes" class="form-control" rows="4">{{ old('notes', $booking->notes ?? '') }}</textarea>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="page-card h-100">
                <div class="section-title">Inställningar</div>

                <div class="alert alert-light border small">
                    <strong>Mailflöde:</strong><br>
                    Bekräftad eller uppdaterad bokning kan skicka mail om e-postadress finns.<br>
                    Om status sätts till <strong>Avbokad</strong> skickas avbokningsmail.
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        @foreach([
                            'preliminary' => 'Preliminär',
                            'confirmed' => 'Bekräftad',
                            'cancelled' => 'Avbokad',
                            'completed' => 'Slutförd'
                        ] as $statusKey => $statusLabel)
                            <option value="{{ $statusKey }}" @selected(old('status', $booking->status ?? 'preliminary') === $statusKey)>
                                {{ $statusLabel }}
                            </option>
                        @endforeach
                    </select>
                    <div class="small muted mt-2">
                        Välj <strong>Avbokad</strong> för att avboka bokningen utan att radera den.
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Ankomststatus</label>
                    <select name="arrival_status" class="form-select">
                        <option value="">Ej satt</option>
                        @foreach([
                            'booked' => 'Bokad',
                            'arrived' => 'Anlänt',
                            'no_show' => 'No-show',
                            'late_cancel' => 'Sen avbokning'
                        ] as $arrivalKey => $arrivalLabel)
                            <option value="{{ $arrivalKey }}" @selected(old('arrival_status', $booking->arrival_status ?? '') === $arrivalKey)>
                                {{ $arrivalLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-check mb-4">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        name="is_walk_in"
                        value="1"
                        id="is_walk_in"
                        @checked(old('is_walk_in', $booking->is_walk_in ?? false))
                    >
                    <label class="form-check-label" for="is_walk_in">
                        Registrera som sista-minuten-besök / walk-in
                    </label>
                </div>

                <button class="btn btn-primary w-100">
                    <i class="bi bi-save me-2"></i>Spara bokning
                </button>
            </div>
        </div>
    </div>
</div>