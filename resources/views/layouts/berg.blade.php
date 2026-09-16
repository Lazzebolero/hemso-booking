<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Närvaro i berget')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:500,700,800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/css/bootstrap-icons.css">
    <style>
        :root {
            --bg: #0b1220;
            --card: #152033;
            --line: #243049;
            --text: #f8fafc;
            --muted: #94a3b8;
            --in: #16a34a;
            --out: #b45309;
            --danger: #dc2626;
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            min-height: 100%;
            background: var(--bg);
            color: var(--text);
            font-family: Figtree, sans-serif;
        }
        body { padding: 12px 12px calc(24px + env(safe-area-inset-bottom)); }
        .berg-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px;
        }
        .berg-title { margin: 0; font-size: 1.35rem; font-weight: 800; }
        .berg-sub { margin: 4px 0 0; color: var(--muted); font-size: 0.92rem; }
        .berg-count {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 10px 14px;
            text-align: right;
            min-width: 92px;
        }
        .berg-count strong { display: block; font-size: 2rem; line-height: 1; }
        .berg-count span { color: var(--muted); font-size: 0.8rem; font-weight: 700; }
        .berg-nav {
            display: flex;
            gap: 8px;
            margin-bottom: 14px;
        }
        .berg-nav a {
            flex: 1;
            text-align: center;
            text-decoration: none;
            color: var(--text);
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 10px 8px;
            font-weight: 800;
            font-size: 0.92rem;
        }
        .berg-nav a.active { background: #1d4ed8; border-color: #1d4ed8; }
        .alert {
            background: #14532d;
            color: #dcfce7;
            border-radius: 12px;
            padding: 10px 12px;
            margin-bottom: 12px;
            font-weight: 700;
        }
        .alert-error { background: #7f1d1d; color: #fecaca; }
        .logout-form button, .ghost-btn {
            background: transparent;
            color: var(--muted);
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 8px 10px;
            font-weight: 700;
        }
        .berg-status {
            background: #1e293b;
            border-radius: 14px;
            padding: 12px 14px;
            font-weight: 800;
            margin-bottom: 12px;
        }
        .berg-status-in { background: #14532d; }
        .berg-status-out { background: #1e293b; }
        .berg-btn {
            display: block !important;
            width: 100% !important;
            min-height: 56px !important;
            border: 0 !important;
            border-radius: 16px !important;
            padding: 18px 14px !important;
            font-size: 1.15rem !important;
            font-weight: 800 !important;
            margin: 0 0 10px !important;
            color: #fff !important;
            line-height: 1.25 !important;
            -webkit-appearance: none !important;
            appearance: none !important;
        }
        .berg-btn-in { background: #16a34a; }
        .berg-btn-out { background: #b45309; }
        .berg-btn-group { background: #1d4ed8; }
        .berg-empty { color: #64748b; font-size: 0.9rem; }
        .mini-in { background: #16a34a; }
        .mini-out { background: #b45309; }
        .mini-leave { background: #dc2626; }
        .mini-restore { background: #334155; }
        .berg-field { margin-bottom: 12px; }
        .berg-field label {
            display: block;
            font-size: 0.8rem;
            font-weight: 800;
            color: var(--muted);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .berg-field input, .berg-field select, .berg-field textarea {
            width: 100%;
            border-radius: 12px;
            border: 1px solid var(--line);
            background: #0b1220;
            color: var(--text);
            padding: 12px;
            font-size: 1rem;
        }
        .berg-submit {
            display: block;
            width: 100%;
            border: 0;
            border-radius: 16px;
            padding: 16px 14px;
            font-size: 1.05rem;
            font-weight: 800;
            color: #fff;
            background: #1d4ed8;
            margin-top: 8px;
        }
        .berg-section { margin-top: 18px; }
        .berg-h {
            margin: 0 0 8px;
            font-size: 0.95rem;
            color: #94a3b8;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .berg-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            background: #152033;
            border: 1px solid #243049;
            border-radius: 12px;
            padding: 10px 12px;
            margin-bottom: 8px;
        }
        .berg-name { font-weight: 700; }
        .berg-meta { color: #94a3b8; font-size: 0.8rem; font-weight: 700; }
        .mini-btn {
            border: 0;
            border-radius: 10px;
            padding: 8px 10px;
            font-weight: 800;
            font-size: 0.8rem;
            color: #fff;
            text-decoration: none;
            display: inline-block;
        }
        .mini-edit { background: #334155; }
        .mini-delete { background: #dc2626; }
        .berg-actions { display: flex; gap: 6px; flex-shrink: 0; flex-wrap: wrap; justify-content: flex-end; }
        .berg-phone {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 16px 14px;
            margin-bottom: 10px;
            text-decoration: none;
            color: var(--text);
        }
        .berg-phone strong { display: block; font-size: 1.05rem; }
        .berg-phone span { color: #93c5fd; font-weight: 800; font-size: 1.05rem; white-space: nowrap; }
        .berg-log-dir {
            flex-shrink: 0;
            border-radius: 999px;
            padding: 6px 10px;
            font-weight: 800;
            font-size: 0.8rem;
            min-width: 42px;
            text-align: center;
        }
        .berg-log-in { background: #14532d; color: #bbf7d0; }
        .berg-log-out { background: #7c2d12; color: #fed7aa; }
    </style>
    @yield('head')
</head>
<body>
    <div class="berg-top">
        <div>
            <h1 class="berg-title">@yield('heading', 'I berget')</h1>
            <p class="berg-sub">
                {{ $production?->name ?? 'Ingen produktion' }}
                @if($production && $production->siteListLabel() !== '')
                    · {{ $production->siteListLabel() }}
                @endif
                · {{ auth()->user()->name }}
            </p>
        </div>
        <div class="berg-count">
            <strong id="berg-inside-count">{{ $insideCount ?? 0 }}</strong>
            <span>inne nu</span>
        </div>
    </div>

    <nav class="berg-nav">
        <a href="{{ url('/berget') }}" class="{{ request()->is('berget') ? 'active' : '' }}">Närvaro</a>
        <a href="{{ url('/berget/nummer') }}" class="{{ request()->is('berget/nummer') ? 'active' : '' }}">Nummer</a>
        <a href="{{ url('/berget/logg') }}" class="{{ request()->is('berget/logg') ? 'active' : '' }}">Logg</a>
        @if(!empty($canManagePeople))
            <a href="{{ url('/berget/personer') }}" class="{{ request()->is('berget/personer*') ? 'active' : '' }}">Personer</a>
        @endif
    </nav>

    @if(session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    @yield('content')

    <form method="POST" action="{{ url('/logout') }}" class="logout-form" style="margin-top: 18px;">
        @csrf
        <button type="submit">Logga ut</button>
    </form>

    @yield('scripts')
</body>
</html>
