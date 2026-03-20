<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <style>
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, sans-serif;
            background: #f5f7fb;
            color: #172033;
        }
        main {
            max-width: 920px;
            margin: 0 auto;
            padding: 40px 24px 80px;
        }
        header, section {
            background: #fff;
            border: 1px solid #dbe2ef;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 18px 44px rgba(23, 32, 51, 0.06);
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }
        h1 {
            margin-top: 0;
        }
        p {
            color: #5b667a;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }
        label {
            display: block;
            margin-bottom: 16px;
            font-size: 14px;
            font-weight: 600;
        }
        select, textarea {
            width: 100%;
            box-sizing: border-box;
            margin-top: 8px;
            padding: 12px 14px;
            border: 1px solid #c6d0e1;
            border-radius: 10px;
            font: inherit;
            background: #fff;
        }
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        .checkbox {
            display: flex;
            gap: 10px;
            align-items: center;
            margin: 8px 0 24px;
            font-weight: 600;
        }
        .checkbox input {
            width: auto;
            margin: 0;
        }
        .actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .error {
            margin-bottom: 20px;
            padding: 14px;
            border-radius: 12px;
            background: #fee2e2;
            color: #991b1b;
        }
        a, button {
            border: 0;
            border-radius: 10px;
            padding: 12px 16px;
            background: #1d4ed8;
            color: #fff;
            font: inherit;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }
        .secondary {
            background: #e2e8f0;
            color: #172033;
        }
        @media (max-width: 900px) {
            .grid, header {
                grid-template-columns: 1fr;
            }
            header {
                display: grid;
            }
        }
    </style>
</head>
<body>
<main>
    <header>
        <div>
            <h1>{{ $pageTitle }}</h1>
            <p>Define the airport pair that should be monitored and exposed for route-level risk queries.</p>
        </div>

        <div class="actions">
            <a href="{{ route('admin.routes.index') }}" class="secondary">Back to Routes</a>
            <a href="{{ route('dashboard') }}" class="secondary">Dashboard</a>
        </div>
    </header>

    <section>
        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ $formAction }}">
            @csrf
            @if ($formMethod !== 'POST')
                @method($formMethod)
            @endif

            <div class="grid">
                <label>
                    Origin airport
                    <select name="origin_airport_id" required>
                        <option value="">Select an origin</option>
                        @foreach ($airports as $airport)
                            <option value="{{ $airport->id }}" @selected((string) old('origin_airport_id', $route->origin_airport_id) === (string) $airport->id)>
                                {{ $airport->iata }} · {{ $airport->city->name }}, {{ $airport->city->country->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label>
                    Destination airport
                    <select name="destination_airport_id" required>
                        <option value="">Select a destination</option>
                        @foreach ($airports as $airport)
                            <option value="{{ $airport->id }}" @selected((string) old('destination_airport_id', $route->destination_airport_id) === (string) $airport->id)>
                                {{ $airport->iata }} · {{ $airport->city->name }}, {{ $airport->city->country->name }}
                            </option>
                        @endforeach
                    </select>
                </label>
            </div>

            <input type="hidden" name="active" value="0">
            <label class="checkbox">
                <input type="checkbox" name="active" value="1" {{ old('active', $route->active) ? 'checked' : '' }}>
                Route is active
            </label>

            <label>
                Notes
                <textarea name="notes">{{ old('notes', $route->notes) }}</textarea>
            </label>

            <div class="actions">
                <button type="submit">{{ $route->exists ? 'Save Changes' : 'Create Route' }}</button>
                <a href="{{ route('admin.routes.index') }}" class="secondary">Cancel</a>
            </div>
        </form>
    </section>
</main>
</body>
</html>
