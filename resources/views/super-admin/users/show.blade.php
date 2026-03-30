<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $user->name }} — User Management</title>
    <style>
        body { margin: 0; font-family: ui-sans-serif, system-ui, sans-serif; background: #f5f7fb; color: #172033; }
        main { max-width: 960px; margin: 0 auto; padding: 40px 24px 80px; }
        .breadcrumb { font-size: 0.85rem; color: #5b667a; margin-bottom: 20px; }
        .breadcrumb a { color: #1d4ed8; text-decoration: none; }
        h1 { margin: 0 0 4px; font-size: 1.5rem; }
        .stack { display: grid; gap: 24px; }
        .card { background: #fff; border: 1px solid #dbe2ef; border-radius: 18px; padding: 24px; box-shadow: 0 4px 20px rgba(23,32,51,0.06); }
        .card h2 { margin: 0 0 18px; font-size: 1.05rem; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-full { grid-column: 1 / -1; }
        label { display: block; margin-bottom: 16px; font-size: 0.88rem; font-weight: 600; }
        label span { display: block; margin-bottom: 6px; color: #5b667a; text-transform: uppercase; letter-spacing: 0.04em; font-size: 0.78rem; }
        input, select, textarea {
            width: 100%; padding: 10px 14px; border: 1px solid #c6d0e1; border-radius: 10px;
            font: inherit; font-size: 0.9rem; box-sizing: border-box;
        }
        textarea { resize: vertical; min-height: 70px; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #1d4ed8; box-shadow: 0 0 0 3px rgba(29,78,216,0.1); }
        .btn {
            display: inline-flex; align-items: center; padding: 10px 18px;
            border-radius: 10px; font: inherit; font-size: 0.9rem;
            font-weight: 700; cursor: pointer; border: none; text-decoration: none;
        }
        .btn-primary { background: #0f172a; color: #fff; }
        .btn-accent { background: #1d4ed8; color: #fff; }
        .btn-danger { background: #fee2e2; color: #991b1b; }
        .btn-secondary { background: #e2e8f0; color: #172033; }
        .btn-sm { padding: 6px 12px; font-size: 0.82rem; }
        .badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
        .role-super_admin { background: #fef3c7; color: #92400e; }
        .role-admin { background: #dbeafe; color: #1d4ed8; }
        .role-member { background: #dcfce7; color: #166534; }
        .role-visitor { background: #f1f5f9; color: #475569; }
        .mem-active { background: #dcfce7; color: #166534; }
        .mem-expired { background: #fee2e2; color: #991b1b; }
        .mem-cancelled { background: #fef3c7; color: #92400e; }
        .mem-trial { background: #dbeafe; color: #1e40af; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px; }
        .info-item { padding: 14px; border: 1px solid #dbe2ef; border-radius: 12px; background: #f8fbff; }
        .info-label { font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.06em; color: #5b667a; margin-bottom: 6px; }
        .info-value { font-weight: 700; font-size: 0.95rem; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 8px 12px; border-bottom: 2px solid #dbe2ef; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.06em; color: #5b667a; }
        td { padding: 12px; border-bottom: 1px solid #f0f4f8; vertical-align: middle; font-size: 0.9rem; }
        .alert-success { padding: 12px 16px; border-radius: 12px; background: #dcfce7; color: #166534; margin-bottom: 20px; font-size: 0.92rem; }
        .alert-error { padding: 12px 16px; border-radius: 12px; background: #fee2e2; color: #991b1b; margin-bottom: 20px; font-size: 0.92rem; }
        .divider { height: 1px; background: #f0f4f8; margin: 20px 0; }
        form { margin: 0; }
    </style>
</head>
<body>
<main>
    <div class="breadcrumb">
        <a href="{{ route('dashboard') }}">Dashboard</a> /
        <a href="{{ route('super-admin.users.index') }}">Users</a> /
        {{ $user->name }}
    </div>

    @if (session('status'))
        <div class="alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    <div class="stack">
        {{-- User overview --}}
        <div class="card">
            <h2>User Profile</h2>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Name</div>
                    <div class="info-value">{{ $user->name }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value" style="word-break: break-all;">{{ $user->email }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Current Role</div>
                    <div class="info-value">
                        <span class="badge role-{{ $user->role }}">{{ str_replace('_', ' ', $user->role) }}</span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Joined</div>
                    <div class="info-value">{{ $user->created_at->format('M j, Y') }}</div>
                </div>
            </div>
        </div>

        {{-- Change role --}}
        <div class="card">
            <h2>Change Role</h2>
            <form method="POST" action="{{ route('super-admin.users.update-role', $user) }}">
                @csrf
                @method('PATCH')
                <div class="form-grid">
                    <label>
                        <span>New Role</span>
                        <select name="role" required>
                            @foreach (\App\Models\User::ROLES as $role)
                                <option value="{{ $role }}" {{ $user->role === $role ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $role)) }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <div style="display: flex; align-items: flex-end; padding-bottom: 16px;">
                        <button type="submit" class="btn btn-primary" onclick="return confirm('Change this user\'s role?')">Update Role</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Assign membership --}}
        <div class="card">
            <h2>Assign New Membership</h2>
            <form method="POST" action="{{ route('super-admin.users.memberships.store', $user) }}">
                @csrf
                <div class="form-grid">
                    <label>
                        <span>Plan *</span>
                        <select name="membership_plan_id" required>
                            <option value="">Select a plan…</option>
                            @foreach ($plans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }} — {{ $plan->formatted_price }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span>Status *</span>
                        <select name="status" required>
                            <option value="active">Active</option>
                            <option value="trial">Trial</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="expired">Expired</option>
                        </select>
                    </label>

                    <label>
                        <span>Expires At</span>
                        <input type="date" name="expires_at" value="{{ old('expires_at') }}" min="{{ now()->addDay()->format('Y-m-d') }}">
                    </label>

                    <label>
                        <span>Notes</span>
                        <textarea name="notes" placeholder="Optional internal notes…">{{ old('notes') }}</textarea>
                    </label>
                </div>
                <button type="submit" class="btn btn-accent">Assign Membership</button>
            </form>
        </div>

        {{-- Membership history --}}
        <div class="card">
            <h2>Membership History ({{ $user->memberships->count() }})</h2>

            @if ($user->memberships->isEmpty())
                <p style="color: #5b667a;">No memberships assigned yet.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th>Status</th>
                            <th>Started</th>
                            <th>Expires</th>
                            <th>Notes</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($user->memberships->sortByDesc('created_at') as $membership)
                            <tr>
                                <td><strong>{{ $membership->plan?->name ?? '—' }}</strong></td>
                                <td><span class="badge mem-{{ $membership->status }}">{{ $membership->status }}</span></td>
                                <td style="font-size: 0.82rem; color: #5b667a;">{{ $membership->started_at?->format('M j, Y') ?? '—' }}</td>
                                <td style="font-size: 0.82rem; color: #5b667a;">
                                    {{ $membership->expires_at?->format('M j, Y') ?? 'No expiry' }}
                                    @if ($membership->isExpired())
                                        <span class="badge mem-expired" style="margin-left: 4px;">Expired</span>
                                    @endif
                                </td>
                                <td style="font-size: 0.82rem; color: #5b667a;">{{ $membership->notes ?? '—' }}</td>
                                <td>
                                    @if ($membership->status === 'active')
                                        <form method="POST" action="{{ route('super-admin.users.memberships.cancel', [$user, $membership]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Cancel this membership?')">Cancel</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</main>
</body>
</html>
