<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $bookingPage->page_title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            margin: 0;
            background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
            font-family: 'Figtree', Arial, sans-serif;
            color: #0f172a;
        }

        .public-wrap {
            max-width: 820px;
            margin: 32px auto;
            padding: 0 16px;
        }

        .public-card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.10);
            padding: 28px;
            border: 1px solid rgba(255,255,255,0.8);
        }

        .public-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .public-logo {
            max-width: 180px;
            max-height: 80px;
            margin: 0 auto 18px;
            display: block;
        }

        .public-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 8px;
            line-height: 1.15;
        }

        .public-subtitle {
            color: #475569;
            font-size: 1rem;
        }

        .tour-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 16px;
            margin-bottom: 22px;
        }

        .tour-box strong {
            display: block;
            margin-bottom: 6px;
            font-size: 1.08rem;
        }

        .price-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 18px;
            padding: 16px;
            margin-bottom: 22px;
        }

        .price-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 10px;
        }

        .price-item {
            background: rgba(255,255,255,0.8);
            border-radius: 14px;
            padding: 12px;
            text-align: center;
        }

        .price-item-label {
            font-size: 0.9rem;
            color: #475569;
            margin-bottom: 6px;
        }

        .price-item-value {
            font-size: 1.1rem;
            font-weight: 700;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .public-form {
            display: grid;
            gap: 18px;
        }

        .field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .count-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .form-block label {
            display: block;
            font-size: 0.92rem;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .form-block input,
        .form-block textarea {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            padding: 12px 14px;
            font-size: 1rem;
            background: #fff;
        }

        .form-block textarea {
            min-height: 110px;
            resize: vertical;
        }

        .terms-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 16px;
            font-size: 0.95rem;
            color: #334155;
        }

        .terms-check {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 12px;
        }

        .terms-check input {
            margin-top: 3px;
        }

        .public-btn {
            background: linear-gradient(135deg, #38bdf8, #2563eb);
            color: #fff;
            border: none;
            border-radius: 16px;
            padding: 14px 18px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
        }

        .public-btn:hover {
            background: linear-gradient(135deg, #0ea5e9, #1d4ed8);
        }

        .error-box {
            background: #fee2e2;
            color: #991b1b;
            padding: 14px;
            border-radius: 14px;
            margin-bottom: 16px;
        }

        .full-box {
            background: #fee2e2;
            color: #991b1b;
            padding: 16px;
            border-radius: 16px;
            border: 1px solid #fecaca;
        }

        @media (max-width: 768px) {
            .public-card {
                padding: 20px;
                border-radius: 18px;
            }

            .field-grid,
            .count-grid,
            .price-grid {
                grid-template-columns: 1fr;
            }

            .public-title {
                font-size: 1.6rem;
            }
        }
    </style>
</head>
<body>
    <div class="public-wrap">
        <div class="public-card">
            <div class="public-header">
                {{-- Justera sökvägen om din logotyp ligger på annan plats --}}
				<center>
              <img src="{{ asset('images/lopgga_gul_morkbla_150.png') }}" alt="Logotyp"></center>
                <div class="public-title">{{ $bookingPage->page_title }}</div>
                <div class="public-subtitle">
                    Boka plats till specialtur
                </div>
            </div>

            <div class="tour-box">
                <strong>{{ $tour->title }}</strong>
                {{ $tour->tour_date }} kl {{ substr($tour->start_time ?? '', 0, 5) }}
            </div>

            @if($bookingPage->page_text)
                <div style="margin-bottom: 22px;">
                    {!! nl2br(e($bookingPage->page_text)) !!}
                </div>
            @endif

            <div class="price-box">
                <div class="section-title">Pris per kategori</div>
                <div class="price-grid">
                    <div class="price-item">
                        <div class="price-item-label">Vuxen</div>
                        <div class="price-item-value">{{ number_format((float) $bookingPage->adult_price, 2, ',', ' ') }} kr</div>
                    </div>
                    <div class="price-item">
                        <div class="price-item-label">Ungdom</div>
                        <div class="price-item-value">{{ number_format((float) $bookingPage->youth_price, 2, ',', ' ') }} kr</div>
                    </div>
                    <div class="price-item">
                        <div class="price-item-label">Barn</div>
                        <div class="price-item-value">{{ number_format((float) $bookingPage->child_price, 2, ',', ' ') }} kr</div>
                    </div>
                </div>
            </div>

            @if($isFull)
                <div class="full-box">
                    {{ $bookingPage->full_tour_text ?: 'Denna tur är fullbokad.' }}
                </div>
            @else
                @if($errors->any())
                    <div class="error-box">
                        <ul style="margin:0; padding-left:18px;">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('public.tour-booking.store', $bookingPage->slug) }}" class="public-form">
                    @csrf

                    <div class="field-grid">
                        <div class="form-block">
                            <label for="contact_name">Kontaktperson</label>
                            <input type="text" id="contact_name" name="contact_name" value="{{ old('contact_name') }}" required>
                        </div>

                        <div class="form-block">
                            <label for="phone">Telefon</label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone') }}">
                        </div>
                    </div>

                    <div class="form-block">
                        <label for="email">E-post</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                    </div>

                    <div>
                        <div class="section-title">Antal deltagare</div>
                        <div class="count-grid">
                            <div class="form-block">
                                <label for="men_count">Män</label>
                                <input type="number" min="0" id="men_count" name="men_count" value="{{ old('men_count', 0) }}" required>
                            </div>

                            <div class="form-block">
                                <label for="women_count">Kvinnor</label>
                                <input type="number" min="0" id="women_count" name="women_count" value="{{ old('women_count', 0) }}" required>
                            </div>

                            <div class="form-block">
                                <label for="youth_count">Ungdomar</label>
                                <input type="number" min="0" id="youth_count" name="youth_count" value="{{ old('youth_count', 0) }}" required>
                            </div>

                            <div class="form-block">
                                <label for="child_count">Barn</label>
                                <input type="number" min="0" id="child_count" name="child_count" value="{{ old('child_count', 0) }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-block">
                        <label for="notes">Meddelande</label>
                        <textarea id="notes" name="notes">{{ old('notes') }}</textarea>
                    </div>

                    @if($bookingPage->booking_terms)
                        <div class="terms-box">
                            <div class="section-title" style="margin-bottom:8px;">Bokningsvillkor</div>
                            {!! nl2br(e($bookingPage->booking_terms)) !!}

                            <label class="terms-check">
                                <input type="checkbox" name="accept_terms" value="1" {{ old('accept_terms') ? 'checked' : '' }} required>
                                <span>Jag godkänner bokningsvillkoren.</span>
                            </label>
                        </div>
                    @endif

                    <button class="public-btn" type="submit">Skicka bokning</button>
                </form>
            @endif
        </div>
    </div>
</body>
</html>