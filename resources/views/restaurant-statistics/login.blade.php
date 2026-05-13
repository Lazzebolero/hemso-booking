<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurang statistik - inloggning</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: Arial, sans-serif;
            background: #0f172a;
            color: #0f172a;
        }

        .login-card {
            width: min(420px, calc(100vw - 2rem));
            background: #ffffff;
            border-radius: 18px;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
        }

        h1 {
            margin: 0 0 0.5rem;
            font-size: 1.5rem;
        }

        p {
            margin: 0 0 1.25rem;
            color: #475569;
        }

        label {
            display: block;
            margin-bottom: 0.45rem;
            font-weight: 700;
        }

        input {
            width: 100%;
            box-sizing: border-box;
            padding: 0.8rem 0.9rem;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            margin-bottom: 0.9rem;
            font-size: 1rem;
        }

        button {
            width: 100%;
            border: 0;
            border-radius: 12px;
            padding: 0.9rem 1rem;
            background: #2563eb;
            color: white;
            font-weight: 700;
            cursor: pointer;
        }

        .error {
            margin-bottom: 1rem;
            color: #b91c1c;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <form method="POST" action="{{ route('restaurant-statistics.login.store') }}" class="login-card">
        @csrf

        <h1>Restaurang statistik</h1>
        <p>Logga in med den separata koden för statistikskärmen.</p>

        @if($errors->any())
            <div class="error">{{ $errors->first('password') }}</div>
        @endif

        <label for="password">Kod / lösenord</label>
        <input type="password" name="password" id="password" required autofocus>

        <button type="submit">Öppna statistik</button>
    </form>
</body>
</html>