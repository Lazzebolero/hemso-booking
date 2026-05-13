<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restauranginloggning</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    <style>
        :root {
            --text-main: #0f172a;
            --text-soft: #64748b;
            --surface: #ffffff;
            --line: #dbe6f1;
            --accent: #2563eb;
            --shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Figtree', sans-serif;
            background: linear-gradient(180deg, #f8fafc 0%, #eaf0ff 100%);
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-card {
            width: 100%;
            max-width: 460px;
            background: rgba(255,255,255,0.98);
            border: 1px solid var(--line);
            border-radius: 24px;
            padding: 28px;
            box-shadow: var(--shadow);
        }

        .login-title {
            margin: 0 0 6px;
            font-size: 1.7rem;
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .login-subtitle {
            margin: 0 0 22px;
            color: var(--text-soft);
            line-height: 1.5;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            min-height: 48px;
            border-radius: 14px;
            border: 1px solid #cfd8e3;
            padding: 12px 14px;
            font-size: 0.95rem;
            margin-bottom: 16px;
        }

        .form-control:focus {
            outline: none;
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px;
            color: var(--text-soft);
        }

        .btn {
            width: 100%;
            min-height: 50px;
            border: none;
            border-radius: 14px;
            background: linear-gradient(135deg, #38bdf8, #2563eb);
            color: #fff;
            font-weight: 800;
            font-size: 0.95rem;
            cursor: pointer;
        }

        .error-box {
            border-radius: 14px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 12px 14px;
            margin-bottom: 18px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h1 class="login-title">Restaurang</h1>
        <p class="login-subtitle">
            Logga in för att se dagens turer och förväntade gästantal i köket.
        </p>

        @if($errors->any())
            <div class="error-box">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('restaurant.login.submit') }}">
            @csrf

            <label class="form-label">E-post</label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>

            <label class="form-label">Lösenord</label>
            <input type="password" name="password" class="form-control" required>

            <label class="form-check">
                <input type="checkbox" name="remember" value="1">
                <span>Håll mig inloggad</span>
            </label>

            <button type="submit" class="btn">Öppna restaurangvy</button>
        </form>
    </div>
</body>
</html>