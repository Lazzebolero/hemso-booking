<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tack för din bokning</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            margin: 0;
            background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
            font-family: 'Figtree', Arial, sans-serif;
            color: #0f172a;
        }

        .thanks-wrap {
            max-width: 760px;
            margin: 50px auto;
            padding: 0 16px;
        }

        .thanks-card {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.10);
            padding: 32px;
            text-align: center;
        }

        .thanks-logo {
            max-width: 180px;
            max-height: 80px;
            margin: 0 auto 20px;
            display: block;
        }

        .thanks-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .thanks-text {
            color: #475569;
            font-size: 1rem;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="thanks-wrap">
        <div class="thanks-card">
            <img src="{{ asset('images/logo.png') }}" alt="Hemsö" class="thanks-logo">

            <div class="thanks-title">Tack för din bokning</div>
            <div class="thanks-text">
                {!! nl2br(e($bookingPage->thank_you_text ?: 'Tack för din bokning. En bokningsbekräftelse har skickats till din e-post.')) !!}
            </div>
        </div>
    </div>
</body>
</html>