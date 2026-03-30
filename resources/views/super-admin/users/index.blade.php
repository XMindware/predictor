<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Users — Predictor Super Admin</title>
    <style>
        body { margin: 0; font-family: ui-sans-serif, system-ui, sans-serif; background: #f5f7fb; color: #172033; }
        main { max-width: 1200px; margin: 0 auto; padding: 40px 24px 80px; }
        .topbar { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; gap: 16px; flex-wrap: wrap; }
        .topbar h1 { margin: 0 0 4px; font-size: 1.6rem; }
        .breadcrumb { font-size: 0.85rem; color: #5b667a; margin-bottom: 6px; }
        .breadcrumb a { color: #1d4ed8; text-decoration: none; }
        nav.sidebar-nav { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 28px; }
        nav.sidebar-nav a {
            padding: 8px 16px; border-radius: 10px; font-size: 0.88rem; font-weight: 600;
            text-decoration: none; border: 1px solid #dbe2ef; background: #fff; color: #172033;
        }
        nav.sidebar-nav a.active { background: #0f172a; color: #fff; border-color: #0f172a; }
        .card { background: #fff; border: 1px solid #dbe2ef; border-radius: 18px; overflow: hidden; box-shadow: 0 4px 20px rgba(23,32,51,0.06); }
        .filter-bar { padding: 18px 24px; border-bottom: 1px solid #dbe2ef; display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
        .filter-bar input, .filter-bar select {
            padding: 9px 14px; border: 1px solid #c6d0e1; border-radius: 10px; font: inherit; font-size: 0.9rem;
        }
        .filter-bar input { width: 260px; }
        .btn {
            display: inline-flex; align-items: center;
            padding: 9px 16px; border-radius: 10px; font: inherit; font-size: 0.88rem;
            font-weight: 700; cursor: pointer; border: none; text-decoration: none;
        }
        .btn-primary { background: #0f172a; color: #fff; }
        .btn-secondary { background: #e2e8f0; color: #172033; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 10px 16px; border-bottom: 2px solid #dbe2ef; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.06em; color: #5b667a; white-space: nowrap; }
        td { padding: 14px 16px; border-bottom: 1px solid #f0f4f8; vertical-align: middle; font-size: 0.92rem; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fbff; }
        .badge { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; white-space: nowrap; }
        .role-super_admin { background: #fef3c7; color: #92400e; }
        .role-admin { background: #dbeafe; color: #1d4ed8; }
        .role-member { background: #dcfce7; color: #166534; }
        .role-visitor { background: #f1f5f9; color: #475569; }
        .mem-active { background: #dcfce7; color: #166534; }
        .mem-expired { background: #fee2e2; color: #991b1b; }
        .mem-trial { background: #dbeafe; color: #1e40af; }
        .mem-none { background: #f1f5f9; color: #94a3b8; }
        .pagination { padding: 16px 24px; display: flex; gap: 8px; align-items: center; border-top: 1px solid #dbe2ef; flex-wrap: wrap; }
        .pagination a { padding: 6px 12px; border-radius: 8px; border: 1px solid #dbe2ef; background: #fff; color: #172033; text-decoration: none; font-size: 0.88rem; }
        .pagination span.active { padding: 6px 12px; border-radius: 8px; background: #0f172a; color: #fff; font-size: 0.88rem; }
        .alert-success { padding: 12px 16px; border-radius: 12px; background: #dcfce7; color: #166534; margin-bottom: 20px; font-size: 0.92rem; }
    </style>
</head>
<body>
<main>
    <div class="breadcrumb">
        <a href="{{ route('dashboard') }}">Dashboard</a> / Super Admin / Users
    </div>

    <nav class="sidebar-nav">
        <a href="{{ route('super-admin.memberships.index') }}">Membership Plans</a>
        <a href="{{ route('super-admin.users.index') }}" class="active">Manage Users</a>
        <a href="{{ route('dashboard') }}">← Back to Dashboard</a>
    </nav>

    <div class="topbar">
        <div>
            <h1>User Management</h1>
            <p style="margin: 0; color: #5b667a; font-size: 0.92rem;">
                {{ $users->total() }} user{{ $users->total() !== 1 ? 's' : '' }} registered
            </p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        <form method="GET" class="filter-bar">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email…">
            <select name="role">
                <option value="">All roles</option>
                @foreach ($roles as $role)
                    <option value="{{ $role }}" {{ request('role') === $role ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_', ' ', $role)) }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            @if (request('search') || request('role'))
                <a href="{{ route('super-admin.users.index') }}" class="btn btn-secondary">Clear</a>
            @endif
        </form>

        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Membership</th>
                    <th>Plan</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <strong>{{ $user->name }}</strong>
                            <div style="color: #5b667a; font-size: 0.82rem; margin-top: 2px;">{{ $user->email }}</div>
                        </td>
                        <td>
                            <span class="badge role-{{ $user->role }}">{{ str_replace('_', ' ', $user->role) }}</span>
                        </td>
                        <td>
                            @if ($user->activeMembership)
                                @php $mem = $user->activeMembership; @endphp
                                <span class="badge mem-{{ $mem->status }}">{{ $mem->status }}</span>
                            @else
                                <span class="badge mem-none">None</span>
                            @endif
                        </td>
                        <td style="font-size: 0.88rem;">
                            {{ $user->activeMembership?->plan?->name ?? '—' }}
                        </td>
                        <td style="font-size: 0.82rem; color: #5b667a;">
                            {{ $user->created_at->format('M j, Y') }}
                        </td>
                        <td>
                            <a href="{{ route('super-admin.users.show', $user) }}" class="btn btn-secondary" style="font-size: 0.82rem; padding: 6px 12px;">
                                Manage
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #5b667a; padding: 40px;">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($users->hasPages())
            <div class="pagination">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</main>
</body>
</html>
