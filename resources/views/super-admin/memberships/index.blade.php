<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Membership Plans — Predictor Super Admin</title>
    <style>
        body { margin: 0; font-family: ui-sans-serif, system-ui, sans-serif; background: #f5f7fb; color: #172033; }
        main { max-width: 1100px; margin: 0 auto; padding: 40px 24px 80px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; gap: 16px; flex-wrap: wrap; }
        .topbar h1 { margin: 0; font-size: 1.6rem; }
        .breadcrumb { font-size: 0.85rem; color: #5b667a; margin-bottom: 6px; }
        .breadcrumb a { color: #1d4ed8; text-decoration: none; }
        nav.sidebar-nav { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 28px; }
        nav.sidebar-nav a {
            padding: 8px 16px; border-radius: 10px; font-size: 0.88rem; font-weight: 600;
            text-decoration: none; border: 1px solid #dbe2ef; background: #fff; color: #172033;
        }
        nav.sidebar-nav a.active { background: #0f172a; color: #fff; border-color: #0f172a; }
        .card { background: #fff; border: 1px solid #dbe2ef; border-radius: 18px; padding: 24px; box-shadow: 0 4px 20px rgba(23,32,51,0.06); }
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 10px 18px; border-radius: 10px; font: inherit; font-size: 0.9rem;
            font-weight: 700; cursor: pointer; border: none; text-decoration: none; transition: opacity 160ms;
        }
        .btn:hover { opacity: 0.85; }
        .btn-primary { background: #0f172a; color: #fff; }
        .btn-accent { background: #1d4ed8; color: #fff; }
        .btn-danger { background: #fee2e2; color: #991b1b; }
        .btn-secondary { background: #e2e8f0; color: #172033; }
        .btn-sm { padding: 6px 12px; font-size: 0.82rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th { text-align: left; padding: 10px 14px; border-bottom: 2px solid #dbe2ef; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.06em; color: #5b667a; }
        td { padding: 14px; border-bottom: 1px solid #f0f4f8; vertical-align: middle; font-size: 0.92rem; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fbff; }
        .badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
        .badge-active { background: #dcfce7; color: #166534; }
        .badge-inactive { background: #f1f5f9; color: #64748b; }
        .feature-tag { display: inline-block; padding: 2px 8px; border-radius: 6px; background: #f1f5f9; color: #475569; font-size: 0.78rem; margin: 2px 2px 2px 0; }
        .alert-success { padding: 12px 16px; border-radius: 12px; background: #dcfce7; color: #166534; margin-bottom: 20px; font-size: 0.92rem; }
        .empty-state { text-align: center; padding: 60px 24px; color: #5b667a; }
        form { margin: 0; }
        .actions-cell { display: flex; gap: 8px; }
    </style>
</head>
<body>
<main>
    <div class="breadcrumb">
        <a href="{{ route('dashboard') }}">Dashboard</a> / Super Admin / Membership Plans
    </div>

    <nav class="sidebar-nav">
        <a href="{{ route('super-admin.memberships.index') }}" class="active">Membership Plans</a>
        <a href="{{ route('super-admin.users.index') }}">Manage Users</a>
        <a href="{{ route('dashboard') }}">← Back to Dashboard</a>
    </nav>

    <div class="topbar">
        <div>
            <h1>Membership Plans</h1>
            <p style="margin: 4px 0 0; color: #5b667a; font-size: 0.92rem;">
                {{ $plans->count() }} plan{{ $plans->count() !== 1 ? 's' : '' }} configured
            </p>
        </div>
        <a href="{{ route('super-admin.memberships.create') }}" class="btn btn-primary">+ New Plan</a>
    </div>

    @if (session('status'))
        <div class="alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        @if ($plans->isEmpty())
            <div class="empty-state">
                <p style="font-size: 1.1rem; font-weight: 700;">No membership plans yet</p>
                <p>Create your first plan to start assigning memberships to users.</p>
                <a href="{{ route('super-admin.memberships.create') }}" class="btn btn-accent">Create First Plan</a>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Price</th>
                        <th>Limits</th>
                        <th>Features</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($plans as $plan)
                        <tr>
                            <td style="color: #5b667a; font-size: 0.82rem;">{{ $plan->sort_order }}</td>
                            <td>
                                <strong>{{ $plan->name }}</strong>
                                @if ($plan->description)
                                    <div style="color: #5b667a; font-size: 0.82rem; margin-top: 3px;">{{ Str::limit($plan->description, 60) }}</div>
                                @endif
                            </td>
                            <td>
                                <code style="font-size: 0.82rem; background: #f1f5f9; padding: 2px 8px; border-radius: 6px;">{{ $plan->slug }}</code>
                            </td>
                            <td>
                                <strong>{{ $plan->formatted_price }}</strong>
                            </td>
                            <td style="font-size: 0.82rem; color: #5b667a;">
                                {{ $plan->api_token_limit }} token{{ $plan->api_token_limit !== 1 ? 's' : '' }}<br>
                                {{ $plan->route_query_limit }} queries/day
                            </td>
                            <td>
                                @if ($plan->features)
                                    @foreach (array_slice($plan->features, 0, 3) as $feature)
                                        <span class="feature-tag">{{ $feature }}</span>
                                    @endforeach
                                    @if (count($plan->features) > 3)
                                        <span class="feature-tag">+{{ count($plan->features) - 3 }} more</span>
                                    @endif
                                @else
                                    <span style="color: #94a3b8; font-size: 0.82rem;">None</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $plan->is_active ? 'badge-active' : 'badge-inactive' }}">
                                    {{ $plan->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <div class="actions-cell">
                                    <a href="{{ route('super-admin.memberships.edit', $plan) }}" class="btn btn-secondary btn-sm">Edit</a>
                                    <form method="POST" action="{{ route('super-admin.memberships.destroy', $plan) }}"
                                          onsubmit="return confirm('Delete the {{ addslashes($plan->name) }} plan?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</main>
</body>
</html>
