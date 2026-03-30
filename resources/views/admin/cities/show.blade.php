<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $airport->iata }} Travel Impact — Predictor</title>
    <style>
        :root {
            --bg: #f0f2f8; --paper: #fff; --card: #fff;
            --line: #e8edf5; --text: #0f172a; --muted: #64748b;
            --accent: #1d4ed8; --accent-hover: #1e40af;
            --shadow: 0 2px 12px rgba(23,32,51,.06);
            --impact: {{ $report['impact_color'] }};
        }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: var(--bg); color: var(--text); min-height: 100vh;
        }
        main { max-width: 1140px; margin: 0 auto; padding: 32px 24px 80px; }

        /* ── breadcrumb ──────────────────────────────────────────────────── */
        .breadcrumb { font-size: .82rem; color: var(--muted); margin-bottom: 16px; }
        .breadcrumb a { color: var(--accent); text-decoration: none; font-weight: 600; }
        .breadcrumb a:hover { text-decoration: underline; }

        /* ── page header ─────────────────────────────────────────────────── */
        .page-header {
            display: flex; align-items: flex-start; justify-content: space-between;
            gap: 16px; margin-bottom: 24px; flex-wrap: wrap;
        }
        .iata-large {
            font-size: 3.2rem; font-weight: 900; letter-spacing: -.06em;
            line-height: 1; color: var(--text);
        }
        .city-name { font-size: 1rem; color: var(--muted); margin-top: 4px; }
        .impact-badge {
            display: inline-block; margin-top: 10px;
            padding: 6px 14px; border-radius: 999px;
            font-size: .78rem; font-weight: 800;
            text-transform: uppercase; letter-spacing: .06em;
            background: color-mix(in srgb, var(--impact) 12%, white);
            color: var(--impact); border: 1px solid color-mix(in srgb, var(--impact) 30%, white);
        }
        .controls { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        select {
            padding: 8px 12px; border: 1px solid var(--line); border-radius: 10px;
            font: inherit; font-size: .88rem; background: #fff; color: var(--text); cursor: pointer;
        }

        /* ── buttons ─────────────────────────────────────────────────────── */
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 9px 16px; border-radius: 10px;
            font: inherit; font-size: .85rem; font-weight: 700;
            cursor: pointer; border: none; text-decoration: none;
            transition: opacity 130ms, transform 80ms; white-space: nowrap;
        }
        .btn:hover { opacity: .85; }
        .btn-primary  { background: var(--accent); color: #fff; }
        .btn-ghost    { background: #f1f5f9; border: 1px solid var(--line); color: var(--text); }
        .btn-accent   { background: #0f172a; color: #fff; }
        .btn-sm       { padding: 7px 13px; font-size: .8rem; }
        .btn-toggle-on  { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .btn-toggle-off { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        /* ── cards ───────────────────────────────────────────────────────── */
        .card {
            background: var(--card); border: 1px solid var(--line);
            border-radius: 16px; padding: 20px 24px; box-shadow: var(--shadow);
        }
        .card-title { font-size: .9rem; font-weight: 700; margin: 0 0 16px; color: var(--text); }

        /* ── stat strip ──────────────────────────────────────────────────── */
        .stats-strip {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 12px; margin-bottom: 24px;
        }
        .stat-card {
            background: var(--card); border: 1px solid var(--line);
            border-radius: 14px; padding: 16px 18px; box-shadow: var(--shadow);
        }
        .stat-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .07em; color: var(--muted); }
        .stat-value { font-size: 1.9rem; font-weight: 900; letter-spacing: -.04em; margin-top: 6px; line-height: 1; }
        .stat-sub   { font-size: .78rem; color: var(--muted); margin-top: 4px; }

        /* ── bar chart ───────────────────────────────────────────────────── */
        .bar-chart { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 18px 20px; box-shadow: var(--shadow); margin-bottom: 24px; }
        .bar-grid { display: flex; align-items: flex-end; gap: 4px; height: 72px; }
        .bar-col  { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 3px; }
        .bar      { width: 100%; border-radius: 4px 4px 0 0; min-height: 3px; background: var(--impact); opacity: .7; transition: opacity 130ms; }
        .bar:hover { opacity: 1; }
        .bar-date { font-size: .6rem; color: var(--muted); white-space: nowrap; transform: rotate(-45deg); transform-origin: left top; margin-top: 4px; }

        /* ── two-col grid ────────────────────────────────────────────────── */
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
        @media (max-width: 720px) { .two-col { grid-template-columns: 1fr; } }

        /* ── category bars ───────────────────────────────────────────────── */
        .cat-row   { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .cat-name  { font-size: .83rem; font-weight: 600; min-width: 90px; }
        .cat-bar-bg   { flex: 1; height: 7px; border-radius: 99px; background: #f1f5f9; overflow: hidden; }
        .cat-bar-fill { height: 100%; border-radius: 99px; }
        .cat-count { font-size: .78rem; color: var(--muted); min-width: 26px; text-align: right; }

        /* ── top-event panel ─────────────────────────────────────────────── */
        .top-event {
            display: flex; gap: 12px; align-items: flex-start;
            padding: 12px 0; border-bottom: 1px solid var(--line);
        }
        .top-event:last-child { border-bottom: none; padding-bottom: 0; }
        .top-event-score {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            width: 42px; min-width: 42px; height: 42px; border-radius: 10px;
            font-size: .72rem; font-weight: 800; text-align: center; line-height: 1.2;
        }
        .top-event-title { font-size: .87rem; font-weight: 600; line-height: 1.4; }
        .top-event-title a { color: var(--text); text-decoration: none; }
        .top-event-title a:hover { color: var(--accent); text-decoration: underline; }
        .top-event-meta { font-size: .76rem; color: var(--muted); margin-top: 3px; display: flex; gap: 8px; flex-wrap: wrap; }

        /* ── news feed section ───────────────────────────────────────────── */
        .news-feed-header {
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; margin-bottom: 14px; flex-wrap: wrap;
        }
        .news-feed-header h2 { margin: 0; font-size: 1.05rem; font-weight: 800; }
        .news-count-badge {
            display: inline-block; padding: 3px 10px; border-radius: 999px;
            background: #f1f5f9; color: var(--muted); font-size: .75rem; font-weight: 700;
        }

        .news-item {
            padding: 16px 0;
            border-bottom: 1px solid var(--line);
            display: grid;
            grid-template-columns: 44px 1fr 200px;
            gap: 16px;
            align-items: flex-start;
        }
        .news-item:last-child { border-bottom: none; padding-bottom: 0; }
        @media (max-width: 860px) {
            .news-item { grid-template-columns: 44px 1fr; }
            .news-context-card { display: none; }
        }
        .news-score-col {
            display: flex; flex-direction: column; gap: 4px; align-items: center;
            padding-top: 2px;
        }
        .score-ring {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            font-size: .68rem; font-weight: 800; line-height: 1.1; text-align: center;
        }
        .news-body {}
        .news-title {
            font-size: .92rem; font-weight: 700; margin: 0 0 5px; line-height: 1.4; color: var(--text);
        }
        .news-title a { color: inherit; text-decoration: none; }
        .news-title a:hover { color: var(--accent); text-decoration: underline; }
        .news-summary {
            font-size: .8rem; color: var(--muted); line-height: 1.55;
            margin: 0 0 7px; display: -webkit-box; -webkit-line-clamp: 2;
            -webkit-box-orient: vertical; overflow: hidden;
        }
        .news-meta {
            display: flex; gap: 8px; flex-wrap: wrap; align-items: center; font-size: .75rem; color: var(--muted);
        }
        .meta-chip {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 2px 8px; border-radius: 6px;
            font-size: .72rem; font-weight: 700;
            background: #f1f5f9; color: #475569;
        }
        .meta-chip.weather   { background: #dbeafe; color: #1d4ed8; }
        .meta-chip.operations{ background: #fef3c7; color: #92400e; }
        .meta-chip.airline   { background: #ede9fe; color: #6d28d9; }
        .meta-chip.general   { background: #f0fdf4; color: #166534; }

        /* ── right-side context card ─────────────────────────────────────── */
        .news-context-card {
            background: #f8faff;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            font-size: .76rem;
        }
        .ctx-section-label {
            font-size: .65rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .07em; color: #94a3b8; margin-bottom: 3px;
        }
        .ctx-relevance-text {
            font-size: .78rem; line-height: 1.5; color: #334155; font-weight: 500;
        }
        .ctx-source-name {
            font-weight: 700; color: var(--text); font-size: .8rem;
        }
        .ctx-source-domain {
            font-size: .72rem; color: var(--muted); margin-top: 2px;
            word-break: break-all;
        }
        .ctx-source-domain a {
            color: var(--accent); text-decoration: none;
        }
        .ctx-source-domain a:hover { text-decoration: underline; }
        .ctx-divider {
            border: none; border-top: 1px solid var(--line); margin: 0;
        }

        /* ── empty state ─────────────────────────────────────────────────── */
        .empty-state {
            text-align: center; padding: 48px 24px;
            border: 2px dashed var(--line); border-radius: 16px;
        }
        .empty-state-icon { font-size: 2.4rem; margin-bottom: 12px; }
        .empty-state h3 { margin: 0 0 8px; font-size: 1rem; }
        .empty-state p  { margin: 0 0 20px; color: var(--muted); font-size: .88rem; }

        /* ── watch targets table ─────────────────────────────────────────── */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 8px 12px; border-bottom: 2px solid var(--line); font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); }
        td { padding: 11px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-size: .86rem; }
        tr:last-child td { border-bottom: none; }
        .badge-on  { background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 999px; font-size: .72rem; font-weight: 700; }
        .badge-off { background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 999px; font-size: .72rem; font-weight: 700; }

        .alert-success { padding: 11px 15px; border-radius: 11px; background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; margin-bottom: 20px; font-size: .88rem; }
        form { margin: 0; }
    </style>
</head>
<body>
<main>
    {{-- Breadcrumb --}}
    <div class="breadcrumb">
        <a href="{{ route('dashboard') }}">Dashboard</a> /
        <a href="{{ route('admin.cities.index') }}">City Impact</a> /
        {{ $airport->iata }} — {{ $report['city']?->name ?? 'Unknown City' }}
    </div>

    @if (session('status'))
        <div class="alert-success">{{ session('status') }}</div>
    @endif

    {{-- Page header --}}
    <div class="page-header">
        <div>
            <div class="iata-large">{{ $airport->iata }}</div>
            <div class="city-name">{{ $report['city']?->name ?? '—' }}{{ $airport->country_code ? ', ' . $airport->country_code : '' }}</div>
            <div class="impact-badge">{{ strtoupper($report['impact_level']) }} TRAVEL IMPACT</div>
        </div>
        <div class="controls">
            <form method="GET">
                <select name="days" onchange="this.form.submit()">
                    @foreach([1, 3, 7, 14, 30] as $d)
                        <option value="{{ $d }}" {{ $days === $d ? 'selected' : '' }}>{{ $d }} day{{ $d !== 1 ? 's' : '' }}</option>
                    @endforeach
                </select>
            </form>
            <form method="POST" action="{{ route('admin.cities.trigger-fetch', $airport->iata) }}">
                @csrf
                <button type="submit" class="btn btn-accent btn-sm">↻ Fetch Now</button>
            </form>
            <a href="{{ route('admin.cities.index') }}" class="btn btn-ghost btn-sm">← All Cities</a>
        </div>
    </div>

    {{-- Stats strip --}}
    <div class="stats-strip">
        <div class="stat-card">
            <div class="stat-label">News Events</div>
            <div class="stat-value" style="color: var(--impact);">{{ $report['total_events'] }}</div>
            <div class="stat-sub">last {{ $days }} day{{ $days !== 1 ? 's' : '' }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Avg Severity</div>
            <div class="stat-value">{{ $report['avg_severity'] > 0 ? number_format($report['avg_severity'], 1) : '—' }}</div>
            <div class="stat-sub">out of 10</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Peak Severity</div>
            <div class="stat-value">{{ $report['peak_severity'] > 0 ? number_format($report['peak_severity'], 1) : '—' }}</div>
            <div class="stat-sub">highest event</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Watch Targets</div>
            <div class="stat-value">{{ $report['active_watch_targets'] }}</div>
            <div class="stat-sub">of {{ $report['watch_targets']->count() }} active</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Last Event</div>
            <div class="stat-value" style="font-size: .95rem; padding-top: 6px;">
                {{ $report['last_event_at'] ? $report['last_event_at']->diffForHumans() : '—' }}
            </div>
            <div class="stat-sub">
                {{ $report['last_event_at'] ? $report['last_event_at']->format('M j, g:i a') : 'no data yet' }}
            </div>
        </div>
    </div>

    {{-- Day-by-day chart --}}
    @if ($report['total_events'] > 0)
        @php $maxDay = max(array_values($report['by_day']) ?: [1]) ?: 1; @endphp
        <div class="bar-chart">
            <div style="font-size:.82rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">
                Daily Event Volume
            </div>
            <div class="bar-grid">
                @foreach ($report['by_day'] as $date => $count)
                    @php $h = max(3, (int) round(($count / $maxDay) * 72)); @endphp
                    <div class="bar-col">
                        <div class="bar" style="height: {{ $h }}px;"
                             title="{{ $date }}: {{ $count }} event{{ $count !== 1 ? 's' : '' }}"></div>
                        <span class="bar-date">{{ date('M d', strtotime($date)) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Summary: category breakdown + top 3 events --}}
    @if ($report['total_events'] > 0)
    <div class="two-col">
        {{-- Category breakdown --}}
        <div class="card">
            <div class="card-title">Impact by Category</div>
            @php
                $maxCat    = max(array_values($report['by_category'])) ?: 1;
                $catColors = [
                    'weather'    => '#3b82f6',
                    'operations' => '#f59e0b',
                    'airline'    => '#8b5cf6',
                    'general'    => '#22c55e',
                    'monitoring' => '#6b7280',
                ];
            @endphp
            @foreach ($report['by_category'] as $cat => $count)
                <div class="cat-row">
                    <span class="cat-name">{{ ucfirst($cat) }}</span>
                    <div class="cat-bar-bg">
                        <div class="cat-bar-fill"
                             style="width: {{ round(($count / $maxCat) * 100) }}%;
                                    background: {{ $catColors[$cat] ?? '#6b7280' }};"></div>
                    </div>
                    <span class="cat-count">{{ $count }}</span>
                </div>
            @endforeach
        </div>

        {{-- Top 3 by composite score --}}
        <div class="card">
            <div class="card-title">Highest Impact</div>
            @foreach ($report['top_events'] as $event)
                @php
                    $composite = round(($event->relevance_score * 0.6) + ($event->severity_score * 0.4), 1);
                    $sevColor  = $event->severity_score >= 7 ? '#ef4444'
                               : ($event->severity_score >= 5 ? '#f97316' : '#eab308');
                @endphp
                <div class="top-event">
                    <div class="top-event-score"
                         style="background: {{ $sevColor }}18; color: {{ $sevColor }}; border: 1px solid {{ $sevColor }}30;">
                        <div style="font-size:.82rem;font-weight:900;">{{ number_format($composite, 1) }}</div>
                        <div style="font-size:.6rem;color:{{ $sevColor }}80;font-weight:600;">SCORE</div>
                    </div>
                    <div>
                        <div class="top-event-title">
                            @if ($event->url)
                                <a href="{{ $event->url }}" target="_blank" rel="noopener">
                                    {{ Str::limit($event->title, 80) }}
                                </a>
                            @else
                                {{ Str::limit($event->title, 80) }}
                            @endif
                        </div>
                        <div class="top-event-meta">
                            <span>Sev {{ number_format($event->severity_score, 1) }}</span>
                            <span>·</span>
                            <span>{{ ucfirst($event->category) }}</span>
                            @if ($event->published_at)
                                <span>·</span>
                                <span>{{ $event->published_at->diffForHumans() }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- FULL NEWS FEED                                                       --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div class="card" style="margin-bottom: 24px;">
        <div class="news-feed-header">
            <div style="display:flex;align-items:center;gap:10px;">
                <h2>News Feed</h2>
                @if ($report['total_events'] > 0)
                    <span class="news-count-badge">{{ $report['total_events'] }} article{{ $report['total_events'] !== 1 ? 's' : '' }}</span>
                @endif
            </div>
            <div style="font-size:.78rem;color:var(--muted);">
                Ranked by relevance × severity · last {{ $days }} day{{ $days !== 1 ? 's' : '' }}
            </div>
        </div>

        @if ($report['news_events']->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <h3>No news events in this period</h3>
                <p>
                    No news was ingested for {{ $report['city']?->name ?? $airport->iata }} in the last {{ $days }} day{{ $days !== 1 ? 's' : '' }}.<br>
                    Try a wider window or trigger a fresh fetch.
                </p>
                <form method="POST" action="{{ route('admin.cities.trigger-fetch', $airport->iata) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-primary">↻ Fetch News Now</button>
                </form>
            </div>
        @else
            @php
                $cityName = $report['city']?->name ?? $airport->iata;

                // Category-based relevance reasons
                $relevanceReasons = [
                    'weather'    => [
                        'icon' => '🌧',
                        'text' => 'Weather conditions at or near %s that may disrupt inbound and outbound flights.',
                    ],
                    'operations' => [
                        'icon' => '🔧',
                        'text' => 'Airport or ground operations issue at %s affecting passenger flow and connections.',
                    ],
                    'airline'    => [
                        'icon' => '✈️',
                        'text' => 'Airline service change or disruption on routes serving %s.',
                    ],
                    'general'    => [
                        'icon' => '📌',
                        'text' => 'Local or regional news event in %s with potential travel impact.',
                    ],
                    'monitoring' => [
                        'icon' => '📡',
                        'text' => 'Actively monitored situation in %s flagged for travel advisories.',
                    ],
                ];
            @endphp

            @foreach ($report['news_events'] as $event)
                @php
                    $composite = ($event->relevance_score * 0.6) + ($event->severity_score * 0.4);
                    if ($composite >= 7.5) {
                        $scoreBg = '#fef2f2'; $scoreColor = '#ef4444';
                    } elseif ($composite >= 5.5) {
                        $scoreBg = '#fff7ed'; $scoreColor = '#f97316';
                    } elseif ($composite >= 4.0) {
                        $scoreBg = '#fefce8'; $scoreColor = '#ca8a04';
                    } else {
                        $scoreBg = '#f0fdf4'; $scoreColor = '#16a34a';
                    }

                    $catChipClass = match ($event->category) {
                        'weather'    => 'weather',
                        'operations' => 'operations',
                        'airline'    => 'airline',
                        default      => 'general',
                    };

                    $reason = $relevanceReasons[$event->category] ?? $relevanceReasons['general'];

                    // Severity modifier appended to the reason text
                    $severityNote = '';
                    if ($event->severity_score >= 7.5) {
                        $severityNote = ' High-severity event — monitor closely.';
                    } elseif ($event->severity_score >= 5.5) {
                        $severityNote = ' Moderate severity — may affect schedules.';
                    }

                    // Extract domain from URL for source display
                    $sourceDomain = '';
                    if ($event->url) {
                        $parsed = parse_url($event->url);
                        $sourceDomain = ltrim($parsed['host'] ?? '', 'www.');
                    }
                @endphp
                <div class="news-item">
                    {{-- Score ring --}}
                    <div class="news-score-col">
                        <div class="score-ring"
                             style="background: {{ $scoreBg }}; color: {{ $scoreColor }}; border: 1px solid {{ $scoreColor }}25;">
                            <div style="font-size:.82rem;font-weight:900;line-height:1;">{{ number_format($composite, 1) }}</div>
                            <div style="font-size:.55rem;font-weight:600;opacity:.65;letter-spacing:.02em;">SCORE</div>
                        </div>
                    </div>

                    {{-- Main content --}}
                    <div class="news-body">
                        <div class="news-title">
                            @if ($event->url)
                                <a href="{{ $event->url }}" target="_blank" rel="noopener">
                                    {{ $event->title }}
                                </a>
                            @else
                                {{ $event->title }}
                            @endif
                        </div>
                        @if ($event->summary && strip_tags($event->summary) !== '')
                            <p class="news-summary">{{ strip_tags($event->summary) }}</p>
                        @endif
                        <div class="news-meta">
                            <span class="meta-chip {{ $catChipClass }}">{{ ucfirst($event->category) }}</span>
                            <span class="meta-chip">Sev {{ number_format($event->severity_score, 1) }}</span>
                            <span class="meta-chip">Rel {{ number_format($event->relevance_score, 1) }}</span>
                            @if ($event->published_at)
                                <span>{{ $event->published_at->diffForHumans() }}</span>
                                <span style="color:#cbd5e1;">·</span>
                                <span>{{ $event->published_at->format('M j, Y') }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Context card --}}
                    <div class="news-context-card">
                        <div>
                            <div class="ctx-section-label">Why relevant</div>
                            <div class="ctx-relevance-text">
                                {{ $reason['icon'] }}
                                {{ sprintf($reason['text'], $cityName) }}{{ $severityNote }}
                            </div>
                        </div>

                        <hr class="ctx-divider">

                        <div>
                            <div class="ctx-section-label">Source</div>
                            @if ($event->sourceProvider)
                                <div class="ctx-source-name">{{ $event->sourceProvider->name }}</div>
                            @endif
                            @if ($sourceDomain)
                                <div class="ctx-source-domain">
                                    @if ($event->url)
                                        <a href="{{ $event->url }}" target="_blank" rel="noopener">
                                            {{ $sourceDomain }}
                                        </a>
                                    @else
                                        {{ $sourceDomain }}
                                    @endif
                                </div>
                            @elseif (!$event->sourceProvider)
                                <div class="ctx-source-domain">Unknown source</div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- Watch targets management --}}
    <div class="card">
        <div class="card-title">
            Watch Targets → {{ $airport->iata }}
            <span style="font-weight: 400; color: var(--muted); font-size: .82rem; margin-left: 4px;">
                ({{ $report['active_watch_targets'] }} of {{ $report['watch_targets']->count() }} active)
            </span>
        </div>

        @if ($report['watch_targets']->isEmpty())
            <p style="color: var(--muted); font-size: .88rem; margin: 0;">No watch targets defined for this destination.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Origin</th>
                        <th>Priority</th>
                        <th>Window</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['watch_targets'] as $wt)
                        <tr>
                            <td>
                                <strong>{{ $wt->originAirport?->iata ?? '—' }}</strong>
                                <span style="color: var(--muted); font-size: .82rem; margin-left: 6px;">
                                    {{ $wt->originCity?->name ?? '' }}
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.cities.watch-targets.update', $wt) }}"
                                      style="display: flex; gap: 6px; align-items: center;">
                                    @csrf
                                    @method('PATCH')
                                    <select name="monitoring_priority"
                                            style="padding: 4px 8px; font-size: .8rem; border-radius: 8px; border: 1px solid var(--line);">
                                        @for($p = 1; $p <= 10; $p++)
                                            <option value="{{ $p }}" {{ $wt->monitoring_priority === $p ? 'selected' : '' }}>{{ $p }}</option>
                                        @endfor
                                    </select>
                            </td>
                            <td>
                                    <select name="date_window_days"
                                            style="padding: 4px 8px; font-size: .8rem; border-radius: 8px; border: 1px solid var(--line);">
                                        @foreach([3, 5, 7, 10, 14, 30] as $dw)
                                            <option value="{{ $dw }}" {{ $wt->date_window_days === $dw ? 'selected' : '' }}>{{ $dw }}d</option>
                                        @endforeach
                                    </select>
                            </td>
                            <td>
                                    <span class="{{ $wt->enabled ? 'badge-on' : 'badge-off' }}">
                                        {{ $wt->enabled ? 'Active' : 'Paused' }}
                                    </span>
                            </td>
                            <td style="display: flex; gap: 6px; align-items: center;">
                                    <button type="submit" class="btn btn-ghost btn-sm">Save</button>
                                </form>
                                <form method="POST" action="{{ route('admin.cities.watch-targets.toggle', $wt) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="btn btn-sm {{ $wt->enabled ? 'btn-toggle-on' : 'btn-toggle-off' }}">
                                        {{ $wt->enabled ? 'Pause' : 'Resume' }}
                                    </button>
                                </form>
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
