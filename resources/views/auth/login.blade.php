<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Predictor Login</title>
    <style>
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, sans-serif;
            background: #f5f7fb;
            color: #172033;
        }
        main {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
        }
        form {
            width: min(100%, 420px);
            background: #fff;
            border: 1px solid #dbe2ef;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 20px 50px rgba(23, 32, 51, 0.08);
        }
        h1 {
            margin: 0 0 8px;
        }
        p {
            margin: 0 0 24px;
            color: #5b667a;
        }
        label {
            display: block;
            margin-bottom: 16px;
            font-size: 14px;
            font-weight: 600;
        }
        input {
            width: 100%;
            box-sizing: border-box;
            margin-top: 8px;
            padding: 12px 14px;
            border: 1px solid #c6d0e1;
            border-radius: 10px;
            font: inherit;
        }
        button {
            width: 100%;
            border: 0;
            border-radius: 10px;
            padding: 12px 16px;
            background: #1d4ed8;
            color: #fff;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }
        .checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }
        .checkbox input {
            width: auto;
            margin: 0;
        }
        .error {
            margin: 0 0 16px;
            padding: 12px 14px;
            border-radius: 10px;
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
<main>
    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <h1>Internal Access</h1>
        <p>Sign in to manage users and issue API tokens.</p>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <label>
            Email
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>
        </label>

        <label>
            Password
            <input type="password" name="password" required>
        </label>

        <label class="checkbox">
            <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
            Remember this session
        </label>

        <button type="submit">Sign in</button>
    </form>
</main>
</body>
</html>
