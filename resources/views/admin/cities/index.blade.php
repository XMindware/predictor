<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>City Travel Impact — Predictor</title>
    <style>
        :root {
            --bg: #f4efe4;
            --paper: rgba(255,251,243,0.85);
            --card: rgba(255,248,237,0.95);
            --line: rgba(61,44,28,0.14);
            --text: #1e1712;
            --muted: #645447;
            --accent: #d96c2f;
            --accent-strong: #b74f1d;
            --teal: #1e7a78;
            --shadow: 0 8px 32px rgba(64,38,20,0.10);
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', system-ui, sans-serif; color: var(--text);
            background: radial-gradient(circle at top left, rgba(217,108,47,.14), transparent 30%),
                        linear-gradient(180deg, #f9f3ea 0%, var(--bg) 100%); min-height: 100vh; }
        main { max-width: 1280px; margin: 0 auto; padding: 36px 24px 80px; }
        .topbar { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 28px; flex-wrap: wrap; }
        .breadcrumb { font-size: .83rem; color: var(--muted); margin-bottom: 6px; }
        .breadcrumb a { color: var(--accent-strong); text-decoration: none; font-weight: 600; }
        h1 { margin: 0 0 4px; font-size: 1.65rem; letter-spacing: -.03em; }
        .topbar p { margin: 0; color: var(--muted); font-size: .93rem; }
        .controls { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .btn { display: inline-flex; align-items: center; padding: 9px 16px; border-radius: 10px;
               font: inherit; font-size: .88rem; font-weight: 700; cursor: pointer; border: none;
               text-decoration: none; transition: opacity 150ms; }
        .btn:hover { opacity: .82; }
        .btn-primary { background: var(--text); color: #fff8f1; }
        .btn-secondary { background: rgba(255,255,255,.7); color: var(--text); border: 1px solid var(--line); }
        .btn-accent { background: var(--accent); color: #fff8f1; }
        .btn-sm { padding: 6px 12px; font-size: .8rem; }
        select { padding: 8px 12px; border: 1px solid var(--line); border-radius: 10px; font: inherit; font-size: .88rem; background: rgba(255,255,255,.7); color: var(--text); cursor: pointer; }
        .alert-success { padding: 12px 16px; border-radius: 12px; background: #dcfce7; color: #166534; margin-bottom: 20px; font-size: .9rem; }
        /* Grid layout */
        .dest-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 20px; }
        /* Destination card */
        .dest-card { background: var(--card); border: 1px solid var(--line); border-radius: 22px; overflow: hidden; box-shadow: var(--shadow); transition: transform 180ms ease, box-shadow 180ms ease; }
        .dest-card:hover { transform: translateY(-3px); box-shadow: 0 16px 48px rgba(64,38,20,.16); }
        .dest-header { padding: 20px 22px 16px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
        .dest-iata { font-size: 2.4rem; font-weight: 800; letter-spacing: -.06em; line-height: 1; }
        .dest-name { font-size: .85rem; color: var(--muted); margin-top: 4px; }
        .impact-badge { padding: 6px 12px; border-radius: 999px; font-size: .75rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; }
        .dest-body { padding: 18px 22px; }
        /* Metrics row */
        .metrics { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 16px; }
        .metric { padding: 12px 14px; border: 1px solid var(--line); border-radius: 14px; background: rgba(255,255,255,.5); }
        .metric-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .07em; color: var(--muted); }
        .metric-value { font-size: 1.4rem; font-weight: 800; letter-spacing: -.04em; margin-top: 5px; }
        .metric-sub { font-size: .78rem; color: var(--muted); margin-top: 3px; }
        /* Sparkbar chart */
        .sparkbar { display: flex; align-items: flex-end; gap: 3px; height: 36px; margin-bottom: 14px; }
        .sparkbar-col { flex: 1; border-radius: 4px 4px 0 0; min-height: 3px; transition: opacity 150ms; background: var(--accent); opacity: .7; }
        .sparkbar-col:hover { opacity: 1; }
        /* Category pills */
        .categories { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 16px; }
        .cat-pill { padding: 3px 10px; border-radius: 999px; font-size: .75rem; font-weight: 700; }
        .cat-weather { background: #dbeafe; color: #1e40af; }
        .cat-operations { background: #fef3c7; color: #92400e; }
        .cat-airline { background: #f3e8ff; color: #6b21a8; }
        .cat-monitoring { background: #f1f5f9; color: #475569; }
        /* Watch targets bar */
        .watch-row { display: flex; align-items: center; justify-content: space-between; padding-top: 14px; border-top: 1px solid var(--line); }
        .watch-label { font-size: .82rem; color: var(--muted); }
        .watch-count { font-size: .82rem; font-weight: 700; }
        .watch-enabled { color: #166534; }
        .watch-disabled { color: #991b1b; }
        /* Nav */
        nav.page-nav { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 24px; }
        nav.page-nav a { padding: 8px 16px; border-radius: 10px; font-size: .88rem; font-weight: 600; text-decoration: none; border: 1px solid var(--line); background: rgba(255,255,255,.6); color: var(--text); }
        nav.page-nav a.active { background: var(--text); color: #fff8f1; border-color: var(--text); }
        .empty-state { text-align: center; padding: 60px; color: var(--muted); }
    </style>
</head>
<body>
<main>
    <div class="breadcrumb">
        <a href="{{ route('dashboard') }}">Dashboard</a> / City Travel Impact
    </div>

    <nav class="page-nav">
        <a href="{{ route('admin.cities.index') }}" class="{{ !$showAll ? 'active' : '' }}">
            Primary Destinations
        </a>
        <a href="{{ route('admin.cities.index', ['all' => 1, 'days' => $days]) }}" class="{{ $showAll ? 'active' : '' }}">
            All Monitored Cities
        </a>
        <a href="{{ route('admin.ops.index') }}">Ops Panel</a>
        <a href="{{ route('dashboard') }}">← Dashboard</a>
    </nav>

    <div class="topbar">
        <div>
            <h1>City Travel Impact</h1>
            <p>
                News impact on travel to
                @if($showAll) all monitored destinations
                @else {{ implode(', ', $baseIatas) }} (primary destinations)
                @endif
                · last {{ $days }} day{{ $days !== 1 ? 's' : '' }}
            </p>
        </div>

        <form method="GET" class="controls">
            @if($showAll)
                <input type="hidden" name="all" value="1">
            @endif
            <select name="days" onchange="this.form.submit()">
                @foreach([1, 3, 7, 14, 30] as $d)
                    <option value="{{ $d }}" {{ $days === $d ? 'selected' : '' }}>{{ $d }} day{{ $d !== 1 ? 's' : '' }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if (session('status'))
        <div class="alert-success">{{ session('status') }}</div>
    @endif

    @if ($summaries->isEmpty())
        <div class="empty-state">
            <p style="font-size:1.1rem; font-weight:700;">No destination data yet</p>
            <p>Run <code>php artisan ingestion:fetch-news</code> to start pulling city-level news data.</p>
        </div>
    @else
        <div class="dest-grid">
            @foreach ($summaries as $report)
                @php
                    $maxDay = max(array_values($report['by_day']) ?: [0]) ?: 1;
                @endphp
                <div class="dest-card">
                    <div class="dest-header">
                        <div>
                            <div class="dest-iata">{{ $report['airport']->iata }}</div>
                            <div class="dest-name">{{ $report['city']?->name ?? '—' }}</div>
                        </div>
                        <span class="impact-badge"
                              style="background: {{ $report['impact_color'] }}22; color: {{ $report['impact_color'] }};">
                            {{ strtoupper($report['impact_level']) }} IMPACT
                        </span>
                    </div>

                    <div class="dest-body">
                        {{-- Metrics row --}}
                        <div class="metrics">
                            <div class="metric">
                                <div class="metric-label">News Events</div>
                                <div class="metric-value">{{ $report['total_events'] }}</div>
                                <div class="metric-sub">last {{ $days }}d</div>
                            </div>
                            <div class="metric">
                                <div class="metric-label">Avg Severity</div>
                                <div class="metric-value" style="color: {{ $report['impact_color'] }};">
                                    {{ $report['avg_severity'] > 0 ? number_format($report['avg_severity'], 1) : '—' }}
                                </div>
                                <div class="metric-sub">out of 10</div>
                            </div>
                            <div class="metric">
                                <div class="metric-label">Peak</div>
                                <div class="metric-value">
                                    {{ $report['peak_severity'] > 0 ? number_format($report['peak_severity'], 1) : '—' }}
                                </div>
                                <div class="metric-sub">max severity</div>
                            </div>
                        </div>

                        {{-- Sparkbar (7-day event volume) --}}
                        @if ($report['total_events'] > 0)
                            <div class="sparkbar" title="Daily event volume">
                                @foreach ($report['by_day'] as $date => $count)
                                    @php $h = max(3, (int) round(($count / $maxDay) * 36)); @endphp
                                    <div class="sparkbar-col"
                                         style="height: {{ $h }}px; background: {{ $report['impact_color'] }};"
                                         title="{{ $date }}: {{ $count }} event{{ $count !== 1 ? 's' : '' }}"></div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Categories --}}
                        @if (!empty($report['by_category']))
                            <div class="categories">
                                @foreach ($report['by_category'] as $cat => $count)
                                    <span class="cat-pill cat-{{ $cat }}">{{ $cat }} ({{ $count }})</span>
                                @endforeach
                            </div>
                        @endif

                        {{-- Top article preview --}}
                        @if ($report['top_events']->isNotEmpty())
                            @php $top = $report['top_events']->first(); @endphp
                            <p style="margin: 0 0 14px; font-size: .84rem; color: var(--muted); line-height: 1.5;">
                                <strong style="color: var(--text);">Top:</strong>
                                @if($top->url)
                                    <a href="{{ $top->url }}" target="_blank" rel="noopener"
                                       style="color: var(--accent-strong); text-decoration: none;">
                                        {{ Str::limit($top->title, 80) }}
                                    </a>
                                @else
                                    {{ Str::limit($top->title, 80) }}
                                @endif
                            </p>
                        @endif

                        {{-- Watch targets + detail link --}}
                        <div class="watch-row">
                            <span class="watch-label">
                                <span class="watch-count watch-enabled">{{ $report['active_watch_targets'] }} active</span>
                                / {{ $report['watch_targets']->count() }} watch targets
                            </span>
                            <a href="{{ route('admin.cities.show', ['iata' => $report['airport']->iata, 'days' => $days]) }}"
                               class="btn btn-secondary btn-sm">
                                Details →
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</main>
</body>
</html>
