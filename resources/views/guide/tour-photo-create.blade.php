<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Ladda upp turbild</title>
    <style>
        :root {
            color-scheme: light;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        body {
            margin: 0;
            background: #f8fafc;
            color: #0f172a;
        }

        main {
            max-width: 560px;
            margin: 0 auto;
            padding: 1rem;
        }

        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1rem;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
        }

        h1 {
            margin: 0 0 0.35rem;
            font-size: 1.25rem;
        }

        p {
            margin: 0 0 1rem;
            color: #64748b;
        }

        label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 700;
        }

        input {
            box-sizing: border-box;
            width: 100%;
            min-height: 44px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 0.65rem;
            font: inherit;
        }

        .field {
            margin-top: 1rem;
        }

        .help {
            margin-top: 0.4rem;
            color: #64748b;
            font-size: 0.9rem;
        }

        .error {
            margin-top: 0.4rem;
            color: #b91c1c;
            font-size: 0.9rem;
            font-weight: 700;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
            margin-top: 1.25rem;
        }

        button,
        a.button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            border-radius: 10px;
            padding: 0.65rem 1rem;
            border: 0;
            text-decoration: none;
            font: inherit;
            font-weight: 800;
        }

        button {
            flex: 1 1 180px;
            background: #2563eb;
            color: #fff;
        }

        a.button {
            background: #e2e8f0;
            color: #0f172a;
        }
    </style>
</head>
<body>
    <main>
        <div class="card">
            <h1>Ladda upp turbild</h1>
            <p>{{ $tour->tourType?->name ?? 'Tur' }} {{ $tour->starts_at?->format('Y-m-d H:i') }}</p>

            <form method="POST" action="{{ route('guide.tours.photos.store', $tour, false) }}" enctype="multipart/form-data">
                @csrf

                <div class="field">
                    <label for="photo">Bild</label>
                    <input
                        type="file"
                        name="photo"
                        id="photo"
                        accept="image/jpeg,image/png,image/gif,image/webp,image/heic,image/heif,.heic,.heif"
                        capture="environment"
                        required
                    >
                    <div class="help">Max 10 MB. På mobil kan du ta foto direkt.</div>

                    @error('photo')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="caption">Bildtext (valfritt)</label>
                    <input
                        type="text"
                        name="caption"
                        id="caption"
                        maxlength="255"
                        value="{{ old('caption') }}"
                        placeholder="Ex. Företagsgrupp vid kanonen"
                    >

                    @error('caption')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="actions">
                    <button type="submit">Ladda upp bild</button>
                    <a href="{{ route('guide.tours.show', $tour, false) }}" class="button">Avbryt</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
