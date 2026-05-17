<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Besökshund</title>
    <style>
        body { margin: 0; background: #f8fafc; color: #0f172a; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        main { max-width: 560px; margin: 0 auto; padding: 1rem; }
        .page-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 1rem; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05); }
        .page-header { margin-bottom: 1rem; }
        .page-title { margin: 0 0 0.35rem; font-size: 1.25rem; }
        .page-subtitle, .form-text { color: #64748b; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-3 { margin-bottom: 1rem; }
        .mb-4 { margin-bottom: 1.25rem; }
        .mt-2 { margin-top: 0.5rem; }
        label { display: block; margin-bottom: 0.4rem; font-weight: 700; }
        .form-control { box-sizing: border-box; width: 100%; min-height: 44px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0.65rem; font: inherit; }
        .btn { display: inline-flex; align-items: center; justify-content: center; min-height: 46px; border-radius: 10px; padding: 0.65rem 1rem; border: 0; text-decoration: none; font: inherit; font-weight: 800; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-outline-secondary { background: #e2e8f0; color: #0f172a; }
        .d-flex { display: flex; }
        .flex-wrap { flex-wrap: wrap; }
        .gap-2 { gap: 0.65rem; }
        .flex-grow-1 { flex-grow: 1; }
        .text-danger { color: #b91c1c; font-weight: 700; }
    </style>
</head>
<body>
    <main>
        <div class="page-header">
            <h1 class="page-title">Besökshund</h1>
            <div class="page-subtitle">Registrera hund som lämnas i vård vid grindstugan under guidad tur.</div>
        </div>

        <div class="page-card">
            @include('visitor-dogs._form', ['defaultVisitDate' => $defaultVisitDate])
        </div>
    </main>
</body>
</html>
