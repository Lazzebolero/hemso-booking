<!doctype html>
<html lang="sv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hemsö Bokningssystem</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
	.badge-soft-primary {
    background: rgba(37, 99, 235, 0.12);
    color: #1d4ed8;
}

.badge-soft-secondary {
    background: rgba(100, 116, 139, 0.14);
    color: #475569;
}

.badge-soft-success {
    background: rgba(22, 163, 74, 0.12);
    color: #166534;
}

.badge-soft-warning {
    background: rgba(245, 158, 11, 0.15);
    color: #92400e;
}

.badge-soft-danger {
    background: rgba(220, 38, 38, 0.12);
    color: #991b1b;
}
        :root {
            --brand-bg: #0f172a;
            --brand-accent: #2563eb;
            --brand-success: #16a34a;
            --brand-warning: #f59e0b;
            --brand-danger: #dc2626;
            --text-main: #0f172a;
            --text-soft: #64748b;
            --shadow-soft: 0 14px 40px rgba(15, 23, 42, 0.08);
        }

        body {
            background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
            color: var(--text-main);
            font-family: Inter, Arial, Helvetica, sans-serif;
            min-height: 100vh;
        }

        .admin-shell {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--brand-bg) 0%, #1e293b 100%);
            color: #fff;
            padding: 1.5rem 1rem;
            position: sticky;
            top: 0;
            height: 100vh;
            box-shadow: 12px 0 30px rgba(15, 23, 42, 0.12);
        }

        .brand-box {
            padding: 0.9rem 1rem 1.2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1rem;
        }

        .brand-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
            color: #fff;
        }

        .brand-subtitle {
            color: rgba(255,255,255,0.65);
            font-size: 0.92rem;
            margin: 0;
        }

        .nav-section-title {
            font-size: 0.78rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.45);
            padding: 0.9rem 0.9rem 0.5rem;
        }

        .side-nav {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .side-link {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            color: rgba(255,255,255,0.88);
            text-decoration: none;
            padding: 0.82rem 0.95rem;
            border-radius: 16px;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .side-link:hover,
        .side-link.active-link {
            background: rgba(255,255,255,0.10);
            color: #fff;
            transform: translateX(3px);
        }

        .side-link i {
            width: 18px;
            text-align: center;
            font-size: 1rem;
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 1rem 0.35rem 0;
        }

        .logout-btn {
            width: 100%;
            border-radius: 14px;
        }

        .content-area {
            padding: 1.5rem 1.5rem 2rem;
        }

        .topbar {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.7);
            border-radius: 22px;
            box-shadow: var(--shadow-soft);
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }

        .topbar-title {
            font-size: 1.35rem;
            font-weight: 700;
            margin: 0;
        }

        .topbar-subtitle {
            color: var(--text-soft);
            margin: 0.2rem 0 0;
        }

        .page-card,
        .card,
        .stats-filter-card,
        .stats-card,
        .chart-card,
        .table-card {
            background: rgba(255,255,255,0.92);
            border-radius: 22px;
            box-shadow: var(--shadow-soft);
            border: 1px solid rgba(255,255,255,0.75);
        }

        .page-card {
            padding: 1.25rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
        }

        .stats-card {
            padding: 1.2rem;
            position: relative;
            overflow: hidden;
        }

        .stats-card::after {
            content: '';
            position: absolute;
            top: -26px;
            right: -26px;
            width: 96px;
            height: 96px;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.12);
        }

        .stats-card.accent-success::after { background: rgba(22, 163, 74, 0.12); }
        .stats-card.accent-warning::after { background: rgba(245, 158, 11, 0.14); }
        .stats-card.accent-danger::after { background: rgba(220, 38, 38, 0.12); }
        .stats-card.accent-purple::after { background: rgba(124, 58, 237, 0.12); }

        .stats-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.06);
            font-size: 1.2rem;
            margin-bottom: 0.9rem;
        }

        .stats-label {
            font-size: 0.92rem;
            color: var(--text-soft);
            margin-bottom: 0.3rem;
        }

        .stats-value {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1.1;
        }

        .stats-subtext {
            font-size: 0.88rem;
            color: var(--text-soft);
            margin-top: 0.45rem;
        }

        .table-responsive {
            border-radius: 18px;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: #f8fafc;
            color: #334155;
            font-weight: 700;
            border-bottom-width: 1px;
        }

        .btn {
            border-radius: 14px;
            padding: 0.65rem 1rem;
            font-weight: 600;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border: none;
        }

        .btn-outline-secondary {
            border-color: #cbd5e1;
            color: #334155;
        }

        .form-control,
        .form-select {
            border-radius: 14px;
            border-color: #dbe2ea;
            padding: 0.7rem 0.9rem;
        }

        .alert {
            border-radius: 16px;
            box-shadow: var(--shadow-soft);
        }

        .badge-soft {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 999px;
            padding: 0.45rem 0.8rem;
            font-weight: 700;
            font-size: 0.82rem;
        }

        .badge-soft-success {
            background: rgba(22, 163, 74, 0.12);
            color: #166534;
        }

        .badge-soft-warning {
            background: rgba(245, 158, 11, 0.15);
            color: #92400e;
        }

        .badge-soft-danger {
            background: rgba(220, 38, 38, 0.12);
            color: #991b1b;
        }
		
		/* Lägg till i layouts/app.blade.php om de saknas */
.badge-soft-info {
    background: rgba(37, 99, 235, 0.12);
    color: #1d4ed8;
}
.badge-soft-default {
    background: rgba(100, 116, 139, 0.12);
    color: #475569;
}


        .progress-modern {
            width: 100%;
            height: 14px;
            background: #e5e7eb;
            border-radius: 999px;
            overflow: hidden;
        }

        .progress-modern > div {
            height: 100%;
            border-radius: 999px;
        }

        .page-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .section-title {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .muted {
            color: var(--text-soft);
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 991px) {
            .admin-shell {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: relative;
                height: auto;
                border-bottom-left-radius: 24px;
                border-bottom-right-radius: 24px;
            }
        }

        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .content-area {
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }
    </style>
</head>
<body>
<div class="admin-shell">
    @auth
        <aside class="sidebar d-flex flex-column">
            <div class="brand-box">
                <div class="brand-title"><i class="bi bi-fort me-2"></i>Hemsö</div>
                <p class="brand-subtitle">Boknings- och guidesystem</p>
            </div>

            <div class="nav-section-title">Navigering</div>
            <nav class="side-nav">
                <a class="side-link {{ request()->routeIs('dashboard') || request()->routeIs('admin.dashboard') || request()->routeIs('guide.dashboard') ? 'active-link' : '' }}" href="{{ route('dashboard') }}">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>

                @if(auth()->user()->role !== 'guide')
                    <a class="side-link {{ request()->routeIs('admin.tours.*') ? 'active-link' : '' }}" href="{{ route('admin.tours.index') }}">
                        <i class="bi bi-signpost-split-fill"></i>
                        <span>Turer</span>
                    </a>
                    <a class="side-link {{ request()->routeIs('admin.bookings.*') ? 'active-link' : '' }}" href="{{ route('admin.bookings.index') }}">
                        <i class="bi bi-journal-check"></i>
                        <span>Bokningar</span>
                    </a>
                    <a class="side-link {{ request()->routeIs('admin.shifts.*') ? 'active-link' : '' }}" href="{{ route('admin.shifts.index') }}">
                        <i class="bi bi-calendar3"></i>
                        <span>Schema</span>
                    </a>
                    @if(Route::has('admin.statistics.index'))
                        <a class="side-link {{ request()->routeIs('admin.statistics.*') ? 'active-link' : '' }}" href="{{ route('admin.statistics.index') }}">
                            <i class="bi bi-bar-chart-line-fill"></i>
                            <span>Statistik</span>
                        </a>
                    @endif
                    <a class="side-link {{ request()->routeIs('admin.reports.*') ? 'active-link' : '' }}" href="{{ route('admin.reports.index') }}">
                        <i class="bi bi-tools"></i>
                        <span>Felrapporter</span>
                    </a>
					
                    <a class="side-link {{ request()->routeIs('admin.activity-logs.*') ? 'active-link' : '' }}" href="{{ route('admin.activity-logs.index') }}">
                        <i class="bi bi-clock-history"></i>
                        <span>Aktivitetslogg</span>
                    </a>
                @endif

                @if(auth()->user()->role === 'admin')
    <div class="nav-section-title">Administration</div>

    <a class="side-link {{ request()->routeIs('admin.users.*') ? 'active-link' : '' }}" href="{{ route('admin.users.index') }}">
        <i class="bi bi-people-fill"></i>
        <span>Användare</span>
    </a>

    <a class="side-link {{ request()->routeIs('admin.settings.*') ? 'active-link' : '' }}" href="{{ route('admin.settings.index') }}">
        <i class="bi bi-gear-fill"></i>
        <span>Inställningar</span>
    </a><a class="side-link {{ request()->routeIs('admin.report-options.*') ? 'active-link' : '' }}" href="{{ route('admin.report-options.index') }}">
    <i class="bi bi-sliders"></i>
    <span>Felrapport-val</span>
</a><a class="side-link {{ request()->routeIs('admin.tour-types.*') ? 'active-link' : '' }}" href="{{ route('admin.tour-types.index') }}">
    <i class="bi bi-signpost-split-fill"></i>
    <span>Turtyper</span>
</a>
<a class="side-link {{ request()->routeIs('admin.notification-templates.*') ? 'active-link' : '' }}"
   href="{{ route('admin.notification-templates.index') }}">
    <i class="bi bi-envelope-fill"></i>
    <span>Mailmallar</span>
</a>
@endif

                @if(auth()->user()->role === 'guide')
                    <div class="nav-section-title">Guide</div>
                    <a class="side-link {{ request()->routeIs('guide.reports.*') ? 'active-link' : '' }}" href="{{ route('guide.reports.create') }}">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span>Ny felrapport</span>
                    </a>
                @endif
            </nav>

            <div class="sidebar-footer">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-light logout-btn">
                        <i class="bi bi-box-arrow-right me-2"></i>Logga ut
                    </button>
                </form>
            </div>
        </aside>
    @endauth

    <main class="content-area">
        <div class="topbar d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="topbar-title">
                    @auth
                        Hej {{ auth()->user()->name }}
                    @else
                        Hemsö Bokningssystem
                    @endauth
                </h1>
                <p class="topbar-subtitle">Översikt, planering och statistik för guidade visningar på Hemsö fästning.</p>
            </div>

            @auth
                <div class="badge-soft badge-soft-success">
                    <i class="bi bi-person-badge"></i>
                    {{ ucfirst(auth()->user()->role) }}
                </div>
            @endauth
        </div>

        @if(session('success'))
            <div class="alert alert-success mb-4">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger mb-4">
                <strong>Det finns fel i formuläret:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
