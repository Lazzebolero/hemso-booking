<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Välj roll</title>

    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="theme-color" content="#0f172a">

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

        .role-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
        }

        .role-card {
            width: 100%;
            max-width: 560px;
            background: rgba(255, 255, 255, .96);
            border: 1px solid rgba(226, 232, 240, .9);
            border-radius: 24px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, .12);
            padding: 1.25rem;
        }

        .role-header {
            display: flex;
            align-items: center;
            gap: .85rem;
            margin-bottom: 1.25rem;
        }

        .role-icon {
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

        .role-title {
            font-size: 1.25rem;
            font-weight: 900;
            line-height: 1.1;
            margin: 0;
        }

        .role-subtitle {
            margin: .2rem 0 0;
            color: #64748b;
            font-size: .92rem;
        }

        .role-list {
            display: grid;
            gap: .75rem;
        }

        .role-button {
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
        }

        .role-button:hover {
            border-color: #93c5fd;
            background: #eff6ff;
            transform: translateY(-1px);
        }

        .role-button-icon {
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

        .role-name {
            font-weight: 850;
            font-size: 1rem;
        }

        .role-description {
            color: #64748b;
            font-size: .86rem;
            margin-top: .1rem;
        }

        .logout-button {
            width: 100%;
            margin-top: 1rem;
            border-radius: 16px;
            min-height: 46px;
            font-weight: 800;
        }
    </style>
</head>
<body>
    <main class="role-page">
        <section class="role-card">
            <div class="role-header">
                <div class="role-icon">
                    <i class="bi bi-person-badge"></i>
                </div>
                <div>
                    <h1 class="role-title">Hej {{ auth()->user()->name ?? '' }}</h1>
                    <p class="role-subtitle">Välj vilken roll du vill arbeta som.</p>
                </div>
            </div>

            <div class="role-list">
                @foreach($availableRoles as $role)
                    <form method="POST" action="{{ route('role.store') }}">
                        @csrf
                        <input type="hidden" name="role" value="{{ $role }}">

                        <button type="submit" class="role-button">
                            <span class="role-button-icon">
                                @switch($role)
                                    @case(\App\Support\Roles::ADMIN)
                                        <i class="bi bi-speedometer2"></i>
                                        @break

                                    @case(\App\Support\Roles::HOST)
                                        <i class="bi bi-person-workspace"></i>
                                        @break

                                    @case(\App\Support\Roles::GUIDE)
                                        <i class="bi bi-signpost-2"></i>
                                        @break

                                    @case(\App\Support\Roles::RESTAURANT)
                                        <i class="bi bi-cup-hot"></i>
                                        @break

                                    @case(\App\Support\Roles::RESTAURANT_STATISTIK)
                                        <i class="bi bi-bar-chart-line"></i>
                                        @break

                                    @default
                                        <i class="bi bi-person"></i>
                                @endswitch
                            </span>

                            <span>
                                <span class="role-name">{{ $labels[$role] ?? ucfirst($role) }}</span>
                                <span class="role-description d-block">
                                    {{ $descriptions[$role] ?? 'Öppna arbetsytan för denna roll.' }}
                                </span>
                            </span>
                        </button>
                    </form>
                @endforeach
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-danger logout-button">
                    <i class="bi bi-box-arrow-right me-2"></i>Logga ut
                </button>
            </form>
        </section>
    </main>
</body>
</html>
