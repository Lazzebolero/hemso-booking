<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Hemsö') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
@include('partials.pwa')
    <style>
        :root {
            --brand-bg: #0f172a;
            --brand-bg-soft: #1e293b;
            --brand-line: #dbe3ee;
            --brand-line-soft: #e8eef5;
            --brand-accent: #2563eb;
            --brand-accent-soft: rgba(37, 99, 235, 0.10);
            --brand-success: #059669;
            --brand-warning: #d97706;
            --brand-danger: #dc2626;

            --surface: #ffffff;
            --surface-soft: #f8fafc;
            --surface-muted: #f1f5f9;

            --text-main: #0f172a;
            --text-soft: #64748b;
            --text-faint: #94a3b8;

            --shadow-soft: 0 10px 28px rgba(15, 23, 42, 0.06);
            --shadow-card: 0 4px 16px rgba(15, 23, 42, 0.05);

            --radius-xl: 20px;
            --radius-lg: 14px;
            --radius-md: 10px;

            --content-max: 1600px;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            min-height: 100%;
        }

        body {
            margin: 0;
            font-family: 'Figtree', sans-serif;
            color: var(--text-main);
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.06), transparent 28%),
                linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
        }

        a {
            text-decoration: none;
        }

        .app-shell {
            display: grid;
            grid-template-columns: 280px minmax(0, 1fr);
            min-height: 100vh;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--brand-bg) 0%, #162234 100%);
            color: #fff;
            padding: 1.25rem 0.9rem;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            box-shadow: 10px 0 30px rgba(15, 23, 42, 0.14);
        }

        .sidebar-flex {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .brand-box {
            padding: 0.7rem 0.8rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            margin-bottom: 0.9rem;
        }

        .brand-title {
            display: flex;
            align-items: center;
            font-size: 1.2rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #fff;
        }

        .brand-subtitle {
            margin: 0.35rem 0 0;
            color: rgba(226, 232, 240, 0.72);
            font-size: 0.88rem;
            line-height: 1.4;
        }

        .nav-section-title,
        .sidebar-section-title {
            font-size: 0.7rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(148, 163, 184, 0.82);
            margin: 1rem 0 0.45rem;
            padding: 0 0.8rem;
        }

        .side-nav {
            display: flex;
            flex-direction: column;
            gap: 0.28rem;
        }

        .side-link {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.78rem 0.9rem;
            border-radius: 12px;
            color: rgba(241, 245, 249, 0.88);
            transition: background 0.18s ease;
            font-weight: 600;
            font-size: 0.94rem;
        }

        .side-link i {
            width: 1.1rem;
            text-align: center;
            font-size: 1rem;
        }

        .side-link:hover {
            background: rgba(255,255,255,0.08);
            color: #fff;
        }

        .active-link {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.18), rgba(37, 99, 235, 0.22));
            color: #fff !important;
            box-shadow: inset 0 0 0 1px rgba(125, 211, 252, 0.14);
        }

        .sidebar-nav-badge {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.35rem;
            height: 1.35rem;
            padding: 0 0.35rem;
            border-radius: 999px;
            background: #f59e0b;
            color: #0f172a;
            font-size: 0.72rem;
            font-weight: 800;
            line-height: 1;
        }

        .sidebar-footer {
            margin-top: 1.25rem;
            padding-top: 0.9rem;
            border-top: 1px solid rgba(255,255,255,0.08);
        }

        .logout-btn {
            width: 100%;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.18);
            color: #fff;
            background: transparent;
            font-weight: 700;
            padding: 0.8rem 1rem;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.08);
            color: #fff;
        }

        .main-area {
            min-width: 0;
            padding: 1.1rem 1.4rem 1.8rem;
        }

        .content-wrap {
            width: 100%;
            max-width: var(--content-max);
            margin: 0;
        }

        @media (min-width: 1800px) {
            :root {
                --content-max: 1760px;
            }
        }

        .topbar {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(255,255,255,0.72);
            border-radius: 18px;
            padding: 1.05rem 1.2rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow-soft);
        }

        .topbar-title {
            margin: 0;
            font-size: 1.28rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .topbar-subtitle {
            margin: 0.2rem 0 0;
            color: var(--text-soft);
            font-size: 0.9rem;
        }

        .topbar-notice-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.55rem 0.75rem;
            border-radius: 999px;
            background: #fff7ed;
            color: #9a3412;
            border: 1px solid #fdba74;
            font-weight: 800;
            font-size: 0.85rem;
        }

        .topbar-link-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.55rem 0.8rem;
            border-radius: 999px;
            background: #fff;
            color: #334155;
            border: 1px solid #dbe3ee;
            font-weight: 800;
            font-size: 0.85rem;
            text-decoration: none;
            transition: all 0.18s ease;
        }

        .topbar-link-chip:hover {
            background: #f8fafc;
            color: #0f172a;
            border-color: #cbd5e1;
        }

        .topbar-link-chip-active {
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.14), rgba(37, 99, 235, 0.16));
            border-color: rgba(37, 99, 235, 0.22);
            color: #0f172a;
        }

        .topbar-count-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.35rem;
            height: 1.35rem;
            padding: 0 0.35rem;
            border-radius: 999px;
            background: #2563eb;
            color: #fff;
            font-size: 0.72rem;
            font-weight: 800;
            line-height: 1;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .page-title {
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin: 0;
        }

        .page-subtitle {
            margin-top: 0.25rem;
            font-size: 0.92rem;
            color: var(--text-soft);
            line-height: 1.45;
        }

        .page-actions {
            display: flex;
            gap: 0.55rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .page-card {
            background: var(--surface);
            border: 1px solid var(--brand-line-soft);
            border-radius: var(--radius-lg);
            padding: 1rem 1.1rem;
            box-shadow: var(--shadow-card);
        }

        .page-card + .page-card {
            margin-top: 1rem;
        }

        .compact-card {
            padding: 0.85rem 0.95rem;
        }

        .form-side-box {
            background: var(--surface);
            border: 1px solid var(--brand-line-soft);
            border-radius: var(--radius-lg);
            padding: 1rem 1.1rem;
            box-shadow: var(--shadow-card);
            align-self: start;
        }

        .form-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.7fr) 320px;
            gap: 1rem;
            align-items: start;
        }

        .section-title {
            font-size: 0.98rem;
            font-weight: 800;
            margin-bottom: 0.85rem;
            letter-spacing: -0.01em;
        }

        .flash-stack {
            display: grid;
            gap: 0.7rem;
            margin-bottom: 1rem;
        }

        .flash-message {
            border-radius: 14px;
            padding: 0.9rem 1rem;
            background: #fff;
            border: 1px solid var(--brand-line-soft);
            box-shadow: var(--shadow-card);
            font-weight: 600;
        }

        .flash-success {
            border-left: 4px solid var(--brand-success);
        }

        .flash-error {
            border-left: 4px solid var(--brand-danger);
        }

        .system-message-banner {
            border-radius: 14px;
            padding: 0.95rem 1rem;
            border: 1px solid var(--brand-line-soft);
            box-shadow: var(--shadow-card);
        }

        .system-message-normal {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1e3a8a;
        }

        .system-message-important {
            background: #fff7ed;
            border-color: #fdba74;
            color: #9a3412;
        }

        .system-message-title {
            font-weight: 800;
            margin-bottom: 0.25rem;
        }

        .system-message-body {
            font-size: 0.92rem;
            line-height: 1.5;
        }

        .muted,
        .text-muted {
            color: var(--text-soft) !important;
        }

        .small-muted {
            font-size: 0.84rem;
            color: var(--text-soft);
        }

        .badge-soft {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 999px;
            padding: 0.34rem 0.68rem;
            font-size: 0.78rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .badge-soft-success {
            background: rgba(5, 150, 105, 0.12);
            color: #047857;
        }

        .badge-soft-warning {
            background: rgba(217, 119, 6, 0.14);
            color: #b45309;
        }

        .badge-soft-danger {
            background: rgba(220, 38, 38, 0.12);
            color: #b91c1c;
        }

        .badge-soft-secondary {
            background: rgba(100, 116, 139, 0.12);
            color: #334155;
        }

        .btn {
            border-radius: 12px;
            font-weight: 700;
            padding: 0.62rem 0.95rem;
            line-height: 1.2;
            box-shadow: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .btn-sm {
            padding: 0.42rem 0.68rem;
            font-size: 0.82rem;
            border-radius: 10px;
        }

        .btn-lg {
            padding: 0.85rem 1rem;
            font-size: 0.98rem;
            border-radius: 14px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #38bdf8, #2563eb);
            border-color: transparent;
            color: #fff;
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #0ea5e9, #1d4ed8);
            color: #fff;
        }

        .btn-success {
            background: linear-gradient(135deg, #34d399, #059669);
            border-color: transparent;
            color: #fff;
        }

        .btn-success:hover {
            color: #fff;
            background: linear-gradient(135deg, #10b981, #047857);
        }

        .btn-danger {
            background: linear-gradient(135deg, #f87171, #dc2626);
            border-color: transparent;
            color: #fff;
        }

        .btn-danger:hover {
            color: #fff;
            background: linear-gradient(135deg, #ef4444, #b91c1c);
        }

        .btn-outline-secondary {
            background: #fff;
            color: #334155;
            border: 1px solid var(--brand-line);
        }

        .btn-outline-secondary:hover {
            background: var(--surface-muted);
            color: #0f172a;
            border-color: #cbd5e1;
        }

        .btn-outline-danger {
            background: #fff;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .btn-outline-danger:hover {
            background: #fef2f2;
            color: #991b1b;
        }

        .form-label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.86rem;
            font-weight: 700;
            color: #334155;
            text-align: left;
        }

        .form-control,
        .form-select,
        input[type="date"],
        input[type="time"],
        input[type="text"],
        input[type="number"],
        input[type="email"],
        input[type="datetime-local"],
        textarea,
        select {
            border-radius: 12px !important;
            border: 1px solid #cfd8e3 !important;
            background: #fff !important;
            min-height: 44px;
            padding: 0.68rem 0.82rem;
            font-size: 0.95rem;
            color: var(--text-main);
            text-align: left;
            width: 100%;
        }

        textarea.form-control,
        textarea {
            min-height: 110px;
            resize: vertical;
        }

        .form-control:focus,
        .form-select:focus,
        textarea:focus,
        input:focus,
        select:focus {
            border-color: #60a5fa !important;
            box-shadow: 0 0 0 0.18rem rgba(59, 130, 246, 0.12) !important;
            outline: 0;
        }

        .form-text {
            color: var(--text-soft);
            font-size: 0.82rem;
            margin-top: 0.3rem;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            min-height: 44px;
        }

        .form-check-input {
            margin-top: 0 !important;
            width: 1rem;
            height: 1rem;
        }

        .form-check-label {
            font-size: 0.9rem;
            color: #334155;
            font-weight: 600;
        }

        .toolbar-inline {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
        }

        .stats-card {
            background: rgba(255,255,255,0.96);
            border-radius: 18px;
            border: 1px solid rgba(255,255,255,0.75);
            box-shadow: var(--shadow-soft);
            padding: 1rem 1.05rem;
            min-height: 132px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .stats-label {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--text-soft);
            margin-bottom: 0.35rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .stats-value {
            font-size: 2rem;
            line-height: 1;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: var(--text-main);
            margin-bottom: 0.45rem;
        }

        .stats-subtext {
            font-size: 0.88rem;
            line-height: 1.45;
            color: var(--text-soft);
        }

        .admin-grid-2 {
            display: grid;
            grid-template-columns: minmax(0, 1.7fr) minmax(320px, 0.9fr);
            gap: 1rem;
            align-items: start;
        }

        .table-responsive-modern {
            width: 100%;
            overflow-x: auto;
        }

        .table-modern {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 780px;
        }

        .table-modern thead th {
            text-align: left;
            font-size: 0.78rem;
            font-weight: 800;
            color: var(--text-soft);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.8rem 0.85rem;
            border-bottom: 1px solid var(--brand-line-soft);
            background: #f8fafc;
        }

        .table-modern tbody td {
            padding: 0.9rem 0.85rem;
            border-bottom: 1px solid #eef2f7;
            vertical-align: middle;
            background: #fff;
        }

        .table-modern tbody tr:last-child td {
            border-bottom: 0;
        }

        .table-modern tbody tr:hover td {
            background: #f8fbff;
        }

        .progress-modern {
            width: 100%;
            height: 10px;
            border-radius: 999px;
            overflow: hidden;
            background: #e2e8f0;
        }

        .progress-modern > div {
            height: 100%;
            border-radius: 999px;
        }

        .info-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 0.95rem;
        }

        .w-100 { width: 100% !important; }

        .fw-bold { font-weight: 700 !important; }
        .fw-semibold { font-weight: 700 !important; }

        .mb-0 { margin-bottom: 0 !important; }
        .mb-1 { margin-bottom: 0.25rem !important; }
        .mb-3 { margin-bottom: 1rem !important; }
        .mb-4 { margin-bottom: 1.5rem !important; }

        .mt-1 { margin-top: 0.25rem !important; }
        .mt-2 { margin-top: 0.5rem !important; }
        .mt-3 { margin-top: 1rem !important; }

        .me-1 { margin-right: 0.25rem !important; }
        .me-2 { margin-right: 0.5rem !important; }
        .ms-2 { margin-left: 0.5rem !important; }

        .d-flex { display: flex !important; }
        .d-grid { display: grid !important; }

        .flex-wrap { flex-wrap: wrap !important; }
        .flex-column { flex-direction: column !important; }
        .flex-grow-1 { flex-grow: 1 !important; }

        .justify-content-between { justify-content: space-between !important; }
        .justify-content-center { justify-content: center !important; }

        .align-items-center { align-items: center !important; }
        .align-items-start { align-items: flex-start !important; }
        .align-items-end { align-items: flex-end !important; }

        .text-center { text-align: center !important; }

        .gap-2 { gap: 0.5rem !important; }
        .gap-3 { gap: 1rem !important; }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin-left: -0.5rem;
            margin-right: -0.5rem;
        }

        .row > [class*="col-"] {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
            width: 100%;
        }

        .g-3 > [class*="col-"] {
            margin-bottom: 1rem;
        }

        .g-4 > [class*="col-"] {
            margin-bottom: 1.25rem;
        }

        .col-12 { width: 100%; }
        .col-md-2 { width: 16.6667%; }
        .col-md-3 { width: 25%; }
        .col-md-4 { width: 33.3333%; }
        .col-md-5 { width: 41.6667%; }
        .col-md-6 { width: 50%; }
        .col-md-8 { width: 66.6667%; }

        .py-4 {
            padding-top: 1.5rem !important;
            padding-bottom: 1.5rem !important;
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .admin-grid-2,
            .form-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 992px) {
            .app-shell {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: relative;
                height: auto;
                max-height: 42vh;
                border-bottom-left-radius: 22px;
                border-bottom-right-radius: 22px;
            }

            .main-area {
                padding: 0.9rem;
            }

            .page-actions {
                width: 100%;
            }

            .page-actions .btn {
                flex: 1 1 auto;
            }

            .col-md-2,
            .col-md-3,
            .col-md-4,
            .col-md-5,
            .col-md-6,
            .col-md-8 {
                width: 100%;
            }
        }
.nav-section {
    margin-bottom: 1.25rem;
}

.nav-section-title {
    font-size: 0.78rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #64748b;
    margin-bottom: 0.5rem;
    padding: 0 0.35rem;
}
        @media (max-width: 700px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .table-modern {
                min-width: 680px;
            }
        }
    </style>
</head>
<body>
@php
    $user = auth()->user();
    $isGuideArea = request()->routeIs('guide.*');
    $activeRole = session('active_role');
    $adminHostPrefix = in_array($activeRole, ['admin', 'host'], true) ? $activeRole : 'admin';
    $hostStaffShell = $activeRole === \App\Support\Roles::HOST && (
        request()->routeIs('staff.*')
        || request()->routeIs('messages.*')
        || request()->routeIs('group-chats.*')
        || request()->routeIs('time.*')
        || request()->routeIs('visitor-dogs.*')
    );

    $activeSystemMessages = collect();
    $unreadSystemMessagesCount = 0;
    $unreadConversationsCount = 0;

    if (auth()->check() && class_exists(\App\Models\SystemMessage::class) && \Illuminate\Support\Facades\Schema::hasTable('system_messages')) {
        $activeSystemMessages = \App\Models\SystemMessage::query()
            ->visibleNow()
            ->forRole($activeRole)
            ->notDismissedForUser($user->id)
            ->with(['users' => function ($query) use ($user) {
                $query->where('users.id', $user->id);
            }])
            ->orderByDesc('priority')
            ->orderByDesc('starts_at')
            ->orderByDesc('created_at')
            ->get();

        $unreadSystemMessagesCount = $activeSystemMessages->filter(function ($message) {
            $pivotUser = $message->users->first();
            return !$pivotUser || empty($pivotUser->pivot?->read_at);
        })->count();
    }

    if (auth()->check() && class_exists(\App\Models\Conversation::class) && class_exists(\App\Models\ConversationParticipant::class) && \Illuminate\Support\Facades\Schema::hasTable('conversations') && \Illuminate\Support\Facades\Schema::hasTable('conversation_participants')) {
        $unreadConversationsCount = \App\Models\Conversation::query()
            ->whereHas('participants', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->where(function ($query) {
                $query->whereHas('participants', function ($participantQuery) {
                    $participantQuery
                        ->where('user_id', auth()->id())
                        ->where(function ($q) {
                            $q->whereNull('last_read_at')
                                ->orWhereColumn('last_read_at', '<', 'conversations.last_message_at');
                        });
                });
            })
            ->whereNotNull('last_message_at')
            ->count();
    }
@endphp

<div class="app-shell">
    @if($user && !$isGuideArea && ! $hostStaffShell)
        <aside class="sidebar sidebar-flex">
            <div class="brand-box">
                <div class="brand-title"><i class="bi bi-fort me-2"></i>Hemsö</div>
                <p class="brand-subtitle">Boknings- och guidesystem</p>
            </div>

    <nav class="side-nav">
	@if(Route::has($adminHostPrefix . '.dashboard'))
                    <a class="side-link {{ request()->routeIs('admin.dashboard') || request()->routeIs('host.dashboard') ? 'active-link' : '' }}"
                       href="{{ route($adminHostPrefix . '.dashboard') }}">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                @endif
    @if(in_array($activeRole, ['admin', 'host'], true) && (Route::has('admin.visitor-dogs.index') || Route::has('host.visitor-dogs.index')))
        @php
            $visitorDogsListRouteName = $activeRole === \App\Support\Roles::HOST && Route::has('host.visitor-dogs.index')
                ? 'host.visitor-dogs.index'
                : 'admin.visitor-dogs.index';
            $visitorDogsGalleryRouteName = $activeRole === \App\Support\Roles::HOST && Route::has('host.visitor-dogs.gallery')
                ? 'host.visitor-dogs.gallery'
                : 'admin.visitor-dogs.gallery';
        @endphp
        <a class="side-link {{ request()->routeIs('admin.visitor-dogs.index', 'admin.visitor-dogs.show', 'host.visitor-dogs.index', 'host.visitor-dogs.show') ? 'active-link' : '' }}"
           href="{{ route($visitorDogsListRouteName) }}">
            <i class="bi bi-list-ul"></i>
            <span>Besökshundar</span>
        </a>
        @if(Route::has('admin.visitor-dogs.gallery') || Route::has('host.visitor-dogs.gallery'))
            <a class="side-link {{ request()->routeIs('admin.visitor-dogs.gallery', 'host.visitor-dogs.gallery') ? 'active-link' : '' }}"
               href="{{ route($visitorDogsGalleryRouteName) }}">
                <i class="bi bi-images"></i>
                <span>Hundbilder</span>
            </a>
        @endif
    @endif
    @if($activeRole === \App\Support\Roles::HOST && Route::has('staff.dashboard'))
        <a class="side-link {{ request()->routeIs('staff.*') ? 'active-link' : '' }}"
           href="{{ route('staff.dashboard') }}"
           title="Samma mobilanpassade vy som för restaurangpersonal: pass, schema, dokument och meddelanden.">
            <i class="bi bi-phone"></i>
            <span>Mobil personalvy</span>
        </a>
    @endif
    @if($activeRole === \App\Support\Roles::HOST && Route::has('host.entry'))
        <a class="side-link {{ request()->routeIs('host.entry') ? 'active-link' : '' }}"
           href="{{ route('host.entry') }}">
            <i class="bi bi-grid-3x3-gap"></i>
            <span>Byt arbetsyta</span>
        </a>
    @endif
    @if(in_array($activeRole, ['admin', 'host'], true))
        <div class="nav-section">
            <div class="nav-section-title">Dagligt arbete</div>

            @if(Route::has($adminHostPrefix . '.tours.index'))
                <a class="side-link {{ request()->routeIs('admin.tours.*') || request()->routeIs('host.tours.*') ? 'active-link' : '' }}"
                   href="{{ route($adminHostPrefix . '.tours.index') }}">
                    <i class="bi bi-signpost-split"></i>
                    <span>Turer</span>
                </a>
            @endif

            @if(Route::has($adminHostPrefix . '.bookings.index'))
                <a class="side-link {{ request()->routeIs('admin.bookings.*') || request()->routeIs('host.bookings.*') ? 'active-link' : '' }}"
                   href="{{ route($adminHostPrefix . '.bookings.index') }}">
                    <i class="bi bi-journal-check"></i>
                    <span>Bokningar</span>
                </a>
            @endif

            @if(Route::has($adminHostPrefix . '.bookings.quick-create'))
                <a class="side-link {{ request()->routeIs('admin.bookings.quick-create') || request()->routeIs('host.bookings.quick-create') ? 'active-link' : '' }}"
                   href="{{ route($adminHostPrefix . '.bookings.quick-create') }}">
                    <i class="bi bi-lightning-charge"></i>
                    <span>Snabbbokning</span>
                </a>
            @endif

            @if(Route::has($adminHostPrefix . '.special-tours.index'))
                <a class="side-link {{ request()->routeIs('admin.special-tours.*') || request()->routeIs('host.special-tours.*') ? 'active-link' : '' }}"
                   href="{{ route($adminHostPrefix . '.special-tours.index') }}">
                    <i class="bi bi-stars"></i>
                    <span>Specialturer</span>
                </a>
            @endif

            @if(Route::has('quick-tours.create'))
                <a class="side-link {{ request()->routeIs('quick-tours.*') ? 'active-link' : '' }}"
                   href="{{ route('quick-tours.create') }}">
                    <i class="bi bi-rocket-takeoff"></i>
                    <span>Snabbtur</span>
                </a>
            @endif

            @if($activeRole === \App\Support\Roles::HOST && Route::has('visitor-dogs.create'))
                <a class="side-link {{ request()->routeIs('visitor-dogs.create') ? 'active-link' : '' }}"
                   href="{{ route('visitor-dogs.create') }}">
                    <i class="bi bi-heart-pulse"></i>
                    <span>Besökshund</span>
                </a>
            @endif

            @if(Route::has($adminHostPrefix . '.tours.batch-create'))
                <a class="side-link {{ request()->routeIs('admin.tours.batch-create') || request()->routeIs('host.tours.batch-create') ? 'active-link' : '' }}"
                   href="{{ route($adminHostPrefix . '.tours.batch-create') }}">
                    <i class="bi bi-calendar-plus"></i>
                    <span>Batch skapa turer</span>
                </a>
            @endif
		@if(Route::has($adminHostPrefix . '.work-shifts.staffing'))
		<div class="menu-section">
  <div class="nav-section-title">Vilka jobbar</div>

        <a class="side-link {{ request()->routeIs('admin.work-shifts.staffing') || request()->routeIs('host.work-shifts.staffing') ? 'active-link' : '' }}"
           href="{{ route($adminHostPrefix . '.work-shifts.staffing') }}">
            <i class="bi bi-people-fill"></i>
            <span>Dagens personal</span>
        </a>
	</div>
	@endif
	@if(Route::has($adminHostPrefix . '.work-shifts.index') || Route::has($adminHostPrefix . '.work-shifts.person') || ($activeRole === 'admin' && (Route::has('admin.time.control-panel') || Route::has('admin.time.payroll-locks.index') || Route::has('admin.time.index'))))
		<div class="nav-section">
			<div class="nav-section">
    <div class="nav-section-title">Personal planering</div>

    @if(Route::has($adminHostPrefix . '.work-shifts.index'))
        <a class="side-link {{ request()->routeIs('admin.work-shifts.index') || request()->routeIs('host.work-shifts.index') ? 'active-link' : '' }}"
           href="{{ route($adminHostPrefix . '.work-shifts.index') }}">
            <i class="bi bi-calendar-day"></i>
            <span>Dagvy</span>
        </a>
    @endif

    @if(Route::has($adminHostPrefix . '.work-shifts.person'))
        <a class="side-link {{ request()->routeIs('admin.work-shifts.person') || request()->routeIs('host.work-shifts.person') ? 'active-link' : '' }}"
           href="{{ route($adminHostPrefix . '.work-shifts.person') }}">
            <i class="bi bi-person-lines-fill"></i>
            <span>Personvy</span>
        </a>
    @endif
    @if($activeRole === 'admin')
	@if(Route::has('admin.time.control-panel'))
    <a class="side-link {{ request()->routeIs('admin.time.control-panel') ? 'active-link' : '' }}"
       href="{{ route('admin.time.control-panel') }}">
        <i class="bi bi-clipboard-pulse"></i>
        <span>Tidkontroll</span>
    </a>
@endif

@if(Route::has('admin.time.payroll-locks.index'))
    <a class="side-link {{ request()->routeIs('admin.time.payroll-locks.*') ? 'active-link' : '' }}"
       href="{{ route('admin.time.payroll-locks.index') }}">
        <i class="bi bi-lock"></i>
        <span>Lönelås</span>
    </a>
@endif

@if(Route::has('admin.time.index'))
    <a class="side-link {{ request()->routeIs('admin.time.*') && !request()->routeIs('admin.time.control-panel') && !request()->routeIs('admin.time.payroll-locks.*') ? 'active-link' : '' }}"
       href="{{ route('admin.time.index') }}">
        <i class="bi bi-clock-history"></i>
        <span>Tider/Lön</span>
    </a>
@endif
    @endif
			</div>
		</div>
@endif
        <div class="nav-section">
            <div class="nav-section-title">Statistik</div>

            @if($activeRole === 'admin' && Route::has('admin.statistics.index'))
                <a class="side-link {{ request()->routeIs('admin.statistics.*') && !request()->routeIs('admin.statistics.guides*') ? 'active-link' : '' }}"
                   href="{{ route('admin.statistics.index') }}">
                    <i class="bi bi-bar-chart"></i>
                    <span>Statistik</span>
                </a>
            @endif

            @if($activeRole === 'admin' && Route::has('admin.statistics.guides'))
                <a class="side-link {{ request()->routeIs('admin.statistics.guides*') ? 'active-link' : '' }}"
                   href="{{ route('admin.statistics.guides') }}">
                    <i class="bi bi-person-lines-fill"></i>
                    <span>Guidestatistik</span>
                </a>
            @endif
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Drift och uppföljning</div>

            @if(Route::has($adminHostPrefix . '.restaurant-board'))
                <a class="side-link {{ request()->routeIs('admin.restaurant-board*') || request()->routeIs('host.restaurant-board*') ? 'active-link' : '' }}"
                   href="{{ route($adminHostPrefix . '.restaurant-board') }}">
                    <i class="bi bi-display"></i>
                    <span>Restaurangvy</span>
                </a>
            @endif

            @if(Route::has($adminHostPrefix . '.reports.index'))
                <a class="side-link {{ request()->routeIs('admin.reports.*') || request()->routeIs('host.reports.*') ? 'active-link' : '' }}"
                   href="{{ route($adminHostPrefix . '.reports.index') }}">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span>Felrapporter</span>
                    @if(($newOpenFacilityReportsCount ?? 0) > 0)
                        <span
                            class="sidebar-nav-badge"
                            data-facility-reports-nav-badge="{{ (int) $newOpenFacilityReportsCount }}"
                            aria-label="Antal nya öppna felrapporter sedan listan senast öppnades"
                        >{{ $newOpenFacilityReportsCount > 99 ? '99+' : $newOpenFacilityReportsCount }}</span>
                    @endif
                </a>
            @endif

            @if($activeRole === 'admin' && Route::has('admin.system-messages.index'))
                <a class="side-link {{ request()->routeIs('admin.system-messages.*') ? 'active-link' : '' }}"
                   href="{{ route('admin.system-messages.index') }}">
                    <i class="bi bi-bell"></i>
                    <span>Systemmeddelanden</span>
                </a>
            @endif

            @if($activeRole === 'admin' && Route::has('admin.activity-logs.index'))
                <a class="side-link {{ request()->routeIs('admin.activity-logs.*') ? 'active-link' : '' }}"
                   href="{{ route('admin.activity-logs.index') }}">
                    <i class="bi bi-clock-history"></i>
                    <span>Logg</span>
                </a>
            @endif
        </div>

        @if($activeRole === 'admin')
            <div class="nav-section">
                <div class="nav-section-title">Inställningar och administration</div>
			<a class="side-link {{ request()->routeIs('admin.staff-documents.*') ? 'active-link' : '' }}"
			href="{{ route('admin.staff-documents.index') }}">
			<i class="bi bi-folder2-open"></i>
			<span>Personaldokument</span>
			</a>
                @if(Route::has('admin.settings.index'))
                    <a class="side-link {{ request()->routeIs('admin.settings.index') ? 'active-link' : '' }}"
                       href="{{ route('admin.settings.index') }}">
                        <i class="bi bi-gear"></i>
                        <span>Systeminställningar</span>
                    </a>
                @endif

                @if(Route::has('admin.settings.reports.index'))
                    <a class="side-link {{ request()->routeIs('admin.settings.reports.*') ? 'active-link' : '' }}"
                       href="{{ route('admin.settings.reports.index') }}">
                        <i class="bi bi-sliders"></i>
                        <span>Felrapportinställningar</span>
                    </a>
                @endif

                @if(Route::has('admin.users.index'))
                    <a class="side-link {{ request()->routeIs('admin.users.*') ? 'active-link' : '' }}"
                       href="{{ route('admin.users.index') }}">
                        <i class="bi bi-people"></i>
                        <span>Användare</span>
                    </a>
                @endif

                @if(Route::has('admin.tour-types.index'))
                    <a class="side-link {{ request()->routeIs('admin.tour-types.*') ? 'active-link' : '' }}"
                       href="{{ route('admin.tour-types.index') }}">
                        <i class="bi bi-signpost-2"></i>
                        <span>Turtyper</span>
                    </a>
                @endif

                @if(Route::has('admin.languages.index'))
                    <a class="side-link {{ request()->routeIs('admin.languages.*') ? 'active-link' : '' }}"
                       href="{{ route('admin.languages.index') }}">
                        <i class="bi bi-translate"></i>
                        <span>Språk</span>
                    </a>
                @endif

                @if(Route::has('admin.notification-templates.index'))
                    <a class="side-link {{ request()->routeIs('admin.notification-templates.*') ? 'active-link' : '' }}"
                       href="{{ route('admin.notification-templates.index') }}">
                        <i class="bi bi-envelope-paper"></i>
                        <span>Mailmallar</span>
                    </a>
                @endif
				@if(Route::has('admin.security-overview.index'))
    <a class="side-link {{ request()->routeIs('admin.security-overview.index') ? 'active-link' : '' }}"
       href="{{ route('admin.security-overview.index') }}">
        <i class="bi bi-shield-exclamation"></i>
        <span>Säkerhetsöversikt</span>
    </a>
@endif
						@if(Route::has($adminHostPrefix . '.system-health.index'))
    <a class="side-link {{ request()->routeIs('admin.system-health.index') || request()->routeIs('host.system-health.index') ? 'active-link' : '' }}"
       href="{{ route($adminHostPrefix . '.system-health.index') }}">
        <i class="bi bi-heart-pulse"></i>
        <span>Systemhälsa</span>
    </a>
	@if(Route::has($adminHostPrefix . '.system-logs.index'))
    <a class="side-link {{ request()->routeIs('admin.system-logs.index') || request()->routeIs('host.system-logs.index') ? 'active-link' : '' }}"
       href="{{ route($adminHostPrefix . '.system-logs.index') }}">
        <i class="bi bi-file-earmark-text"></i>
        <span>Systemlogg</span>
    </a>
	@if(Route::has('admin.login-events.index'))
    <a class="side-link {{ request()->routeIs('admin.login-events.index') ? 'active-link' : '' }}"
       href="{{ route('admin.login-events.index') }}">
        <i class="bi bi-shield-lock"></i>
        <span>Inloggningslogg</span>
    </a>
	@if(Route::has('admin.backup-check.index'))
    <a class="side-link {{ request()->routeIs('admin.backup-check.index') ? 'active-link' : '' }}"
       href="{{ route('admin.backup-check.index') }}">
        <i class="bi bi-cloud-check"></i>
        <span>Backup-kontroll</span>
    </a>
@endif
@endif
@endif
            </div>
	
@endif
        @endif
    @endif

    @if($activeRole === 'guide')
        <div class="nav-section">
            <div class="nav-section-title">Guidearbete</div>

            @if(Route::has('guide.dashboard'))
                <a class="side-link {{ request()->routeIs('guide.dashboard') ? 'active-link' : '' }}"
                   href="{{ route('guide.dashboard') }}">
                    <i class="bi bi-speedometer2"></i>
                    <span>Guideöversikt</span>
                </a>
            @endif

            @if(Route::has('guide.reports.create'))
                <a class="side-link {{ request()->routeIs('guide.reports.*') ? 'active-link' : '' }}"
                   href="{{ route('guide.reports.create') }}">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span>Ny felrapport</span>
                </a>
            @endif

            @if(Route::has('quick-tours.create'))
                <a class="side-link {{ request()->routeIs('quick-tours.*') ? 'active-link' : '' }}"
                   href="{{ route('quick-tours.create') }}">
                    <i class="bi bi-rocket-takeoff"></i>
                    <span>Snabbtur</span>
                </a>
            @endif
        </div>
    @endif

    @if($activeRole === 'restaurant')
        <div class="nav-section">
            <div class="nav-section-title">Restaurang</div>

            @if(Route::has('restaurant.dashboard'))
                <a class="side-link {{ request()->routeIs('restaurant.dashboard') ? 'active-link' : '' }}"
                   href="{{ route('restaurant.dashboard') }}">
                    <i class="bi bi-display"></i>
                    <span>Översikt</span>
                </a>
            @endif

            @if(Route::has('restaurant.kiosk'))
                <a class="side-link {{ request()->routeIs('restaurant.kiosk') ? 'active-link' : '' }}"
                   href="{{ route('restaurant.kiosk') }}">
                    <i class="bi bi-tv"></i>
                    <span>Kioskläge</span>
                </a>
            @endif
        </div>
    @endif
</nav>

            <div class="sidebar-footer">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="logout-btn">
                        <i class="bi bi-box-arrow-right me-2"></i>Logga ut
                    </button>
                </form>
            </div>
        </aside>
    @endif

    <main class="main-area">

<!-- Restaurang: alltid mobilrad under lg. Värd: endast i personal-/meddelande-/tidyta (inte bokningsdashboard). -->
@if(session('active_role') === \App\Support\Roles::RESTAURANT || $hostStaffShell)
<style>
@media (max-width: 991.98px) {
    .sidebar,
    aside.sidebar,
    .app-sidebar,
    .topbar,
    header.topbar,
    .navbar.topbar {
        display: none !important;
    }

    .restaurant-mobile-header {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: .85rem;
        margin: 0 0 1rem 0;
        box-shadow: 0 10px 28px rgba(15,23,42,.06);
    }

    .restaurant-mobile-greeting {
        display: flex;
        align-items: center;
        gap: .75rem;
        margin-bottom: .75rem;
    }

    .restaurant-mobile-avatar {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #38bdf8, #2563eb);
        color: #fff;
        font-size: 1.1rem;
        box-shadow: 0 8px 18px rgba(37,99,235,.22);
        flex: 0 0 auto;
    }

    .restaurant-mobile-title {
        font-size: 1rem;
        font-weight: 900;
        line-height: 1.1;
        color: #0f172a;
    }

    .restaurant-mobile-subtitle {
        font-size: .78rem;
        color: #64748b;
        font-weight: 700;
        margin-top: .15rem;
    }

    .restaurant-mobile-nav {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
        align-items: center;
    }

    .restaurant-mobile-btn {
        position: relative;
        width: 42px;
        height: 42px;
        border-radius: 13px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        color: #0f172a !important;
        border: 1px solid #cbd5e1;
        text-decoration: none !important;
        box-shadow: 0 4px 12px rgba(15,23,42,.07);
        font-size: 1.05rem;
        padding: 0;
        cursor: pointer;
    }

    .restaurant-mobile-btn.active,
    .restaurant-mobile-btn:hover {
        background: #eff6ff;
        border-color: #93c5fd;
        color: #1d4ed8 !important;
    }

    .restaurant-mobile-btn-danger {
        color: #b91c1c !important;
        border-color: #fecaca;
    }

    .restaurant-mobile-btn-success {
        color: #047857 !important;
        border-color: #bbf7d0;
        background: #f0fdf4;
    }

    .restaurant-mobile-btn-warning {
        color: #9a3412 !important;
        border-color: #fed7aa;
        background: #fff7ed;
    }

    .restaurant-mobile-badge {
        position: absolute;
        top: -7px;
        right: -7px;
        min-width: 1.2rem;
        height: 1.2rem;
        border-radius: 999px;
        background: #dc2626;
        color: #fff;
        border: 2px solid #fff;
        font-size: .68rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 .25rem;
    }
}
</style>

<div class="restaurant-mobile-header @unless($hostStaffShell) d-lg-none @endunless">
    <div class="restaurant-mobile-greeting">
        <div class="restaurant-mobile-avatar">
            <i class="bi bi-person-badge"></i>
        </div>
        <div>
            <div class="restaurant-mobile-title">
                Hej {{ auth()->user()->name ?? 'Restaurang' }}
            </div>
            <div class="restaurant-mobile-subtitle">
                @if($hostStaffShell)
                    Entrévärd · Personalvy
                @else
                    Restaurang · Personalvy
                @endif
            </div>
        </div>
    </div>

    <div class="restaurant-mobile-nav">
        @if($hostStaffShell && Route::has('host.entry'))
            <a href="{{ route('host.entry') }}"
               class="restaurant-mobile-btn {{ request()->routeIs('host.entry') ? 'active' : '' }}"
               title="Byt arbetsyta"
               aria-label="Byt arbetsyta">
                <i class="bi bi-grid-3x3-gap"></i>
            </a>
        @endif

        @if($hostStaffShell && Route::has('host.visitor-dogs.index'))
            <a href="{{ route('host.visitor-dogs.index') }}"
               class="restaurant-mobile-btn {{ request()->routeIs('host.visitor-dogs.index', 'host.visitor-dogs.show') ? 'active' : '' }}"
               title="Besökshundar"
               aria-label="Besökshundar">
                <i class="bi bi-list-ul"></i>
            </a>
        @endif

        @if($hostStaffShell && Route::has('host.visitor-dogs.gallery'))
            <a href="{{ route('host.visitor-dogs.gallery') }}"
               class="restaurant-mobile-btn {{ request()->routeIs('host.visitor-dogs.gallery') ? 'active' : '' }}"
               title="Hundbilder"
               aria-label="Hundbilder">
                <i class="bi bi-images"></i>
            </a>
        @endif

        @if($hostStaffShell && Route::has('visitor-dogs.create'))
            <a href="{{ route('visitor-dogs.create') }}"
               class="restaurant-mobile-btn {{ request()->routeIs('visitor-dogs.create') ? 'active' : '' }}"
               title="Besökshund"
               aria-label="Besökshund">
                <i class="bi bi-heart-pulse"></i>
            </a>
        @endif

        <a href="{{ route('staff.dashboard') }}"
           class="restaurant-mobile-btn {{ request()->routeIs('staff.*') ? 'active' : '' }}"
           title="Personalvy"
           aria-label="Personalvy">
            <i class="bi bi-house-door"></i>
        </a>

        @if(Route::has('staff.schedule'))
            <a href="{{ route('staff.schedule') }}"
               class="restaurant-mobile-btn {{ request()->routeIs('staff.schedule') ? 'active' : '' }}"
               title="Mitt schema"
               aria-label="Mitt schema">
                <i class="bi bi-calendar-week"></i>
            </a>
        @endif

        @if(Route::has('messages.index'))
            <a href="{{ route('messages.index') }}"
               class="restaurant-mobile-btn {{ request()->routeIs('messages.*') || request()->routeIs('group-chats.*') ? 'active' : '' }}"
               title="Meddelanden"
               aria-label="Meddelanden">
                <i class="bi bi-chat-dots-fill"></i>
                @if(($unreadMessagesCount ?? $unreadConversationsCount ?? 0) > 0)
                    <span class="restaurant-mobile-badge">{{ $unreadMessagesCount ?? $unreadConversationsCount }}</span>
                @endif
            </a>
        @endif

        <a href="#system-messages-panel"
           class="restaurant-mobile-btn"
           title="Systemmeddelanden"
           aria-label="Systemmeddelanden">
            <i class="bi bi-bell-fill"></i>
            @if(($unreadSystemMessagesCount ?? 0) > 0)
                <span class="restaurant-mobile-badge">{{ $unreadSystemMessagesCount }}</span>
            @endif
        </a>

        @if(Route::has('time.clock-out') && ($openTimeEntryForHeader ?? null))
            <form method="POST" action="{{ route('time.clock-out') }}" style="margin:0;" data-offline-queue>
                @csrf
                <button type="submit"
                        class="restaurant-mobile-btn restaurant-mobile-btn-warning"
                        title="Stämpla ut"
                        aria-label="Stämpla ut">
                    <i class="bi bi-stop-circle-fill"></i>
                </button>
            </form>
        @elseif(Route::has('time.clock-in'))
            <form method="POST" action="{{ route('time.clock-in') }}" style="margin:0;" data-offline-queue>
                @csrf
                <button type="submit"
                        class="restaurant-mobile-btn restaurant-mobile-btn-success"
                        title="Stämpla in"
                        aria-label="Stämpla in">
                    <i class="bi bi-play-circle-fill"></i>
                </button>
            </form>
        @endif

        @if(Route::has('time.index'))
            <a href="{{ route('time.index') }}"
               class="restaurant-mobile-btn {{ request()->routeIs('time.*') ? 'active' : '' }}"
               title="Tidrapportering"
               aria-label="Tidrapportering">
                <i class="bi bi-clock-history"></i>
            </a>
        @endif

        @if(Route::has('staff.documents.index'))
            <a href="{{ route('staff.documents.index') }}"
               class="restaurant-mobile-btn {{ request()->routeIs('staff.documents.*') ? 'active' : '' }}"
               title="Dokument"
               aria-label="Dokument">
                <i class="bi bi-folder2-open"></i>
            </a>
        @endif

        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
            @csrf
            <button type="submit"
                    class="restaurant-mobile-btn restaurant-mobile-btn-danger"
                    title="Logga ut"
                    aria-label="Logga ut">
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </form>
    </div>
</div>
@endif
<!-- Restaurangmobilnav / värd personalmobilnav END -->


<div class="content-wrap">
            @if($user && ! $hostStaffShell)
                <div class="topbar">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h1 class="topbar-title">Hej {{ $user->name }}</h1>
                            <p class="topbar-subtitle">
                                Administrera bokningar, turer, guider och rapporter i ett sammanhållet gränssnitt.
                            </p>
                        </div>

                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            @if(Route::has($adminHostPrefix . '.dashboard') && auth()->check() && in_array($activeRole, ['admin', 'host'], true))
        <a href="{{ route($adminHostPrefix . '.dashboard') }}"
                               class="topbar-link-chip {{ request()->routeIs('admin.dashboard') || request()->routeIs('host.dashboard') ? 'topbar-link-chip-active' : '' }}">
                                <i class="bi bi-speedometer2"></i>
                                <span>Dashboard</span>
                            </a>
		<a href="{{ route('time.index') }}"
                               class="topbar-link-chip {{ request()->routeIs('time.*') ? 'topbar-link-chip-active' : '' }}">
                                <i class="bi bi-clock-history"></i>
                                <span>Tidrapportering</span>
                            </a>
    @elseif($activeRole === 'guide' && Route::has('guide.dashboard'))
        <a href="{{ route('guide.dashboard') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-speedometer2 me-2"></i>Dashboard
        </a><a href="{{ route('time.index') }}"
                               class="topbar-link-chip {{ request()->routeIs('time.*') ? 'topbar-link-chip-active' : '' }}">
                                <i class="bi bi-clock-history"></i>
                                <span>Tidrapportering</span>
                            </a>
    @elseif($activeRole === 'restaurant' && Route::has('restaurant.dashboard'))
        <a href="{{ route('restaurant.dashboard') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-speedometer2 me-2"></i>Dashboard
        </a><a href="{{ route('time.index') }}"
                               class="topbar-link-chip {{ request()->routeIs('time.*') ? 'topbar-link-chip-active' : '' }}">
                                <i class="bi bi-clock-history"></i>
                                <span>Tidrapportering</span>
                            </a>
    @endif
@if(Route::has('staff.documents.index'))
    <a href="{{ route('staff.documents.index') }}"
       class="topbar-link-chip {{ request()->routeIs('staff.documents.*') ? 'topbar-link-chip-active' : '' }}">
        <i class="bi bi-folder2-open"></i>
        <span>Dokument</span>
    </a>
@endif
                            @if(Route::has('messages.index') && auth()->check())
                                <a href="{{ route('messages.index') }}"
                                   class="topbar-link-chip {{ request()->routeIs('messages.*') || request()->routeIs('group-chats.*') ? 'topbar-link-chip-active' : '' }}">
                                    <i class="bi bi-chat-dots-fill"></i>
                                    <span>Meddelanden</span>
                                    @if($unreadConversationsCount > 0)
                                        <span class="topbar-count-badge">{{ $unreadConversationsCount }}</span>
                                    @endif
                                </a>
                            @endif
@if(Route::has('staff.schedule') && auth()->check())
    <a href="{{ route('staff.schedule') }}"
       class="topbar-link-chip {{ request()->routeIs('staff.schedule') ? 'topbar-link-chip-active' : '' }}">
        <i class="bi bi-calendar-week"></i>
        <span>Mitt schema</span>
    </a>
@endif
                            <a href="#system-messages-panel" class="guide-notice-chip">
    <i class="bi bi-bell-fill"></i>
    <span>{{ $unreadSystemMessagesCount }}</span>
</a>

                            <div class="badge-soft badge-soft-success">
                                <i class="bi bi-person-badge"></i>
                                {{ ucfirst($user->role) }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="flash-stack">
                @if(session('success'))
                    <div class="flash-message flash-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="flash-message flash-error">
                        {{ $errors->first() }}
                    </div>
                @endif
            </div>

            @if($activeSystemMessages->isNotEmpty())
                <div class="flash-stack mb-3">
                    @foreach($activeSystemMessages as $message)
                        @php
                            $pivotUser = $message->users->first();
                            $isUnread = !$pivotUser || empty($pivotUser->pivot?->read_at);
                            $isAcked = $pivotUser && !empty($pivotUser->pivot?->acknowledged_at);
                        @endphp

                        @if(!$message->popup_only)
                            <div class="system-message-banner {{ $message->priority === 3 ? 'system-message-important' : 'system-message-normal' }}">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div class="flex-grow-1">
                                        <div class="system-message-title">
                                            @if($message->priority === 3)
                                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                            @else
                                                <i class="bi bi-megaphone-fill me-2"></i>
                                            @endif

                                            {{ $message->title }}

                                            @if($isUnread)
                                                <span class="badge-soft badge-soft-warning ms-2">Oläst</span>
                                            @endif

                                            @if($message->requires_ack && !$isAcked)
                                                <span class="badge-soft badge-soft-danger ms-2">Kräver kvittering</span>
                                            @endif
                                        </div>

                                        @if($message->body)
                                            <div class="system-message-body">
                                                {!! nl2br(e($message->body)) !!}
                                            </div>
                                        @endif
                                    </div>

                                    <div class="toolbar-inline">
                                        @if($activeSystemMessages->isNotEmpty())
    <div id="system-messages-panel" class="flash-stack mb-3">
                                            <form method="POST" action="{{ route('system-messages.read', $message) }}" data-offline-queue>
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">Markera läst</button>
                                            </form>
                                        @endif

                                        @if($message->requires_ack && !$isAcked && Route::has('system-messages.acknowledge'))
                                            <form method="POST" action="{{ route('system-messages.acknowledge', $message) }}" data-offline-queue>
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary">Kvittera</button>
                                            </form>
                                        @endif

                                        @if(Route::has('system-messages.dismiss'))
                                            <form method="POST" action="{{ route('system-messages.dismiss', $message) }}" data-offline-queue>
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">Stäng</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
@include('partials.open-time-entry-warning')
            @yield('content')
        </div>
    </main>
</div>

@if(auth()->check() && Route::has('system-messages.live-panel'))
<script>
    (function () {
        let lastUnreadCount = {{ (int) $unreadSystemMessagesCount }};

        async function refreshSystemMessagePanel() {
            try {
                const response = await fetch('{{ route('system-messages.live-panel') }}', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });

                if (!response.ok) return;

                const data = await response.json();

                const badge = document.querySelector('.topbar-notice-chip span');
                if (badge) {
                    badge.textContent = data.unread_count ?? 0;
                }

                const currentUnread = Number(data.unread_count ?? 0);

                if (Array.isArray(data.important_unread) && data.important_unread.length > 0) {
                    const latestForced = data.important_unread.find(item => item.priority === 3 && item.requires_ack);

                    if (latestForced) {
                        showLiveSystemToast(latestForced.title, latestForced.body || '', true);
                    } else if (currentUnread > lastUnreadCount) {
                        const newest = data.important_unread[0];
                        if (newest && newest.title) {
                            showLiveSystemToast(newest.title, newest.body || '', !!newest.requires_ack);
                        }
                    }
                }

                lastUnreadCount = currentUnread;
            } catch (e) {
            }
        }

        function showLiveSystemToast(title, body, requiresAck) {
            const old = document.getElementById('live-system-toast');
            if (old) old.remove();

            const toast = document.createElement('div');
            toast.id = 'live-system-toast';
            toast.innerHTML = `
                <div style="
                    position: fixed;
                    right: 24px;
                    bottom: 24px;
                    width: 360px;
                    max-width: calc(100vw - 32px);
                    background: #fff7ed;
                    color: #9a3412;
                    border: 1px solid #fdba74;
                    border-radius: 16px;
                    box-shadow: 0 18px 40px rgba(15,23,42,0.15);
                    padding: 16px 18px;
                    z-index: 9999;
                ">
                    <div style="font-weight:800; margin-bottom:6px;">
                        <i class="bi bi-bell-fill" style="margin-right:8px;"></i>${escapeHtml(title)}
                    </div>
                    <div style="font-size:0.92rem; line-height:1.45; margin-bottom:${requiresAck ? '8px' : '0'};">
                        ${escapeHtml(body)}
                    </div>
                    ${requiresAck ? '<div style="font-size:0.8rem; font-weight:700;">Kräver kvittering i systemet.</div>' : ''}
                </div>
            `;

            document.body.appendChild(toast);

            setTimeout(() => {
                const current = document.getElementById('live-system-toast');
                if (current) current.remove();
            }, 8000);
        }

        function escapeHtml(text) {
            return String(text || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        setInterval(refreshSystemMessagePanel, 30000);
    })();
</script>
@endif

@if(auth()->check() && Route::has('system-messages.force-popup-panel'))
<script>
    (function () {
        async function loadForcedPopups() {
            try {
                const response = await fetch('{{ route('system-messages.force-popup-panel') }}', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });

                if (!response.ok) return;

                const data = await response.json();
                const messages = data.messages || [];

                if (!messages.length) return;

                const first = messages[0];
                showForcedSystemModal(first);
            } catch (e) {
            }
        }

        function showForcedSystemModal(message) {
            if (document.getElementById('forced-system-modal')) return;

            const modal = document.createElement('div');
            modal.id = 'forced-system-modal';
            modal.innerHTML = `
                <div style="
                    position: fixed;
                    inset: 0;
                    background: rgba(15, 23, 42, 0.55);
                    z-index: 9999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                ">
                    <div style="
                        width: 100%;
                        max-width: 640px;
                        background: #fff;
                        border-radius: 20px;
                        padding: 24px;
                        box-shadow: 0 24px 60px rgba(15,23,42,0.25);
                    ">
                        <div style="font-size: 1.2rem; font-weight: 800; margin-bottom: 10px;">
                            ${escapeHtml(message.title)}
                        </div>
                        <div style="color: #334155; line-height: 1.6; margin-bottom: 20px; white-space: pre-line;">
                            ${escapeHtml(message.body || '')}
                        </div>
                        <div style="display:flex; gap:10px; justify-content:flex-end;">
                            <button id="forced-system-read" style="
                                border:none;
                                background:#e2e8f0;
                                color:#0f172a;
                                padding:10px 14px;
                                border-radius:12px;
                                font-weight:700;
                                cursor:pointer;
                            ">Markera läst</button>
                            ${message.requires_ack ? `
                                <form method="POST" action="/system-messages/${message.id}/acknowledge" style="margin:0;">
                                    <input type="hidden" name="_token" value="${csrfToken()}">
                                    <button type="submit" style="
                                        border:none;
                                        background:linear-gradient(135deg,#38bdf8,#2563eb);
                                        color:#fff;
                                        padding:10px 14px;
                                        border-radius:12px;
                                        font-weight:700;
                                        cursor:pointer;
                                    ">Kvittera</button>
                                </form>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            const readBtn = document.getElementById('forced-system-read');
            if (readBtn) {
                readBtn.addEventListener('click', async function () {
                    await fetch(`/system-messages/${message.id}/read`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin'
                    });

                    modal.remove();
                });
            }
        }

        function csrfToken() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') : '';
        }

        function escapeHtml(text) {
            return String(text || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        loadForcedPopups();
    })();
</script>
@endif
<script>
document.addEventListener('DOMContentLoaded', function () {
    async function refreshSystemMessagesPanel() {
        try {
            const response = await fetch('{{ route('system-messages.live-panel') }}', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            });

            if (!response.ok) return;

            const data = await response.json();

            const badge = document.querySelector('[data-system-message-count]');
            if (badge) {
                badge.textContent = data.unread_count ?? 0;
                badge.style.display = (data.unread_count ?? 0) > 0 ? '' : 'none';
            }
        } catch (error) {
            console.error('Systemmeddelanden kunde inte uppdateras', error);
        }
    }

    refreshSystemMessagesPanel();
    setInterval(refreshSystemMessagesPanel, 30000);
});
</script>
</body>
</html>