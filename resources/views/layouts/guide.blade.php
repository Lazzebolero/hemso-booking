<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Hemsö') }} – Guide</title>

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

            --content-max: 1080px;
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

        .guide-shell {
            min-height: 100vh;
        }

        .guide-header {
            position: sticky;
            top: 0;
            z-index: 60;
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(226,232,240,0.9);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
        }

        .guide-header-inner {
            width: 100%;
            max-width: var(--content-max);
            margin: 0 auto;
            padding: 0.95rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .guide-brand {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .guide-brand-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #38bdf8, #2563eb);
            color: #fff;
            font-size: 1.05rem;
            box-shadow: var(--shadow-card);
        }

        .guide-brand-title {
            margin: 0;
            font-size: 1.08rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.1;
        }

        .guide-brand-subtitle {
            margin: 0.18rem 0 0;
            color: var(--text-soft);
            font-size: 0.85rem;
            line-height: 1.35;
        }

        .guide-header-actions {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .guide-chip,
        .guide-notice-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.42rem;
            border-radius: 999px;
            padding: 0.5rem 0.78rem;
            font-size: 0.8rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .guide-chip {
            background: rgba(5, 150, 105, 0.12);
            color: #047857;
        }

        .guide-notice-chip {
            background: #fff7ed;
            color: #9a3412;
            border: 1px solid #fdba74;
        }

        .guide-topbar-link-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.5rem 0.78rem;
            border-radius: 999px;
            background: #fff;
            color: #334155;
            border: 1px solid #dbe3ee;
            font-weight: 800;
            font-size: 0.82rem;
            text-decoration: none;
            transition: all 0.18s ease;
        }

        .guide-topbar-link-chip:hover {
            background: #f8fafc;
            color: #0f172a;
            border-color: #cbd5e1;
        }

        .guide-topbar-link-chip-active {
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.14), rgba(37, 99, 235, 0.16));
            border-color: rgba(37, 99, 235, 0.22);
            color: #0f172a;
        }

        .guide-topbar-count-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.3rem;
            height: 1.3rem;
            padding: 0 0.35rem;
            border-radius: 999px;
            background: #2563eb;
            color: #fff;
            font-size: 0.72rem;
            font-weight: 800;
            line-height: 1;
        }

        .guide-main {
            min-width: 0;
            padding: 1rem 1rem 2rem;
        }

        .guide-content {
            width: 100%;
            max-width: var(--content-max);
            margin: 0 auto;
        }

        .page-card {
            background: var(--surface);
            border: 1px solid var(--brand-line-soft);
            border-radius: 18px;
            padding: 1rem 1.1rem;
            box-shadow: var(--shadow-card);
        }

        .page-card + .page-card {
            margin-top: 1rem;
        }

        .compact-card {
            padding: 0.85rem 0.95rem;
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

        .table-responsive-modern {
            width: 100%;
            overflow-x: auto;
        }

        .table-modern {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 680px;
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

        .guide-focus-card {
            background: rgba(255,255,255,0.96);
            border-radius: 22px;
            box-shadow: var(--shadow-soft);
            border: 1px solid rgba(255,255,255,0.75);
            padding: 1rem;
        }

        .guide-focus-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.8rem;
            margin-bottom: 1rem;
        }

        .guide-focus-title {
            font-size: 1.2rem;
            font-weight: 800;
            margin-bottom: 0.35rem;
            line-height: 1.2;
        }

        .guide-focus-meta {
            display: grid;
            gap: 0.2rem;
            color: var(--text-soft);
            font-size: 0.95rem;
        }

        .guide-chip-row {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .guide-focus-stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .guide-stat-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 0.85rem;
        }

        .guide-stat-label {
            font-size: 0.82rem;
            color: var(--text-soft);
            margin-bottom: 0.25rem;
        }

        .guide-stat-value {
            font-size: 1.15rem;
            font-weight: 700;
        }

        .guide-primary-actions {
            display: grid;
            gap: 0.75rem;
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


        .guide-mobile-action {
            position: relative;
            width: 42px;
            height: 42px;
            border-radius: 13px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.07);
            text-decoration: none;
            font-size: 1.05rem;
            cursor: pointer;
            transition: all 0.16s ease;
            padding: 0;
            font-family: inherit;
        }

        .guide-mobile-action:hover,
        .guide-mobile-action-active {
            background: #eff6ff;
            color: #1d4ed8;
            border-color: #93c5fd;
        }

        .guide-mobile-action-primary {
            background: linear-gradient(135deg, #38bdf8, #2563eb);
            color: #ffffff;
            border-color: transparent;
        }

        .guide-mobile-action-primary:hover {
            background: linear-gradient(135deg, #0ea5e9, #1d4ed8);
            color: #ffffff;
        }

        .guide-mobile-action-success {
            background: linear-gradient(135deg, #34d399, #059669);
            color: #ffffff;
            border-color: transparent;
        }

        .guide-mobile-action-warning {
            background: #fff7ed;
            color: #9a3412;
            border-color: #fdba74;
        }

        .guide-mobile-action-danger {
            color: #b91c1c;
            border-color: #fecaca;
            background: #ffffff;
        }

        .guide-mobile-action-danger:hover {
            background: #fef2f2;
            color: #991b1b;
        }

        .guide-mobile-badge {
            position: absolute;
            top: -7px;
            right: -7px;
            min-width: 1.25rem;
            height: 1.25rem;
            padding: 0 0.32rem;
            border-radius: 999px;
            background: #dc2626;
            color: #ffffff;
            border: 2px solid #ffffff;
            font-size: 0.68rem;
            font-weight: 900;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .guide-mobile-role-chip {
            height: 42px;
            display: inline-flex;
            align-items: center;
            gap: 0.42rem;
            border-radius: 13px;
            padding: 0 0.7rem;
            background: rgba(5, 150, 105, 0.12);
            color: #047857;
            border: 1px solid rgba(5, 150, 105, 0.14);
            font-size: 0.8rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .guide-header-actions form {
            margin: 0;
        }

        .guide-header-actions button.guide-mobile-action {
            padding: 0;
            font-family: inherit;
        }

        .d-none {
            display: none !important;
        }

        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .guide-header-inner {
                align-items: flex-start;
                flex-direction: column;
            }

            .guide-header-actions {
                width: 100%;
                justify-content: space-between;
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

        @media (max-width: 700px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .guide-main {
                padding: 0.9rem 0.85rem 2rem;
            }

            .table-modern {
                min-width: 640px;
            }

            .guide-focus-top {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
@include('partials.pwa-offline-banner')
@php
    $user = auth()->user();

    $activeSystemMessages = collect();
    $unreadSystemMessagesCount = 0;
    $unreadConversationsCount = 0;

    if (auth()->check() && class_exists(\App\Models\SystemMessage::class) && \Illuminate\Support\Facades\Schema::hasTable('system_messages')) {
        $activeSystemMessages = \App\Models\SystemMessage::query()
            ->visibleNow()
            ->forRole($user->role ?? null)
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

    $isRestaurantRole = auth()->check()
        && (
            (int) ($user->role_id ?? 0) === 4
            || strtolower((string) ($user->role ?? '')) === 'restaurant'
            || strtolower((string) ($user->role ?? '')) === 'restaurang'
        );

    $isGuideRole = auth()->check()
        && (
            strtolower((string) ($user->role ?? '')) === 'guide'
            || (int) ($user->role_id ?? 0) === 3
        );

@endphp

<div class="guide-shell">
    <header class="guide-header">
        <div class="guide-header-inner">
            <div class="guide-brand">
                <div class="guide-brand-icon">
                    <i class="bi bi-compass"></i>
                </div>

                <div>
                    <h1 class="guide-brand-title">{{ $isRestaurantRole ? 'Personalvy' : 'Guidevy' }}</h1>
                    <p class="guide-brand-subtitle">
                        {{ $isRestaurantRole ? 'Hemsö – personal, schema och driftinformation' : 'Hemsö – dagens turer och driftinformation' }}
                    </p>
                </div>
            </div>

            <div class="guide-header-actions">
                @if(Route::has('guide.dashboard'))
                    <a href="{{ route('guide.dashboard') }}"
                       class="guide-mobile-action {{ request()->routeIs('guide.dashboard') ? 'guide-mobile-action-active' : '' }}"
                       title="Hem"
                       aria-label="Hem">
                        <i class="bi bi-house-door"></i>
                    </a>
                @endif

                @if(Route::has('my-schedule.index'))
                    <a href="{{ route('my-schedule.index') }}"
                       class="guide-mobile-action {{ request()->routeIs('my-schedule.*') ? 'guide-mobile-action-active' : '' }}"
                       title="Mitt schema"
                       aria-label="Mitt schema">
                        <i class="bi bi-calendar-week"></i>
                    </a>
                @endif

                @if(Route::has('messages.index'))
                    <a href="{{ route('messages.index') }}"
                       class="guide-mobile-action {{ request()->routeIs('messages.*') || request()->routeIs('group-chats.*') ? 'guide-mobile-action-active' : '' }}"
                       title="Meddelanden"
                       aria-label="Meddelanden">
                        <i class="bi bi-chat-dots-fill"></i>
                        @if(($unreadConversationsCount ?? $unreadMessagesCount ?? 0) > 0)
                            <span class="guide-mobile-badge">{{ $unreadConversationsCount ?? $unreadMessagesCount }}</span>
                        @endif
                    </a>
                @endif

                <a href="#system-messages-panel"
                   class="guide-mobile-action"
                   title="Systemmeddelanden"
                   aria-label="Systemmeddelanden">
                    <i class="bi bi-bell-fill"></i>
                    @if(($unreadSystemMessagesCount ?? 0) > 0)
                        <span class="guide-mobile-badge">{{ $unreadSystemMessagesCount }}</span>
                    @endif
                </a>

                @if(Route::has('time.clock-out') && ($openTimeEntryForHeader ?? null))
                    <form method="POST" action="{{ route('time.clock-out') }}" data-offline-queue>
                        @csrf
                        <button type="submit"
                                class="guide-mobile-action guide-mobile-action-warning"
                                title="Stämpla ut"
                                aria-label="Stämpla ut">
                            <i class="bi bi-stop-circle-fill"></i>
                        </button>
                    </form>
                @elseif(Route::has('time.clock-in'))
                    <form method="POST" action="{{ route('time.clock-in') }}" data-offline-queue>
                        @csrf
                        <button type="submit"
                                class="guide-mobile-action guide-mobile-action-success"
                                title="Stämpla in"
                                aria-label="Stämpla in">
                            <i class="bi bi-play-circle-fill"></i>
                        </button>
                    </form>
                @endif

                @if(Route::has('time.index'))
                    <a href="{{ route('time.index') }}"
                       class="guide-mobile-action {{ request()->routeIs('time.*') ? 'guide-mobile-action-active' : '' }}"
                       title="Tidrapportering"
                       aria-label="Tidrapportering">
                        <i class="bi bi-clock-history"></i>
                    </a>
                @endif

                @if(Route::has('guide.reports.create'))
                    <a href="{{ route('guide.reports.create') }}"
                       class="guide-mobile-action {{ request()->routeIs('guide.reports.*') ? 'guide-mobile-action-active' : '' }}"
                       title="Felrapport"
                       aria-label="Felrapport">
                        <i class="bi bi-tools"></i>
                    </a>
                @endif

                @if(Route::has('visitor-dogs.index'))
                    <a href="{{ route('visitor-dogs.index') }}"
                       class="guide-mobile-action {{ request()->routeIs('visitor-dogs.index', 'visitor-dogs.show', 'visitor-dogs.edit') ? 'guide-mobile-action-active' : '' }}"
                       title="Mina hundar"
                       aria-label="Mina hundar">
                        <i class="bi bi-list-ul"></i>
                    </a>
                @endif

                @if(Route::has('visitor-dogs.create'))
                    <a href="{{ route('visitor-dogs.create') }}"
                       class="guide-mobile-action {{ request()->routeIs('visitor-dogs.create') ? 'guide-mobile-action-active' : '' }}"
                       title="Registrera hund"
                       aria-label="Registrera hund">
                        <i class="bi bi-heart-pulse"></i>
                    </a>
                @endif

                @if(Route::has('quick-tours.create'))
                    <a href="{{ route('quick-tours.create') }}"
                       class="guide-mobile-action guide-mobile-action-primary"
                       title="Snabbtur"
                       aria-label="Snabbtur">
                        <i class="bi bi-lightning-charge-fill"></i>
                    </a>
                @endif
                @if(Route::has('staff.documents.index'))
                    <a href="{{ route('staff.documents.index') }}"
                       class="guide-mobile-action {{ request()->routeIs('staff.documents.*') ? 'guide-mobile-action-active' : '' }}"
                       title="Dokument"
                       aria-label="Dokument">
                        <i class="bi bi-folder2-open"></i>
                    </a>
                @endif


                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="guide-mobile-action guide-mobile-action-danger"
                            title="Logga ut"
                            aria-label="Logga ut">
                        <i class="bi bi-box-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="guide-main">
        <div class="guide-content">
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
                <div id="system-messages-panel" class="flash-stack mb-3">
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
                                        @if($isUnread && Route::has('system-messages.read'))
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

                const badge = document.querySelector('[data-system-message-count]');
                if (badge) {
                    const unread = Number(data.unread_count ?? 0);
                    badge.textContent = unread;
                    badge.classList.toggle('d-none', unread <= 0);
                    badge.style.display = unread > 0 ? 'inline-flex' : 'none';
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
@auth
@php
    $pwaWarmUrls = collect([
        Route::has('guide.dashboard') ? route('guide.dashboard') : null,
        Route::has('quick-tours.create') ? route('quick-tours.create') : null,
    ])->filter()->values()->all();
@endphp
@if(count($pwaWarmUrls))
<script>
    (function () {
        var urls = @json($pwaWarmUrls);
        var MIN_GAP_MS = 45 * 1000;
        var INTERVAL_MS = 4 * 60 * 1000;
        var lastWarm = 0;

        function warmGuideShellCache() {
            if (!navigator.onLine || !('serviceWorker' in navigator)) {
                return;
            }

            var now = Date.now();
            if (now - lastWarm < MIN_GAP_MS) {
                return;
            }

            lastWarm = now;

            urls.forEach(function (url) {
                fetch(url, {credentials: 'same-origin'}).catch(function () {});
            });
        }

        warmGuideShellCache();
        window.setInterval(warmGuideShellCache, INTERVAL_MS);
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') {
                warmGuideShellCache();
            }
        });
    })();
</script>
@endif
@endauth
</body>
</html>