<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Välj arbetsyta · Värd</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            margin: 0;
            background:
                radial-gradient(circle at top left, rgba(56, 189, 248, .18), transparent 32rem),
                linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: #0f172a;
        }

        .host-entry-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
        }

        .host-entry-card {
            width: 100%;
            max-width: 560px;
            background: rgba(255, 255, 255, .96);
            border: 1px solid rgba(226, 232, 240, .9);
            border-radius: 24px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, .12);
            padding: 1.25rem;
        }

        .host-entry-header {
            display: flex;
            align-items: center;
            gap: .85rem;
            margin-bottom: 1.25rem;
        }

        .host-entry-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #38bdf8, #2563eb);
            color: #fff;
            font-size: 1.3rem;
            box-shadow: 0 10px 24px rgba(37, 99, 235, .24);
            flex: 0 0 auto;
        }

        .host-entry-title {
            font-size: 1.25rem;
            font-weight: 900;
            line-height: 1.1;
            margin: 0;
        }

        .host-entry-subtitle {
            margin: .2rem 0 0;
            color: #64748b;
            font-size: .92rem;
        }

        .host-entry-list {
            display: grid;
            gap: .75rem;
        }

        .host-entry-link {
            width: 100%;
            border: 1px solid #cbd5e1;
            background: #fff;
            border-radius: 18px;
            padding: 1rem;
            text-align: left;
            display: flex;
            align-items: center;
            gap: .85rem;
            color: #0f172a;
            box-shadow: 0 8px 18px rgba(15, 23, 42, .06);
            transition: all .16s ease;
            text-decoration: none;
        }

        .host-entry-link:hover {
            border-color: #93c5fd;
            background: #eff6ff;
            transform: translateY(-1px);
            color: #0f172a;
        }

        .host-entry-link-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            color: #1d4ed8;
            font-size: 1.1rem;
            flex: 0 0 auto;
        }

        .host-entry-link-title {
            font-weight: 850;
            font-size: 1rem;
        }

        .host-entry-link-desc {
            color: #64748b;
            font-size: .86rem;
            margin-top: .1rem;
        }

        .host-entry-logout {
            width: 100%;
            margin-top: 1rem;
            border-radius: 16px;
            min-height: 46px;
            font-weight: 800;
        }
    </style>
</head>
<body>
<main class="host-entry-page">
    <section class="host-entry-card">
        <div class="host-entry-header">
            <div class="host-entry-icon">
                <i class="bi bi-person-workspace"></i>
            </div>
            <div>
                <h1 class="host-entry-title">Hej {{ auth()->user()->name ?? '' }}</h1>
                <p class="host-entry-subtitle">Välj hur du vill arbeta som värd.</p>
            </div>
        </div>

        <div class="host-entry-list">
            @if(Route::has('host.dashboard'))
                <a href="{{ route('host.dashboard') }}" class="host-entry-link">
                    <span class="host-entry-link-icon">
                        <i class="bi bi-columns-gap"></i>
                    </span>
                    <span>
                        <span class="host-entry-link-title">Bokning och turer</span>
                        <span class="host-entry-link-desc d-block">Vanlig dashboard med sidomeny — turer, bokningar och drift. Ingen mobil personalnav här.</span>
                    </span>
                </a>
            @endif

            @if(Route::has('staff.dashboard'))
                <a href="{{ route('staff.dashboard') }}" class="host-entry-link">
                    <span class="host-entry-link-icon">
                        <i class="bi bi-phone"></i>
                    </span>
                    <span>
                        <span class="host-entry-link-title">Mobil personalvy</span>
                        <span class="host-entry-link-desc d-block">Som restaurangpersonal: pass, schema, dokument, meddelanden och tid — utan att administrera turer eller bokningar.</span>
                    </span>
                </a>
            @endif
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-danger host-entry-logout">
                <i class="bi bi-box-arrow-right me-2"></i>Logga ut
            </button>
        </form>
    </section>
</main>
</body>
</html>
