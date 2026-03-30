<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Visitor Account — {{ config('app.name', 'Predictor') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,700|ibm-plex-mono:400,500&display=swap" rel="stylesheet" />
    <style>
        :root {
            --bg: #f4efe4;
            --card: rgba(255, 248, 237, 0.95);
            --line: rgba(61, 44, 28, 0.14);
            --text: #1e1712;
            --muted: #645447;
            --accent: #d96c2f;
            --accent-strong: #b74f1d;
            --shadow: 0 30px 80px rgba(64, 38, 20, 0.12);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Space Grotesk', sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(217, 108, 47, 0.18), transparent 32%),
                radial-gradient(circle at bottom right, rgba(30, 122, 120, 0.16), transparent 28%),
                linear-gradient(180deg, #f9f3ea 0%, var(--bg) 50%, #efe6d8 100%);
            display: grid;
            place-items: center;
            padding: 24px;
        }
        .card {
            width: min(100%, 480px);
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 28px;
            padding: 40px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(12px);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            text-decoration: none;
            color: var(--text);
        }
        .brand-mark {
            display: grid;
            place-items: center;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--accent), #f2b46f);
            color: #fff8f1;
            font-size: 1.1rem;
        }
        .badge-visitor {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(217, 108, 47, 0.1);
            color: var(--accent-strong);
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 12px;
        }
        h1 {
            margin: 0 0 6px;
            font-size: 1.8rem;
            letter-spacing: -0.03em;
        }
        .subtitle {
            margin: 0 0 28px;
            color: var(--muted);
            font-size: 0.95rem;
            line-height: 1.6;
        }
        .features {
            display: grid;
            gap: 8px;
            margin-bottom: 28px;
            padding: 18px;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.5);
        }
        .feature {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
            color: var(--muted);
        }
        .feature-dot {
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: var(--accent);
            flex-shrink: 0;
        }
        label {
            display: block;
            margin-bottom: 16px;
            font-size: 0.88rem;
            font-weight: 600;
        }
        label span {
            display: block;
            margin-bottom: 7px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-size: 0.78rem;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 12px;
            font: inherit;
            font-size: 0.95rem;
            background: rgba(255, 255, 255, 0.7);
            color: var(--text);
            transition: border-color 150ms ease, box-shadow 150ms ease;
        }
        input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(217, 108, 47, 0.12);
        }
        .btn {
            width: 100%;
            padding: 13px 16px;
            border: none;
            border-radius: 12px;
            font: inherit;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            background: var(--text);
            color: #fff8f1;
            box-shadow: 0 8px 20px rgba(30, 23, 18, 0.18);
            transition: transform 160ms ease, box-shadow 160ms ease;
            margin-top: 4px;
        }
        .btn:hover { transform: translateY(-2px); }
        .error-box {
            margin-bottom: 18px;
            padding: 12px 16px;
            border-radius: 12px;
            background: #fee2e2;
            color: #991b1b;
            font-size: 0.9rem;
        }
        .error-box ul { margin: 6px 0 0; padding-left: 18px; }
        .footer-link {
            text-align: center;
            margin-top: 22px;
            font-size: 0.9rem;
            color: var(--muted);
        }
        .footer-link a {
            color: var(--accent-strong);
            font-weight: 600;
            text-decoration: none;
        }
        .footer-link a:hover { text-decoration: underline; }
        .divider {
            height: 1px;
            background: var(--line);
            margin: 24px 0;
        }
    </style>
</head>
<body>
    <div class="card">
        <a href="{{ route('login') }}" class="brand">
            <div class="brand-mark">P</div>
            <div>Predictor</div>
        </a>

        <div class="badge-visitor">✦ Visitor Account</div>
        <h1>Try Predictor free</h1>
        <p class="subtitle">
            Create a visitor account to explore the platform with pre-loaded demo data.
            No credit card required.
        </p>

        @if($freePlan && $freePlan->features)
        <div class="features">
            @foreach($freePlan->features as $feature)
                <div class="feature">
                    <span class="feature-dot"></span>
                    <span>{{ $feature }}</span>
                </div>
            @endforeach
        </div>
        @endif

        @if ($errors->any())
            <div class="error-box">
                <strong>Please fix the following:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}">
            @csrf

            <label>
                <span>Full Name</span>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="Jane Smith" required autofocus>
            </label>

            <label>
                <span>Email Address</span>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="jane@example.com" required>
            </label>

            <label>
                <span>Password</span>
                <input type="password" name="password" placeholder="At least 8 characters" required>
            </label>

            <label>
                <span>Confirm Password</span>
                <input type="password" name="password_confirmation" placeholder="Repeat your password" required>
            </label>

            <button type="submit" class="btn">Create Visitor Account</button>
        </form>

        <div class="divider"></div>

        <div class="footer-link">
            Already have an account?
            <a href="{{ route('login') }}">Sign in</a>
        </div>
    </div>
</body>
</html>
