<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Routes</title>
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
        h1 {
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
        .row-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .row-actions form {
            margin: 0;
        }
        .status {
            margin-bottom: 20px;
            padding: 14px;
            border-radius: 12px;
            background: #dcfce7;
            color: #166534;
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
        .pill {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: #dcfce7;
            color: #166534;
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
            <h1>Route Management</h1>
            <p>Manage the inbound route network used for flight ingestion, watch targets, and route-level risk scoring.</p>
        </div>

        <div class="actions">
            <a href="{{ route('dashboard') }}" class="secondary">Back to Dashboard</a>
            <a href="{{ route('admin.ops.index') }}" class="secondary">Operations Panel</a>
            <a href="{{ route('admin.providers.index') }}" class="secondary">Providers</a>
            <a href="{{ route('admin.routes.create') }}">New Route</a>
        </div>
    </header>

    <section>
        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        <table>
            <thead>
                <tr>
                    <th>Origin</th>
                    <th>Destination</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($routes as $route)
                    <tr>
                        <td>
                            <strong>{{ $route->originAirport->iata }}</strong>
                            <div class="meta">
                                {{ $route->originAirport->city->name }}, {{ $route->originAirport->city->country->name }}<br>
                                {{ $route->originAirport->name }}
                            </div>
                        </td>
                        <td>
                            <strong>{{ $route->destinationAirport->iata }}</strong>
                            <div class="meta">
                                {{ $route->destinationAirport->city->name }}, {{ $route->destinationAirport->city->country->name }}<br>
                                {{ $route->destinationAirport->name }}
                            </div>
                        </td>
                        <td>
                            <span class="pill {{ $route->active ? '' : 'inactive' }}">
                                {{ $route->active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>{{ $route->notes ?: 'No notes.' }}</td>
                        <td>
                            <div class="row-actions">
                                <a href="{{ route('admin.routes.edit', $route) }}" class="secondary">Edit</a>

                                <form method="POST" action="{{ route('admin.routes.destroy', $route) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No routes configured yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</main>
</body>
</html>
