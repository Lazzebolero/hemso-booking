<!doctype html>
<html lang="sv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hemsö Bokningssystem</title>
    <style>
        body{font-family:Arial,sans-serif;margin:0;background:#f5f7fa;color:#222} .nav{background:#17324d;padding:12px 20px} .nav a{color:#fff;margin-right:14px;text-decoration:none} .container{max-width:1200px;margin:20px auto;padding:0 16px} .card{background:#fff;border:1px solid #ddd;border-radius:8px;margin-bottom:20px} .card-body{padding:16px} table{width:100%;border-collapse:collapse} th,td{padding:10px;border-bottom:1px solid #ddd;text-align:left} .btn{display:inline-block;padding:8px 12px;border-radius:6px;text-decoration:none;border:1px solid #999;background:#fff;color:#222} .btn-primary{background:#17324d;color:#fff;border-color:#17324d} .btn-danger{background:#b42318;color:#fff;border-color:#b42318} .grid{display:grid;gap:12px} .grid-3{grid-template-columns:repeat(3,1fr)} .mb-4{margin-bottom:16px} .alert{padding:12px;border-radius:6px;background:#e7f5ea;border:1px solid #9fd3ab;margin-bottom:16px} input,select,textarea{width:100%;padding:8px;border:1px solid #bbb;border-radius:6px} label{display:block;margin-bottom:6px;font-weight:600} form.inline{display:inline}
    .badge-soft-info {
    background: rgba(37, 99, 235, 0.12);
    color: #1d4ed8;
}

.badge-soft-default {
    background: rgba(100, 116, 139, 0.12);
    color: #475569;
}

@media (max-width: 767.98px) {

    .admin-shell {
        grid-template-columns: 1fr;
    }

    .sidebar {
        display: none;
    }

    .guide-bottom-nav {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1050;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5rem;
        background: rgba(15, 23, 42, 0.96);
        backdrop-filter: blur(8px);
        padding: 0.7rem;
        box-shadow: 0 -10px 30px rgba(15, 23, 42, 0.2);
    }

    .guide-bottom-link {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.25rem;
        min-height: 56px;
        border-radius: 16px;
        color: rgba(255,255,255,0.82);
        text-decoration: none;
        font-size: 0.8rem;
        font-weight: 600;
        background: transparent;
        border: none;
        width: 100%;
    }

    .guide-bottom-link.active {
        background: rgba(255,255,255,0.1);
        color: #fff;
    }

    .guide-bottom-link i {
        font-size: 1.1rem;
    }

    .guide-bottom-button {
        appearance: none;
        cursor: pointer;
    }

    /* så innehåll inte hamnar bakom menyn */
    .content-area {
        padding-bottom: 80px;
    }
}</style>
</head>
<body>
<div class="nav">
    @auth
        <a href="{{ route('dashboard') }}">Dashboard</a>
        @if(auth()->user()->role === 'guide')
    <div class="nav-section-title d-none d-md-block">Guide</div>
    <a class="side-link d-none d-md-flex {{ request()->routeIs('guide.reports.*') ? 'active-link' : '' }}" href="{{ route('guide.reports.create') }}">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span>Ny felrapport</span>
    </a>
@endif
        @if(auth()->user()->role === 'admin')
            <a href="{{ route('admin.users.index') }}">Användare</a>
		<a class="side-link {{ request()->routeIs('admin.languages.*') ? 'active-link' : '' }}" href="{{ route('admin.languages.index') }}">
    <i class="bi bi-translate"></i>
    <span>Språk</span>
</a>
        @endif
        @if(auth()->user()->role === 'guide')
            <a href="{{ route('guide.reports.create') }}">Ny felrapport</a>
        @endif
    @endauth
</div>
<div class="container">
    @if(session('success'))<div class="alert">{{ session('success') }}</div>@endif
    @yield('content')
</div>
@auth
    @if(auth()->user()->role === 'guide')
        <nav class="guide-bottom-nav d-md-none">
            <a href="{{ route('guide.dashboard') }}" class="guide-bottom-link {{ request()->routeIs('guide.dashboard') ? 'active' : '' }}">
                <i class="bi bi-house-door-fill"></i>
                <span>Turer</span>
            </a>

            <a href="{{ route('guide.reports.create') }}" class="guide-bottom-link {{ request()->routeIs('guide.reports.*') ? 'active' : '' }}">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>Felrapport</span>
            </a>

            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="guide-bottom-link guide-bottom-button">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logga ut</span>
                </button>
            </form>
        </nav>
    @endif
@endauth
</body>
</html>
