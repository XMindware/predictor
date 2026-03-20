<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Predictor Dashboard</title>
    <style>
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, sans-serif;
            background: #f5f7fb;
            color: #172033;
        }
        main {
            max-width: 960px;
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
        .stack {
            display: grid;
            gap: 24px;
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
        form {
            margin: 0;
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
            border: 0;
            border-radius: 10px;
            padding: 12px 16px;
            background: #1d4ed8;
            color: #fff;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }
        .actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .button-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            padding: 12px 16px;
            background: #0f172a;
            color: #fff;
            font-weight: 700;
            text-decoration: none;
        }
        .secondary {
            background: #e2e8f0;
            color: #172033;
        }
        .status, .token {
            margin: 0 0 20px;
            padding: 14px;
            border-radius: 12px;
        }
        .status {
            background: #dcfce7;
            color: #166534;
        }
        .token {
            background: #172033;
            color: #f8fafc;
        }
        code {
            display: block;
            margin-top: 8px;
            padding: 12px;
            border-radius: 10px;
            overflow-x: auto;
            background: rgba(255, 255, 255, 0.08);
        }
        ul {
            list-style: none;
            padding: 0;
            margin: 24px 0 0;
            display: grid;
            gap: 12px;
        }
        li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 16px;
            border: 1px solid #dbe2ef;
            border-radius: 12px;
        }
        .meta {
            color: #5b667a;
            font-size: 14px;
        }
        .metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }
        .metric {
            padding: 18px;
            border: 1px solid #dbe2ef;
            border-radius: 14px;
            background: #f8fbff;
        }
        .metric strong {
            display: block;
            font-size: 28px;
            color: #0f172a;
        }
        .metric span {
            display: block;
            margin-top: 6px;
            color: #5b667a;
            font-size: 14px;
        }
        .panel-grid {
            display: grid;
            gap: 24px;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            margin-top: 24px;
        }
        .panel {
            border: 1px solid #dbe2ef;
            border-radius: 14px;
            padding: 18px;
            background: #fbfdff;
        }
        .panel h3 {
            margin-top: 0;
            margin-bottom: 12px;
        }
        .alert-list, .monitor-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 12px;
        }
        .alert-item, .monitor-item {
            border: 1px solid #dbe2ef;
            border-radius: 12px;
            padding: 14px;
            background: #fff;
        }
        .alert-item.error {
            border-color: #fecaca;
            background: #fff7f7;
        }
        .alert-item.warning {
            border-color: #fde68a;
            background: #fffdf2;
        }
        .eyebrow {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }
        .eyebrow.error {
            background: #fee2e2;
            color: #991b1b;
        }
        .eyebrow.warning {
            background: #fef3c7;
            color: #92400e;
        }
        .eyebrow.ok {
            background: #dcfce7;
            color: #166534;
        }
        .run-status {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            background: #e2e8f0;
            color: #334155;
        }
        .run-status.failed {
            background: #fee2e2;
            color: #991b1b;
        }
        .run-status.completed {
            background: #dcfce7;
            color: #166534;
        }
        .run-status.running {
            background: #dbeafe;
            color: #1d4ed8;
        }
    </style>
</head>
<body>
<main>
    <div class="stack">
        <header>
            <div>
                <h1>{{ $user->name }}</h1>
                <p>{{ $user->email }}</p>
            </div>

            <div class="actions">
                <a href="{{ route('admin.ops.index') }}" class="button-link secondary">Operations Panel</a>
                <a href="{{ route('admin.providers.index') }}" class="button-link">Manage Providers</a>
                <a href="{{ route('admin.routes.index') }}" class="button-link secondary">Manage Routes</a>
                <a href="{{ route('admin.data-inspection.index') }}" class="button-link secondary">Inspect Data</a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="secondary">Sign out</button>
                </form>
            </div>
        </header>

        <section>
            <h2>Operations Monitoring</h2>
            <p>Track recent ingestion activity, queue failures, and stale provider conditions from one internal screen.</p>

            <div class="metrics">
                <div class="metric">
                    <strong>{{ $stats['failed_jobs_24h'] }}</strong>
                    <span>Failed jobs in 24h</span>
                </div>
                <div class="metric">
                    <strong>{{ $stats['failed_ingestion_runs_24h'] }}</strong>
                    <span>Failed ingestion runs in 24h</span>
                </div>
                <div class="metric">
                    <strong>{{ $stats['stale_warning_count'] }}</strong>
                    <span>Stale provider warnings</span>
                </div>
                <div class="metric">
                    <strong>{{ $stats['pending_normalization_count'] }}</strong>
                    <span>Pending normalization backlog</span>
                </div>
            </div>

            <div class="panel-grid">
                <div class="panel">
                    <h3>Failure Alerts</h3>

                    <ul class="alert-list">
                        @forelse ($alerts as $alert)
                            <li class="alert-item {{ $alert['level'] }}">
                                <span class="eyebrow {{ $alert['level'] }}">{{ $alert['level'] }}</span>
                                <p><strong>{{ $alert['title'] }}</strong></p>
                                <p>{{ $alert['message'] }}</p>
                            </li>
                        @empty
                            <li class="alert-item">
                                <span class="eyebrow ok">ok</span>
                                <p><strong>No active alerts</strong></p>
                                <p>Recent failed jobs, ingestion failures, and stale provider warnings are clear.</p>
                            </li>
                        @endforelse
                    </ul>
                </div>

                <div class="panel">
                    <h3>Stale Provider Warnings</h3>

                    <ul class="monitor-list">
                        @forelse ($staleWarnings as $warning)
                            <li class="monitor-item">
                                <p><strong>{{ $warning['title'] }}</strong></p>
                                <p>{{ $warning['message'] }}</p>
                            </li>
                        @empty
                            <li class="monitor-item">Provider freshness checks are healthy.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="panel-grid">
                <div class="panel">
                    <h3>Recent Ingestion Runs</h3>

                    <ul class="monitor-list">
                        @forelse ($recentIngestionRuns as $run)
                            <li class="monitor-item">
                                <span class="run-status {{ $run->status }}">{{ $run->status }}</span>
                                <p>
                                    <strong>{{ $run->provider?->name ?? 'Unknown provider' }}</strong>
                                    · {{ strtoupper($run->source_type) }}
                                </p>
                                <p class="meta">
                                    Started: {{ $run->started_at?->toDayDateTimeString() ?? 'n/a' }}<br>
                                    Finished: {{ $run->finished_at?->toDayDateTimeString() ?? 'In progress' }}
                                </p>
                                @if ($run->error_message)
                                    <p>{{ $run->error_message }}</p>
                                @endif
                            </li>
                        @empty
                            <li class="monitor-item">No ingestion runs recorded yet.</li>
                        @endforelse
                    </ul>
                </div>

                <div class="panel">
                    <h3>Recent Failed Jobs</h3>

                    <ul class="monitor-list">
                        @forelse ($recentFailedJobs as $job)
                            <li class="monitor-item">
                                <p><strong>{{ $job->queue }}</strong> · {{ $job->failed_at?->toDayDateTimeString() ?? 'n/a' }}</p>
                                <p class="meta">{{ $job->connection }}</p>
                                <p>{{ \Illuminate\Support\Str::limit($job->exception, 180) }}</p>
                            </li>
                        @empty
                            <li class="monitor-item">No failed jobs recorded.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </section>

        <section>
            <h2>Issue API Token</h2>
            <p>Create a bearer token for external consumers. Leave abilities blank to grant full API access.</p>

            @if (session('status'))
                <div class="status">{{ session('status') }}</div>
            @endif

            @if ($plainTextToken)
                <div class="token">
                    New token
                    <code>{{ $plainTextToken }}</code>
                </div>
            @endif

            <form method="POST" action="{{ route('tokens.store') }}">
                @csrf
                <label>
                    Token name
                    <input type="text" name="token_name" value="{{ old('token_name') }}" placeholder="Partner integration" required>
                </label>

                <label>
                    Abilities
                    <input type="text" name="abilities" value="{{ old('abilities') }}" placeholder="orders:read, bookings:write">
                </label>

                <button type="submit">Create token</button>
            </form>

            <ul>
                @forelse ($tokens as $token)
                    <li>
                        <div>
                            <strong>{{ $token->name }}</strong>
                            <div class="meta">
                                Abilities: {{ $token->abilities ? implode(', ', $token->abilities) : '*' }}<br>
                                Created: {{ $token->created_at?->toDayDateTimeString() ?? 'n/a' }}<br>
                                Last used: {{ $token->last_used_at?->toDayDateTimeString() ?? 'Never' }}
                            </div>
                        </div>

                        <form method="POST" action="{{ route('tokens.destroy', $token) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="secondary">Revoke</button>
                        </form>
                    </li>
                @empty
                    <li>No API tokens issued yet.</li>
                @endforelse
            </ul>
        </section>
    </div>
</main>
</body>
</html>
