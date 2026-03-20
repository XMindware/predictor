<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Provider Registry</title>
    <style>
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, sans-serif;
            background: #f5f7fb;
            color: #172033;
        }
        main {
            max-width: 1120px;
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
        h1, h2 {
            margin-top: 0;
        }
        p {
            color: #5b667a;
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
        .danger {
            background: #dc2626;
        }
        .actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .status {
            margin-bottom: 20px;
            padding: 14px;
            border-radius: 12px;
            background: #dcfce7;
            color: #166534;
        }
        .error {
            margin-bottom: 20px;
            padding: 14px;
            border-radius: 12px;
            background: #fee2e2;
            color: #991b1b;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        th {
            color: #475569;
            font-size: 14px;
        }
        .meta {
            color: #64748b;
            font-size: 14px;
        }
        .row-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .row-actions form {
            margin: 0;
        }
        .pill {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: #dbeafe;
            color: #1d4ed8;
        }
        .pill.inactive {
            background: #e2e8f0;
            color: #475569;
        }
    </style>
</head>
<body>
<main>
    <header>
        <div>
            <h1>Provider Registry</h1>
            <p>Manage weather, flight, and news providers plus their credentials and configs.</p>
        </div>

        <div class="actions">
            <a href="{{ route('dashboard') }}" class="secondary">Back to Dashboard</a>
            <a href="{{ route('admin.ops.index') }}" class="secondary">Operations Panel</a>
            <a href="{{ route('admin.routes.index') }}" class="secondary">Routes</a>
            <a href="{{ route('admin.providers.create') }}">New Provider</a>
        </div>
    </header>

    <section>
        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="error">{{ session('error') }}</div>
        @endif

        <table>
            <thead>
                <tr>
                    <th>Provider</th>
                    <th>Service</th>
                    <th>Registry</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($providers as $provider)
                    <tr>
                        <td>
                            <strong>{{ $provider->name }}</strong>
                            <div class="meta">
                                Slug: {{ $provider->slug }}<br>
                                Driver: {{ $provider->driver }}
                            </div>
                        </td>
                        <td>{{ $provider->service }}</td>
                        <td class="meta">
                            Credentials: {{ $provider->credentials_count }}<br>
                            Configs: {{ $provider->configs_count }}
                        </td>
                        <td>
                            <span class="pill {{ $provider->active ? '' : 'inactive' }}">
                                {{ $provider->active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <div class="row-actions">
                                <a href="{{ route('admin.providers.edit', $provider) }}" class="secondary">Edit</a>

                                <form method="POST" action="{{ route('admin.providers.test', $provider) }}">
                                    @csrf
                                    <button type="submit" class="secondary">Test API</button>
                                </form>

                                <form method="POST" action="{{ route('admin.providers.destroy', $provider) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No providers registered yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</main>
</body>
</html>
