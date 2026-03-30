<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Predictor — Dashboard</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        /* ── Base ──────────────────────────────────────────────────────────── */
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: #f0f2f8;
            color: #172033;
            min-height: 100vh;
        }

        /* ── Top Nav ───────────────────────────────────────────────────────── */
        .topnav {
            background: #0f172a;
            color: #e2e8f0;
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 56px;
            position: sticky;
            top: 0;
            z-index: 100;
            gap: 16px;
        }
        .topnav-brand {
            font-weight: 900;
            font-size: 1rem;
            letter-spacing: -0.03em;
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .topnav-brand-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #22d3ee;
        }
        .topnav-user {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 0.82rem;
        }
        .topnav-user-name { color: #94a3b8; }
        .topnav-badge {
            padding: 3px 9px;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .topnav-logout {
            background: rgba(255,255,255,0.08);
            color: #cbd5e1;
            border: none;
            border-radius: 8px;
            padding: 7px 14px;
            font: inherit;
            font-size: 0.8rem;
            cursor: pointer;
            transition: background 120ms;
        }
        .topnav-logout:hover { background: rgba(255,255,255,0.15); }

        /* ── Layout ─────────────────────────────────────────────────────────── */
        .shell {
            display: grid;
            grid-template-columns: 220px 1fr;
            min-height: calc(100vh - 56px);
            max-width: 1280px;
            margin: 0 auto;
            gap: 0;
        }

        /* ── Sidebar ─────────────────────────────────────────────────────────*/
        .sidebar {
            background: #fff;
            border-right: 1px solid #e8edf5;
            padding: 24px 16px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .sidebar-section {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            padding: 16px 10px 6px;
            margin-top: 8px;
        }
        .sidebar-section:first-child { margin-top: 0; padding-top: 4px; }
        .nav-link {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 9px 12px;
            border-radius: 9px;
            text-decoration: none;
            font-size: 0.87rem;
            font-weight: 500;
            color: #374151;
            transition: background 120ms, color 120ms;
        }
        .nav-link:hover { background: #f0f4ff; color: #1d4ed8; }
        .nav-link.active { background: #eff6ff; color: #1d4ed8; font-weight: 700; }
        .nav-link.purple:hover { background: #f5f3ff; color: #6d28d9; }
        .nav-link.purple.active { background: #f5f3ff; color: #6d28d9; }
        .nav-link.teal:hover { background: #f0fdfa; color: #0f766e; }
        .nav-icon { width: 16px; text-align: center; font-style: normal; }

        /* ── Main content area ──────────────────────────────────────────────── */
        .content {
            padding: 28px 32px 64px;
            overflow: hidden;
        }

        /* ── Section header ─────────────────────────────────────────────────── */
        .section-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }
        .section-head h2 {
            margin: 0 0 2px;
            font-size: 1.1rem;
        }
        .section-head p {
            margin: 0;
            font-size: 0.82rem;
            color: #6b7280;
        }

        /* ── Cards ───────────────────────────────────────────────────────────── */
        .card {
            background: #fff;
            border: 1px solid #e8edf5;
            border-radius: 16px;
            padding: 22px 24px;
            box-shadow: 0 2px 12px rgba(23,32,51,0.05);
        }
        .card + .card { margin-top: 20px; }
        .card-title {
            font-size: 0.9rem;
            font-weight: 700;
            margin: 0 0 14px;
            color: #0f172a;
        }

        /* ── Page sections separated by margin ─────────────────────────────── */
        .page-section { margin-bottom: 32px; }

        /* ── Welcome strip ──────────────────────────────────────────────────── */
        .welcome-strip {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
            border-radius: 16px;
            padding: 22px 26px;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }
        .welcome-name { font-size: 1.3rem; font-weight: 800; margin: 0 0 4px; }
        .welcome-sub  { font-size: 0.82rem; color: #94a3b8; margin: 0; }
        .welcome-badges { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }

        /* ── Metric grid ─────────────────────────────────────────────────────── */
        .metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 14px;
        }
        .metric-card {
            background: #f8faff;
            border: 1px solid #e8edf5;
            border-radius: 13px;
            padding: 16px 18px;
        }
        .metric-value {
            font-size: 2rem;
            font-weight: 900;
            color: #0f172a;
            line-height: 1;
        }
        .metric-value.warn  { color: #b45309; }
        .metric-value.error { color: #991b1b; }
        .metric-value.ok    { color: #166534; }
        .metric-label { font-size: 0.75rem; color: #6b7280; margin-top: 5px; }

        /* ── Destination chips ───────────────────────────────────────────────── */
        .dest-row { display: flex; gap: 12px; flex-wrap: wrap; }
        .dest-chip {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 18px;
            border: 1px solid #dbeafe;
            border-radius: 14px;
            background: #f0f9ff;
            text-decoration: none;
            color: #0f172a;
            transition: background 140ms, border-color 140ms, transform 120ms;
        }
        .dest-chip:hover {
            background: #dbeafe;
            border-color: #93c5fd;
            transform: translateY(-1px);
        }
        .dest-chip-iata  { font-size: 1.4rem; font-weight: 900; letter-spacing: -0.04em; }
        .dest-chip-label { font-size: 0.75rem; color: #64748b; }
        .dest-chip-arrow { font-size: 0.8rem; color: #93c5fd; }

        /* ── Alert / panel lists ─────────────────────────────────────────────── */
        .panel-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
        }
        .panel {
            border: 1px solid #e8edf5;
            border-radius: 14px;
            padding: 18px;
            background: #fafcff;
        }
        .panel h3 { margin: 0 0 14px; font-size: 0.88rem; color: #374151; }
        .item-list { display: grid; gap: 10px; list-style: none; padding: 0; margin: 0; }
        .list-item {
            padding: 12px 14px;
            border: 1px solid #e8edf5;
            border-radius: 11px;
            background: #fff;
            font-size: 0.82rem;
        }
        .list-item.error   { border-color: #fecaca; background: #fff7f7; }
        .list-item.warning { border-color: #fde68a; background: #fffdf2; }

        /* ── Status badges ───────────────────────────────────────────────────── */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .badge-error   { background: #fee2e2; color: #991b1b; }
        .badge-warn    { background: #fef3c7; color: #92400e; }
        .badge-ok      { background: #dcfce7; color: #166534; }
        .badge-info    { background: #dbeafe; color: #1d4ed8; }
        .badge-purple  { background: #ede9fe; color: #6d28d9; }
        .badge-gray    { background: #f1f5f9; color: #475569; }
        .badge-teal    { background: #ccfbf1; color: #0f766e; }
        .run-status-completed { background: #dcfce7; color: #166534; }
        .run-status-failed    { background: #fee2e2; color: #991b1b; }
        .run-status-running   { background: #dbeafe; color: #1d4ed8; }

        /* ── Token section ───────────────────────────────────────────────────── */
        .token-box {
            background: #0f172a;
            color: #f8fafc;
            border-radius: 11px;
            padding: 14px 16px;
            margin-bottom: 16px;
            font-size: 0.82rem;
        }
        .token-box code {
            display: block;
            margin-top: 8px;
            padding: 10px;
            border-radius: 8px;
            background: rgba(255,255,255,0.08);
            overflow-x: auto;
            word-break: break-all;
            font-size: 0.78rem;
        }
        .token-list { list-style: none; padding: 0; margin: 16px 0 0; display: grid; gap: 10px; }
        .token-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border: 1px solid #e8edf5;
            border-radius: 11px;
            font-size: 0.82rem;
        }
        .token-meta { color: #6b7280; font-size: 0.75rem; margin-top: 3px; }

        /* ── Forms ───────────────────────────────────────────────────────────── */
        .form-inline {
            display: flex;
            gap: 10px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        .form-field { display: flex; flex-direction: column; gap: 5px; flex: 1; min-width: 160px; }
        .form-label { font-size: 0.78rem; font-weight: 600; color: #374151; }
        input[type=text], input[type=email], input[type=password] {
            padding: 9px 12px;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            font: inherit;
            font-size: 0.88rem;
            background: #fff;
            width: 100%;
        }
        input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.12); }

        /* ── Buttons ─────────────────────────────────────────────────────────── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 16px;
            border-radius: 9px;
            font: inherit;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: opacity 120ms, transform 80ms;
            white-space: nowrap;
        }
        .btn:hover { opacity: 0.88; }
        .btn-primary  { background: #1d4ed8; color: #fff; }
        .btn-ghost    { background: #f1f5f9; color: #374151; }
        .btn-danger   { background: #fee2e2; color: #991b1b; }
        .btn-sm       { padding: 6px 12px; font-size: 0.78rem; }

        /* ── Status flash ────────────────────────────────────────────────────── */
        .flash { padding: 11px 16px; border-radius: 11px; margin-bottom: 20px; font-size: 0.85rem; }
        .flash-ok    { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .flash-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        /* ── Responsive ──────────────────────────────────────────────────────── */
        @media (max-width: 768px) {
            .shell { grid-template-columns: 1fr; }
            .sidebar { display: none; }
            .content { padding: 20px 16px 48px; }
        }
    </style>
</head>
<body>

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- TOP NAV                                                                 --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
@php $authUser = auth()->user(); @endphp
<nav class="topnav">
    <a href="{{ route('dashboard') }}" class="topnav-brand">
        <div class="topnav-brand-dot"></div>
        Predictor
    </a>

    <div class="topnav-user">
        <span class="topnav-user-name">{{ $authUser->name }}</span>

        @php
            $roleColors = [
                'super_admin' => 'background:#fef3c7;color:#92400e',
                'admin'       => 'background:#dbeafe;color:#1d4ed8',
                'member'      => 'background:#dcfce7;color:#166534',
                'visitor'     => 'background:#f1f5f9;color:#475569',
            ];
        @endphp
        <span class="topnav-badge"
              style="{{ $roleColors[$authUser->role] ?? 'background:#f1f5f9;color:#475569' }}">
            {{ $authUser->role_label }}
        </span>

        <form method="POST" action="{{ route('logout') }}" style="margin:0">
            @csrf
            <button type="submit" class="topnav-logout">Sign out</button>
        </form>
    </div>
</nav>

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- SHELL (sidebar + content)                                               --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div class="shell">

    {{-- ── SIDEBAR ──────────────────────────────────────────────────────── --}}
    <aside class="sidebar">
        <div class="sidebar-section">General</div>
        <a href="{{ route('dashboard') }}" class="nav-link active">
            <i class="nav-icon">⊞</i> Dashboard
        </a>

        @if ($authUser->hasMinRole('member'))
            <a href="#tokens" class="nav-link">
                <i class="nav-icon">🔑</i> API Tokens
            </a>
        @endif

        @if ($authUser->hasMinRole('admin'))
            <div class="sidebar-section">Admin</div>
            <a href="{{ route('admin.cities.index') }}" class="nav-link teal">
                <i class="nav-icon">🌍</i> City Impact
            </a>
            <a href="{{ route('admin.ops.index') }}" class="nav-link">
                <i class="nav-icon">📡</i> Operations
            </a>
            <a href="{{ route('admin.providers.index') }}" class="nav-link">
                <i class="nav-icon">🔌</i> Providers
            </a>
            <a href="{{ route('admin.routes.index') }}" class="nav-link">
                <i class="nav-icon">🛫</i> Routes
            </a>
            <a href="{{ route('admin.data-inspection.index') }}" class="nav-link">
                <i class="nav-icon">🔬</i> Data Inspection
            </a>
        @endif

        @if ($authUser->isSuperAdmin())
            <div class="sidebar-section">Super Admin</div>
            <a href="{{ route('super-admin.destinations.index') }}" class="nav-link purple">
                <i class="nav-icon">🗺️</i> Destinations
            </a>
            <a href="{{ route('super-admin.rss-sources.index') }}" class="nav-link purple">
                <i class="nav-icon">📰</i> RSS Sources
            </a>
            <a href="{{ route('super-admin.memberships.index') }}" class="nav-link purple">
                <i class="nav-icon">💳</i> Memberships
            </a>
            <a href="{{ route('super-admin.users.index') }}" class="nav-link purple">
                <i class="nav-icon">👥</i> All Users
            </a>
        @endif
    </aside>

    {{-- ── MAIN CONTENT ─────────────────────────────────────────────────── --}}
    <main class="content">

        {{-- Flash messages --}}
        @if (session('status'))
            <div class="flash flash-ok">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="flash flash-error">{{ $errors->first() }}</div>
        @endif

        {{-- ── WELCOME STRIP ────────────────────────────────────────────── --}}
        <div class="welcome-strip">
            <div>
                <p class="welcome-name">{{ $user->name }}</p>
                <p class="welcome-sub">{{ $user->email }}</p>
            </div>
            <div class="welcome-badges">
                @if ($user->activeMembership)
                    <span class="badge badge-info">
                        {{ $user->activeMembership->plan?->name ?? 'Membership' }}
                    </span>
                @endif
                @if ($user->isVisitor())
                    <span class="badge badge-gray" style="background:rgba(255,255,255,0.1);color:#94a3b8;">
                        Visitor — limited access
                    </span>
                @endif
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════════════════ --}}
        {{-- ADMIN SECTIONS                                                    --}}
        {{-- ════════════════════════════════════════════════════════════════ --}}
        @if ($authUser->hasMinRole('admin'))

            {{-- ── Monitored Destinations ─────────────────────────────────── --}}
            <div class="page-section">
                <div class="section-head">
                    <div>
                        <h2>Monitored Destinations</h2>
                        <p>Cities being tracked for inbound travel impact. Click any to view its news analysis.</p>
                    </div>
                    <a href="{{ route('admin.cities.index') }}" class="btn btn-ghost btn-sm">All Cities →</a>
                </div>

                <div class="dest-row">
                    @forelse ($monitoredDestinations as $dest)
                        <a href="{{ route('admin.cities.show', $dest->iata) }}" class="dest-chip">
                            <div>
                                <div class="dest-chip-iata">{{ $dest->iata }}</div>
                                <div class="dest-chip-label">
                                    {{ $dest->label ?: ($dest->airport?->city?->name ?? 'Unknown') }}
                                </div>
                            </div>
                            <span class="dest-chip-arrow">→</span>
                        </a>
                    @empty
                        @foreach ($fallbackIatas as $iata)
                            <a href="{{ route('admin.cities.show', $iata) }}" class="dest-chip">
                                <div>
                                    <div class="dest-chip-iata">{{ $iata }}</div>
                                    <div class="dest-chip-label">from config</div>
                                </div>
                                <span class="dest-chip-arrow">→</span>
                            </a>
                        @endforeach
                        @if (empty($fallbackIatas))
                            <p style="color:#94a3b8;font-size:0.85rem;margin:0;">
                                No destinations configured.
                                @if ($authUser->isSuperAdmin())
                                    <a href="{{ route('super-admin.destinations.index') }}" style="color:#6d28d9;">Add one →</a>
                                @endif
                            </p>
                        @endif
                    @endforelse

                    @if ($authUser->isSuperAdmin())
                        <a href="{{ route('super-admin.destinations.index') }}"
                           class="dest-chip" style="border-style:dashed;background:#fafaff;color:#6d28d9;border-color:#c4b5fd;">
                            <div>
                                <div style="font-size:1.4rem;font-weight:900;">＋</div>
                                <div class="dest-chip-label" style="color:#6d28d9;">Manage</div>
                            </div>
                        </a>
                    @endif
                </div>
            </div>

            {{-- ── System Health ───────────────────────────────────────────── --}}
            <div class="page-section">
                <div class="section-head">
                    <div>
                        <h2>System Health</h2>
                        <p>At-a-glance ingestion and queue status.</p>
                    </div>
                    <a href="{{ route('admin.ops.index') }}" class="btn btn-ghost btn-sm">Full Operations →</a>
                </div>

                <div class="metric-grid">
                    <div class="metric-card">
                        <div class="metric-value {{ $stats['failed_jobs_24h'] > 0 ? 'error' : 'ok' }}">
                            {{ $stats['failed_jobs_24h'] }}
                        </div>
                        <div class="metric-label">Failed jobs (24h)</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value {{ $stats['failed_ingestion_runs_24h'] > 0 ? 'error' : 'ok' }}">
                            {{ $stats['failed_ingestion_runs_24h'] }}
                        </div>
                        <div class="metric-label">Failed ingestion runs (24h)</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value {{ $stats['stale_warning_count'] > 0 ? 'warn' : 'ok' }}">
                            {{ $stats['stale_warning_count'] }}
                        </div>
                        <div class="metric-label">Stale provider warnings</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value {{ $stats['pending_normalization_count'] > 5 ? 'warn' : 'ok' }}">
                            {{ $stats['pending_normalization_count'] }}
                        </div>
                        <div class="metric-label">Normalization backlog</div>
                    </div>
                </div>

                {{-- Alerts strip (only when there are actual alerts) --}}
                @php $alertCount = count($alerts); @endphp
                @if ($alertCount > 0)
                    <div style="margin-top: 16px; display: grid; gap: 10px;">
                        @foreach (array_slice($alerts, 0, 3) as $alert)
                            <div class="list-item {{ $alert['level'] }}">
                                <span class="badge badge-{{ $alert['level'] === 'error' ? 'error' : 'warn' }}">
                                    {{ $alert['level'] }}
                                </span>
                                <strong style="margin-left:8px;font-size:0.85rem;">{{ $alert['title'] }}</strong>
                                <span style="color:#6b7280;font-size:0.82rem;margin-left:6px;">— {{ $alert['message'] }}</span>
                            </div>
                        @endforeach
                        @if ($alertCount > 3)
                            <p style="font-size:0.8rem;color:#6b7280;margin:0;">
                                +{{ $alertCount - 3 }} more alerts.
                                <a href="{{ route('admin.ops.index') }}" style="color:#1d4ed8;">View all →</a>
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- ── Recent Activity (two-column) ───────────────────────────── --}}
            <div class="page-section">
                <div class="section-head">
                    <div>
                        <h2>Recent Activity</h2>
                        <p>Failed jobs and ingestion run history.</p>
                    </div>
                    <form method="POST" action="{{ route('admin.ops.logs.clear') }}" style="margin:0"
                          onsubmit="return confirm('Clear all failed jobs and old ingestion run logs?')">
                        @csrf
                        <input type="hidden" name="redirect_back" value="dashboard">
                        <button type="submit" class="btn btn-ghost btn-sm"
                                style="color:#991b1b;border:1px solid #fecaca;">
                            🗑 Clear logs
                        </button>
                    </form>
                </div>
                <div class="panel-grid">

                    <div class="panel">
                        <h3>Ingestion Runs</h3>
                        <ul class="item-list">
                            @forelse ($recentIngestionRuns->take(5) as $run)
                                <li class="list-item">
                                    <span class="badge run-status-{{ $run->status }}">{{ $run->status }}</span>
                                    <strong style="margin-left:8px;">
                                        {{ $run->provider?->name ?? 'Unknown' }}
                                    </strong>
                                    <span style="color:#94a3b8;margin-left:4px;">
                                        · {{ strtoupper($run->source_type) }}
                                    </span>
                                    <div class="token-meta" style="margin-top:4px;">
                                        {{ $run->started_at?->diffForHumans() ?? 'n/a' }}
                                        @if ($run->error_message)
                                            — <span style="color:#991b1b;">{{ \Illuminate\Support\Str::limit($run->error_message, 80) }}</span>
                                        @endif
                                    </div>
                                </li>
                            @empty
                                <li class="list-item" style="color:#94a3b8;">No ingestion runs yet.</li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="panel">
                        <h3>Failed Jobs</h3>
                        <ul class="item-list">
                            @forelse ($recentFailedJobs->take(5) as $job)
                                <li class="list-item error">
                                    <strong>{{ $job->queue }}</strong>
                                    <div class="token-meta" style="margin-top:4px;">
                                        {{ $job->failed_at?->diffForHumans() ?? 'n/a' }}
                                    </div>
                                    <div style="color:#991b1b;font-size:0.78rem;margin-top:4px;">
                                        {{ \Illuminate\Support\Str::limit($job->exception, 120) }}
                                    </div>
                                </li>
                            @empty
                                <li class="list-item" style="color:#166534;">
                                    ✓ No failed jobs.
                                </li>
                            @endforelse
                        </ul>
                    </div>

                </div>
            </div>

        @endif {{-- end admin --}}

        {{-- ════════════════════════════════════════════════════════════════ --}}
        {{-- API TOKENS (members and above)                                   --}}
        {{-- ════════════════════════════════════════════════════════════════ --}}
        @if ($authUser->hasMinRole('member'))
            <div class="page-section" id="tokens">
                <div class="section-head">
                    <div>
                        <h2>API Tokens</h2>
                        <p>Bearer tokens for external consumers. Leave abilities blank for full API access.</p>
                    </div>
                </div>

                <div class="card">
                    @if ($plainTextToken)
                        <div class="token-box">
                            <strong>New token created — copy it now, it won't be shown again.</strong>
                            <code>{{ $plainTextToken }}</code>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('tokens.store') }}">
                        @csrf
                        <div class="form-inline">
                            <div class="form-field">
                                <label class="form-label">Token name *</label>
                                <input type="text" name="token_name" value="{{ old('token_name') }}"
                                       placeholder="Partner integration" required>
                            </div>
                            <div class="form-field">
                                <label class="form-label">Abilities</label>
                                <input type="text" name="abilities" value="{{ old('abilities') }}"
                                       placeholder="orders:read, bookings:write">
                            </div>
                            <button type="submit" class="btn btn-primary" style="flex-shrink:0;">Create token</button>
                        </div>
                    </form>

                    @if ($tokens->isNotEmpty())
                        <ul class="token-list">
                            @foreach ($tokens as $token)
                                <li class="token-item">
                                    <div>
                                        <strong>{{ $token->name }}</strong>
                                        <div class="token-meta">
                                            Abilities: {{ $token->abilities ? implode(', ', $token->abilities) : '*' }}
                                            · Created {{ $token->created_at?->diffForHumans() ?? 'n/a' }}
                                            · Last used: {{ $token->last_used_at?->diffForHumans() ?? 'Never' }}
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('tokens.destroy', $token) }}" style="margin:0">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Revoke</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p style="color:#94a3b8;font-size:0.82rem;margin:16px 0 0;">No tokens issued yet.</p>
                    @endif
                </div>
            </div>
        @else
            {{-- Visitor upgrade nudge --}}
            <div class="card" style="border-style:dashed;background:#fffdf2;border-color:#fde68a;">
                <p style="margin:0;font-size:0.88rem;color:#92400e;">
                    <strong>Visitor account</strong> — API tokens and advanced features require a membership.
                    Contact an admin to upgrade your account.
                </p>
            </div>
        @endif

    </main>{{-- .content --}}
</div>{{-- .shell --}}

</body>
</html>
