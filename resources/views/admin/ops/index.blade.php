<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Operations Panel</title>
    <style>
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, sans-serif;
            background: #f5f7fb;
            color: #172033;
        }
        main {
            max-width: 1280px;
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
        section + section {
            margin-top: 24px;
        }
        h1, h2, h3 {
            margin-top: 0;
        }
        p {
            color: #5b667a;
        }
        a, button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            padding: 12px 16px;
            background: #1d4ed8;
            color: #fff;
            font-weight: 700;
            text-decoration: none;
            border: 0;
            cursor: pointer;
        }
        .secondary {
            background: #e2e8f0;
            color: #172033;
        }
        .actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .grid {
            display: grid;
            gap: 24px;
        }
        .dual {
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        }
        .triple {
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        }
        .panel {
            border: 1px solid #dbe2ef;
            border-radius: 14px;
            padding: 18px;
            background: #fbfdff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 14px;
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
            background: #dbeafe;
            color: #1d4ed8;
        }
        .pill.inactive {
            background: #e2e8f0;
            color: #475569;
        }
        .pill.failed {
            background: #fee2e2;
            color: #991b1b;
        }
        .pill.completed {
            background: #dcfce7;
            color: #166534;
        }
        .pill.running {
            background: #dbeafe;
            color: #1d4ed8;
        }
        .scroll {
            overflow-x: auto;
        }
        .indicator-score {
            font-weight: 700;
            color: #0f172a;
        }
        .empty {
            color: #64748b;
        }
        .indicator-legend {
            margin-bottom: 18px;
            padding: 16px 18px;
            border: 1px solid #dbe2ef;
            border-radius: 14px;
            background: #f8fbff;
        }
        .indicator-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }
        .indicator-breakdown {
            margin-top: 8px;
            line-height: 1.5;
        }
        .indicator-breakdown.is-hidden {
            display: none;
        }
        .status, .error, .result {
            margin-bottom: 20px;
            padding: 14px;
            border-radius: 12px;
        }
        .status {
            background: #dcfce7;
            color: #166534;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
        }
        .result {
            background: #172033;
            color: #f8fafc;
        }
        .result p,
        .result strong,
        .result span,
        .result li {
            color: inherit;
        }
        .score-summary {
            display: grid;
            gap: 16px;
        }
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }
        .detail-card {
            padding: 14px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.08);
        }
        .detail-card strong {
            display: block;
            margin-bottom: 6px;
            font-size: 18px;
        }
        .detail-card small {
            color: #cbd5e1;
            display: block;
            line-height: 1.45;
        }
        .result-list,
        .driver-list {
            margin: 0;
            padding-left: 18px;
            display: grid;
            gap: 8px;
        }
        .driver-list strong {
            font-size: 15px;
        }
        .risk-meta {
            margin: 0;
            padding: 12px 14px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.08);
        }
        .score-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
        }
        .score-note {
            margin: 0;
            padding: 12px 14px;
            border-radius: 12px;
            background: rgba(147, 197, 253, 0.12);
            color: #dbeafe;
        }
        .score-metric {
            padding: 12px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.08);
        }
        .score-metric strong {
            display: block;
            font-size: 24px;
        }
        .score-chart {
            padding: 12px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.06);
        }
        .score-chart-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
        }
        .score-chart-header strong {
            display: block;
        }
        .score-chart-header span {
            color: #cbd5e1;
            font-size: 13px;
        }
        .score-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .score-legend li {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #cbd5e1;
        }
        .score-legend-swatch {
            width: 10px;
            height: 10px;
            border-radius: 999px;
        }
        .score-chart svg {
            display: block;
            width: 100%;
            height: 320px;
        }
        .score-points {
            display: grid;
            gap: 8px;
            margin-top: 12px;
            max-height: 320px;
            overflow-y: auto;
        }
        .score-points li {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.06);
        }
        label {
            display: block;
            margin-bottom: 14px;
            font-size: 14px;
            font-weight: 600;
            color: #172033;
        }
        select, input {
            width: 100%;
            box-sizing: border-box;
            margin-top: 8px;
            padding: 12px 14px;
            border: 1px solid #c6d0e1;
            border-radius: 10px;
            font: inherit;
            background: #fff;
            color: #172033;
        }
        pre {
            margin: 0;
            white-space: pre-wrap;
            word-break: break-word;
        }
    </style>
</head>
<body>
<main>
    @php
        $manualToolResult = session('manual_tool_result');
        $riskEvaluationResult = $manualToolResult && ($manualToolResult['tool'] ?? null) === 'recompute risk'
            ? $manualToolResult['details']
            : null;
        $queryCityScoreResult = $manualToolResult && ($manualToolResult['tool'] ?? null) === 'query city score'
            ? $manualToolResult['details']
            : null;
    @endphp

    <header>
        <div>
            <h1>Operations Panel</h1>
            <p>Inspect provider registry, watch targets, ingestion activity, latest indicators, and queue failures.</p>
        </div>

        <div class="actions">
            <a href="{{ route('dashboard') }}" class="secondary">Back to Dashboard</a>
            <a href="{{ route('admin.providers.index') }}">Manage Providers</a>
            <a href="{{ route('admin.data-inspection.index') }}" class="secondary">Inspect Data</a>
        </div>
    </header>

    <section>
        <h2>Manual Ops Tools</h2>
        <p>Trigger targeted fetches, rebuild indicators, and recompute route risk for debugging and demos.</p>

        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="error">{{ session('error') }}</div>
        @endif

        @if ($manualToolResult)
            <div class="result">
                @if ($riskEvaluationResult)
                    @php
                        $resolvedOrigin = $riskEvaluationResult['resolved']['origin'] ?? [];
                        $resolvedDestination = $riskEvaluationResult['resolved']['destination'] ?? [];
                        $originLabel = $resolvedOrigin['airport']['iata']
                            ?? $resolvedOrigin['city']['name']
                            ?? 'Unknown origin';
                        $destinationLabel = $resolvedDestination['airport']['iata']
                            ?? $resolvedDestination['city']['name']
                            ?? 'Unknown destination';
                        $routeLabel = $originLabel.' → '.$destinationLabel;
                        $drivers = collect($riskEvaluationResult['drivers'] ?? [])->take(4);
                        $componentAges = collect($riskEvaluationResult['freshness']['component_ages'] ?? []);
                    @endphp
                    <div class="score-summary">
                        <div>
                            <strong>Risk Evaluation</strong>
                            <p>
                                {{ $routeLabel }}
                                for {{ $riskEvaluationResult['resolved']['travel_date'] ?? 'n/a' }}.
                                This reflects {{ str_replace('_', ' ', $riskEvaluationResult['assessment_type'] ?? 'short term travel disruption risk') }}.
                            </p>
                            <p class="score-note">{{ $riskEvaluationResult['product_framing'] ?? 'Estimate of short-term travel disruption risk and probable no-show uplift.' }}</p>
                        </div>

                        <div class="detail-grid">
                            <div class="detail-card">
                                <span>Score</span>
                                <strong>{{ number_format((float) ($riskEvaluationResult['score'] ?? 0), 2) }}</strong>
                                <small>Risk level: {{ strtoupper((string) ($riskEvaluationResult['risk_level'] ?? 'unknown')) }}</small>
                            </div>
                            <div class="detail-card">
                                <span>Confidence</span>
                                <strong>{{ strtoupper((string) ($riskEvaluationResult['confidence']['level'] ?? 'unknown')) }}</strong>
                                <small>{{ number_format(((float) ($riskEvaluationResult['confidence']['score'] ?? 0)) * 100, 0) }}% weighted coverage</small>
                            </div>
                            <div class="detail-card">
                                <span>Data Freshness</span>
                                <strong>{{ strtoupper((string) ($riskEvaluationResult['freshness']['level'] ?? 'unknown')) }}</strong>
                                <small>
                                    Stalest signal:
                                    {{ $riskEvaluationResult['freshness']['minutes_since_stalest_signal'] ?? 'n/a' }}
                                    min old
                                </small>
                            </div>
                            <div class="detail-card">
                                <span>Probable No-show Uplift</span>
                                <strong>{{ number_format((float) ($riskEvaluationResult['probable_no_show_uplift']['estimate_percent'] ?? 0), 1) }}%</strong>
                                <small>
                                    Range {{ number_format((float) ($riskEvaluationResult['probable_no_show_uplift']['range_percent']['low'] ?? 0), 1) }}%
                                    to {{ number_format((float) ($riskEvaluationResult['probable_no_show_uplift']['range_percent']['high'] ?? 0), 1) }}%
                                </small>
                            </div>
                        </div>

                        <p class="risk-meta">
                            <strong>Recommended Action</strong><br>
                            {{ $riskEvaluationResult['recommended_action']['summary'] ?? 'Continue monitoring this itinerary.' }}
                            @if (!empty($riskEvaluationResult['recommended_action']['primary_driver']))
                                Primary driver: {{ str_replace('_', ' ', $riskEvaluationResult['recommended_action']['primary_driver']) }}.
                            @endif
                        </p>

                        <div class="grid dual">
                            <div class="panel">
                                <h3>Top Drivers</h3>
                                <ol class="driver-list">
                                    @forelse ($drivers as $driver)
                                        <li>
                                            <strong>{{ ucfirst(str_replace('_', ' ', $driver['factor'] ?? 'unknown')) }}</strong>
                                            score {{ number_format((float) ($driver['component_score'] ?? 0), 2) }},
                                            weighted contribution {{ number_format((float) ($driver['weighted_contribution'] ?? 0), 2) }},
                                            source {{ $driver['source'] ?? 'unknown' }}
                                        </li>
                                    @empty
                                        <li>No driver details available.</li>
                                    @endforelse
                                </ol>
                            </div>

                            <div class="panel">
                                <h3>Freshness By Factor</h3>
                                <ul class="result-list">
                                    @forelse ($componentAges as $factor => $age)
                                        <li>
                                            {{ ucfirst(str_replace('_', ' ', (string) $factor)) }}:
                                            @if (!empty($age['as_of']))
                                                {{ $age['minutes_old'] }} min old
                                                ({{ \Illuminate\Support\Carbon::parse($age['as_of'])->toDayDateTimeString() }})
                                            @else
                                                no recent signal
                                            @endif
                                        </li>
                                    @empty
                                        <li>No freshness detail available.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>

                        <div class="panel">
                            <h3>Evaluation Summary</h3>
                            <ul class="result-list">
                                @foreach (($riskEvaluationResult['explanations'] ?? []) as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @elseif ($queryCityScoreResult)
                    @php
                        $scoreScope = $queryCityScoreResult['score_scope'] ?? 'city';
                    @endphp
                    <div class="score-summary">
                        @if (($queryCityScoreResult['mode'] ?? null) === 'single')
                            <div>
                                <strong>City Score Summary</strong>
                                <p>
                                    {{ $queryCityScoreResult['city'] }}{{ !empty($queryCityScoreResult['country']) ? ', '.$queryCityScoreResult['country'] : '' }}
                                    for {{ $queryCityScoreResult['requested_date'] }}.
                                    Snapshot used: {{ \Illuminate\Support\Carbon::parse($queryCityScoreResult['snapshot_as_of'])->toDayDateTimeString() }}.
                                </p>
                                @if ($scoreScope === 'route')
                                    <p class="score-note">
                                        This city score is route-backed. It uses the monitored route
                                        {{ $queryCityScoreResult['route_label'] ?? ($queryCityScoreResult['city'].' → '.($queryCityScoreResult['base_airport_iata'] ?? 'n/a')) }}
                                        and follows the same route indicator logic: flight + news.
                                    </p>
                                @else
                                    <p class="score-note">
                                        This score combines city weather, city news, and flight disruption on active routes into the base airport
                                        {{ $queryCityScoreResult['base_airport_iata'] ?? 'n/a' }}.
                                    </p>
                                @endif
                                <p class="score-note">
                                    Score scale: 0 to 3 means low disruption risk, above 3 to 6 means moderate risk, above 6 to 8 means high risk,
                                    and above 8 means severe risk. Higher scores mean worse expected travel conditions.
                                </p>
                            </div>

                            <div class="score-metrics">
                                <div class="score-metric">
                                    <span>Combined</span>
                                    <strong>{{ number_format((float) $queryCityScoreResult['combined_score'], 2) }}</strong>
                                </div>
                                @if ($scoreScope !== 'route')
                                    <div class="score-metric">
                                        <span>Weather</span>
                                        <strong>{{ number_format((float) $queryCityScoreResult['weather_score'], 2) }}</strong>
                                        <span>{{ $queryCityScoreResult['weather_events'] }} events</span>
                                    </div>
                                @endif
                                <div class="score-metric">
                                    <span>News</span>
                                    <strong>{{ number_format((float) $queryCityScoreResult['news_score'], 2) }}</strong>
                                    <span>{{ $queryCityScoreResult['news_events'] }} events</span>
                                </div>
                                <div class="score-metric">
                                    <span>Flights to {{ $queryCityScoreResult['base_airport_iata'] ?? 'Base' }}</span>
                                    <strong>{{ number_format((float) ($queryCityScoreResult['flight_score'] ?? 0), 2) }}</strong>
                                    <span>{{ $queryCityScoreResult['flight_events'] ?? 0 }} events</span>
                                </div>
                                <div class="score-metric">
                                    <span>Window</span>
                                    <strong>{{ $queryCityScoreResult['window_hours'] }}h</strong>
                                </div>
                            </div>
                        @else
                            @php
                                $points = collect($queryCityScoreResult['points'] ?? []);
                                $hasWeather = $points->contains(fn (array $point): bool => array_key_exists('weather_score', $point) && $point['weather_score'] !== null);
                                $chartLeft = 56;
                                $chartRight = 760;
                                $chartTop = 28;
                                $chartBottom = 220;
                                $chartHeight = $chartBottom - $chartTop;
                                $chartWidth = $chartRight - $chartLeft;
                                $toY = static fn ($score): float => round(
                                    $chartBottom - ((max(0, min(10, (float) ($score ?? 0))) / 10) * $chartHeight),
                                    2
                                );
                                $chartPoints = $points->values()->map(function (array $point, int $index) use ($chartBottom, $chartLeft, $chartWidth, $points, $toY) {
                                    $x = $points->count() > 1 ? $chartLeft + ($index * ($chartWidth / max(1, $points->count() - 1))) : $chartLeft + ($chartWidth / 2);
                                    $combinedY = $toY((float) $point['combined_score']);
                                    $weatherY = array_key_exists('weather_score', $point) && $point['weather_score'] !== null
                                        ? $toY((float) $point['weather_score'])
                                        : null;
                                    $newsY = $toY((float) $point['news_score']);
                                    $flightY = $toY((float) ($point['flight_score'] ?? 0));

                                    return [
                                        'x' => round($x, 2),
                                        'combined_y' => $combinedY,
                                        'weather_y' => $weatherY,
                                        'news_y' => $newsY,
                                        'flight_y' => $flightY,
                                    ] + $point;
                                });
                                $combinedPolyline = $chartPoints->map(
                                    fn (array $point): string => $point['x'].','.$point['combined_y']
                                )->implode(' ');
                                $weatherPolyline = $hasWeather
                                    ? $chartPoints
                                        ->filter(fn (array $point): bool => $point['weather_y'] !== null)
                                        ->map(fn (array $point): string => $point['x'].','.$point['weather_y'])
                                        ->implode(' ')
                                    : '';
                                $newsPolyline = $chartPoints->map(
                                    fn (array $point): string => $point['x'].','.$point['news_y']
                                )->implode(' ');
                                $flightPolyline = $chartPoints->map(
                                    fn (array $point): string => $point['x'].','.$point['flight_y']
                                )->implode(' ');
                                $yTicks = collect([0, 3, 6, 8, 10])->map(fn (int $value): array => [
                                    'value' => $value,
                                    'y' => $toY((float) $value),
                                ]);
                                $xTicks = $chartPoints
                                    ->filter(fn (array $point, int $index): bool => $index === 0
                                        || $index === $chartPoints->count() - 1
                                        || $index % 7 === 0)
                                    ->values();
                            @endphp
                            <div>
                                <strong>City Score Trend</strong>
                                <p>
                                    {{ $queryCityScoreResult['city'] }}{{ !empty($queryCityScoreResult['country']) ? ', '.$queryCityScoreResult['country'] : '' }}
                                    projected for the next {{ $queryCityScoreResult['projection_days'] ?? 30 }} days,
                                    from {{ $queryCityScoreResult['from_date'] }} to {{ $queryCityScoreResult['to_date'] }}.
                                </p>
                                @if (!empty($queryCityScoreResult['baseline_snapshot_as_of']))
                                    <p>
                                        Baseline city/news signal uses the latest snapshot from
                                        {{ \Illuminate\Support\Carbon::parse($queryCityScoreResult['baseline_snapshot_as_of'])->toDayDateTimeString() }}.
                                    </p>
                                @endif
                                <p class="score-note">
                                    @if ($scoreScope === 'route')
                                        This graph is route-backed and follows
                                        {{ $queryCityScoreResult['route_label'] ?? ($queryCityScoreResult['city'].' → '.($queryCityScoreResult['base_airport_iata'] ?? 'the base airport')) }}:
                                        flight + news.
                                    @else
                                        This graph combines city weather, city news, and flight disruption for active routes from this city into
                                        {{ $queryCityScoreResult['base_airport_iata'] ?? 'the base airport' }}.
                                    @endif
                                </p>
                                <p class="score-note">
                                    Score scale: 0 to 3 means low disruption risk, above 3 to 6 means moderate risk, above 6 to 8 means high risk,
                                    and above 8 means severe risk. Higher scores mean worse expected travel conditions.
                                </p>
                            </div>

                            <div class="score-chart">
                                <div class="score-chart-header">
                                    <div>
                                        <strong>Projected Daily Scores (0-10)</strong>
                                        <span>Combined score and component trends for the next {{ $queryCityScoreResult['projection_days'] ?? 30 }} days.</span>
                                    </div>
                                    <ul class="score-legend" aria-label="Chart legend">
                                        <li><span class="score-legend-swatch" style="background:#93c5fd;"></span>Combined</li>
                                        @if ($hasWeather)
                                            <li><span class="score-legend-swatch" style="background:#34d399;"></span>Weather</li>
                                        @endif
                                        <li><span class="score-legend-swatch" style="background:#fbbf24;"></span>News</li>
                                        <li><span class="score-legend-swatch" style="background:#f87171;"></span>Flight</li>
                                    </ul>
                                </div>
                                <svg viewBox="0 0 860 260" role="img" aria-label="City score trend chart with combined, weather, news, and flight lines">
                                    <text x="56" y="16" font-size="12" fill="#cbd5e1">Risk score</text>
                                    <text x="408" y="250" text-anchor="middle" font-size="12" fill="#cbd5e1">Projected travel date</text>
                                    <rect x="{{ $chartLeft }}" y="{{ $toY(10) }}" width="{{ $chartWidth }}" height="{{ $toY(8) - $toY(10) }}" fill="rgba(239,68,68,0.12)" />
                                    <rect x="{{ $chartLeft }}" y="{{ $toY(8) }}" width="{{ $chartWidth }}" height="{{ $toY(6) - $toY(8) }}" fill="rgba(251,191,36,0.10)" />
                                    <rect x="{{ $chartLeft }}" y="{{ $toY(6) }}" width="{{ $chartWidth }}" height="{{ $toY(3) - $toY(6) }}" fill="rgba(96,165,250,0.10)" />
                                    <rect x="{{ $chartLeft }}" y="{{ $toY(3) }}" width="{{ $chartWidth }}" height="{{ $toY(0) - $toY(3) }}" fill="rgba(52,211,153,0.10)" />
                                    @foreach ($yTicks as $tick)
                                        <line x1="{{ $chartLeft }}" y1="{{ $tick['y'] }}" x2="{{ $chartRight }}" y2="{{ $tick['y'] }}" stroke="rgba(255,255,255,0.14)" stroke-width="1" />
                                        <text x="44" y="{{ $tick['y'] + 4 }}" text-anchor="end" font-size="11" fill="#cbd5e1">{{ $tick['value'] }}</text>
                                    @endforeach
                                    <line x1="{{ $chartLeft }}" y1="{{ $chartTop }}" x2="{{ $chartLeft }}" y2="{{ $chartBottom }}" stroke="rgba(255,255,255,0.25)" stroke-width="1" />
                                    <line x1="{{ $chartLeft }}" y1="{{ $chartBottom }}" x2="{{ $chartRight }}" y2="{{ $chartBottom }}" stroke="rgba(255,255,255,0.25)" stroke-width="1" />
                                    @foreach ($xTicks as $tick)
                                        <line x1="{{ $tick['x'] }}" y1="{{ $chartBottom }}" x2="{{ $tick['x'] }}" y2="{{ $chartBottom + 6 }}" stroke="rgba(255,255,255,0.25)" stroke-width="1" />
                                        <text x="{{ $tick['x'] }}" y="{{ $chartBottom + 20 }}" text-anchor="middle" font-size="10" fill="#cbd5e1">{{ $tick['label'] }}</text>
                                    @endforeach
                                    @if ($hasWeather)
                                        <polyline fill="none" stroke="#34d399" stroke-width="2" opacity="0.85" points="{{ $weatherPolyline }}" />
                                    @endif
                                    <polyline fill="none" stroke="#fbbf24" stroke-width="2" opacity="0.85" points="{{ $newsPolyline }}" />
                                    <polyline fill="none" stroke="#f87171" stroke-width="2" opacity="0.9" points="{{ $flightPolyline }}" />
                                    <polyline fill="none" stroke="#93c5fd" stroke-width="3" points="{{ $combinedPolyline }}" />
                                    @foreach ($chartPoints as $point)
                                        <circle cx="{{ $point['x'] }}" cy="{{ $point['combined_y'] }}" r="4" fill="#ffffff" />
                                    @endforeach
                                    <text x="{{ $chartRight + 8 }}" y="{{ $toY(9) + 4 }}" font-size="10" fill="#fca5a5">Severe</text>
                                    <text x="{{ $chartRight + 8 }}" y="{{ $toY(7) + 4 }}" font-size="10" fill="#fde68a">High</text>
                                    <text x="{{ $chartRight + 8 }}" y="{{ $toY(4.5) + 4 }}" font-size="10" fill="#bfdbfe">Moderate</text>
                                    <text x="{{ $chartRight + 8 }}" y="{{ $toY(1.5) + 4 }}" font-size="10" fill="#86efac">Low</text>
                                </svg>
                                <ul class="score-points">
                                    @foreach ($points as $point)
                                        <li>
                                            <span>{{ $point['label'] }}</span>
                                            <span>
                                                Combined {{ number_format((float) $point['combined_score'], 2) }} ·
                                                @if ($hasWeather)
                                                    Weather {{ number_format((float) $point['weather_score'], 2) }} ·
                                                @endif
                                                News {{ number_format((float) $point['news_score'], 2) }} ·
                                                Flight {{ number_format((float) ($point['flight_score'] ?? 0), 2) }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @else
                    <strong>{{ ucfirst($manualToolResult['tool']) }}</strong>
                    <pre>{{ json_encode($manualToolResult['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                @endif
            </div>
        @endif

        <div class="grid dual">
            <div class="panel">
                <h3>Query City Score</h3>
                <form method="POST" action="{{ route('admin.ops.triggers.city-score') }}">
                    @csrf
                    <label>
                        City
                        <select name="city_id" required>
                            <option value="">Select city</option>
                            @foreach ($queryCities as $city)
                                <option value="{{ $city->id }}">{{ $city->name }}{{ $city->country ? ', '.$city->country->name : '' }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        Date
                        <input type="date" name="query_date" value="">
                    </label>

                    <button type="submit">Get City Score</button>
                    <p class="meta">City score uses weather, news, and flight disruption into the configured base airport. Scores run from 0 to 10, where higher means more disruption risk. Leave the date blank to see a projected daily graph for the next 30 days.</p>
                </form>
            </div>

            <div class="panel">
                <h3>Re-fetch Weather For City</h3>
                <form method="POST" action="{{ route('admin.ops.triggers.weather') }}">
                    @csrf
                    <label>
                        City
                        <select name="city_id" required>
                            <option value="">Select city</option>
                            @foreach ($weatherCities as $city)
                                <option value="{{ $city->id }}">{{ $city->name }}{{ $city->country ? ', '.$city->country->name : '' }}</option>
                            @endforeach
                        </select>
                    </label>

                    <button type="submit">Run Weather Fetch</button>
                </form>
            </div>

            <div class="panel">
                <h3>Re-fetch News For City</h3>
                <form method="POST" action="{{ route('admin.ops.triggers.news') }}">
                    @csrf
                    <label>
                        City
                        <select name="city_id" required>
                            <option value="">Select city</option>
                            @foreach ($newsCities as $city)
                                <option value="{{ $city->id }}">{{ $city->name }}{{ $city->country ? ', '.$city->country->name : '' }}</option>
                            @endforeach
                        </select>
                    </label>

                    <button type="submit">Run News Fetch</button>
                </form>
            </div>

            <div class="panel">
                <h3>Re-fetch Flights For Route</h3>
                <form method="POST" action="{{ route('admin.ops.triggers.flights') }}">
                    @csrf
                    <label>
                        Route
                        <select name="route_id" required>
                            <option value="">Select route</option>
                            @foreach ($flightRoutes as $route)
                                <option value="{{ $route->id }}">
                                    {{ $route->originAirport?->iata ?? 'n/a' }} → {{ $route->destinationAirport?->iata ?? 'n/a' }}
                                    ({{ $route->originAirport?->city?->name ?? 'n/a' }} to {{ $route->destinationAirport?->city?->name ?? 'n/a' }})
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <button type="submit">Run Flight Fetch</button>
                </form>
            </div>

            <div class="panel">
                <h3>Rebuild Indicators</h3>
                <form method="POST" action="{{ route('admin.ops.triggers.indicators') }}">
                    @csrf
                    <p class="meta">Recompute airport, city, and route snapshots using the latest normalized events.</p>
                    <button type="submit">Rebuild Now</button>
                </form>
            </div>

            <div class="panel">
                <h3>Recompute Risk</h3>
                <form method="POST" action="{{ route('admin.ops.triggers.risk') }}">
                    @csrf
                    <label>
                        Route
                        <select name="route_id" required>
                            <option value="">Select route</option>
                            @foreach ($riskRoutes as $route)
                                <option value="{{ $route->id }}">
                                    {{ $route->originAirport?->iata ?? 'n/a' }} → {{ $route->destinationAirport?->iata ?? 'n/a' }}
                                    ({{ $route->originAirport?->city?->name ?? 'n/a' }} to {{ $route->destinationAirport?->city?->name ?? 'n/a' }})
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        Travel date
                        <input type="date" name="travel_date" value="{{ now()->addDays(7)->toDateString() }}" required>
                    </label>

                    <button type="submit">Recompute Risk</button>
                </form>
            </div>
        </div>
    </section>

    <section>
        <h2>Providers</h2>
        <div class="scroll">
            <table>
                <thead>
                <tr>
                    <th>Provider</th>
                    <th>Service</th>
                    <th>Registry</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($providers as $provider)
                    <tr>
                        <td>
                            <strong>{{ $provider->name }}</strong>
                            <div class="meta">Slug: {{ $provider->slug }} · Driver: {{ $provider->driver }}</div>
                        </td>
                        <td>{{ strtoupper($provider->service) }}</td>
                        <td class="meta">
                            Credentials: {{ $provider->credentials_count }}<br>
                            Configs: {{ $provider->configs_count }}<br>
                            Runs: {{ $provider->ingestion_runs_count }}
                        </td>
                        <td>
                            <span class="pill {{ $provider->active ? '' : 'inactive' }}">
                                {{ $provider->active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty">No providers registered yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section>
        <h2>Watch Targets</h2>
        <div class="scroll">
            <table>
                <thead>
                <tr>
                    <th>Origin</th>
                    <th>Destination</th>
                    <th>Monitoring</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($watchTargets as $target)
                    <tr>
                        <td>
                            <strong>{{ $target->originAirport?->iata ?? $target->originCity?->name ?? 'n/a' }}</strong>
                            <div class="meta">
                                City: {{ $target->originCity?->name ?? $target->originAirport?->city?->name ?? 'n/a' }}
                            </div>
                        </td>
                        <td>
                            <strong>{{ $target->destinationAirport?->iata ?? $target->destinationCity?->name ?? 'Any destination' }}</strong>
                            <div class="meta">
                                City: {{ $target->destinationCity?->name ?? $target->destinationAirport?->city?->name ?? 'Flexible' }}
                            </div>
                        </td>
                        <td class="meta">
                            Priority: {{ $target->monitoring_priority }}<br>
                            Date window: {{ $target->date_window_days }} day(s)
                        </td>
                        <td>
                            <span class="pill {{ $target->enabled ? '' : 'inactive' }}">
                                {{ $target->enabled ? 'Enabled' : 'Disabled' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty">No watch targets configured yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section>
        <h2>Ingestion Runs</h2>
        <div class="scroll">
            <table>
                <thead>
                <tr>
                    <th>Provider</th>
                    <th>Source</th>
                    <th>Status</th>
                    <th>Output</th>
                    <th>Timing</th>
                    <th>Error</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($ingestionRuns as $run)
                    @php
                        $payloadCount = $run->response_meta['payload_count'] ?? $run->raw_payloads_count ?? 0;
                        $recordsCreated = $run->response_meta['normalized_events'] ?? null;
                    @endphp
                    <tr>
                        <td>{{ $run->provider?->name ?? 'Unknown provider' }}</td>
                        <td>{{ strtoupper($run->source_type) }}</td>
                        <td>
                            <span class="pill {{ $run->status }}">
                                {{ $run->status }}
                            </span>
                        </td>
                        <td class="meta">
                            Payloads: {{ $payloadCount }}<br>
                            Records created: {{ $recordsCreated ?? 'n/a' }}
                        </td>
                        <td class="meta">
                            Started: {{ $run->started_at?->toDayDateTimeString() ?? 'n/a' }}<br>
                            Finished: {{ $run->finished_at?->toDayDateTimeString() ?? 'In progress' }}
                        </td>
                        <td class="meta">{{ $run->error_message ?? 'None' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty">No ingestion runs recorded yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section>
        <h2>Latest Indicators</h2>
        <div class="indicator-legend">
            <div class="indicator-toolbar">
                <div>
                    <strong>How to read these values</strong>
                    <p class="meta">
                        Scores run from 0 to 10. `Combined` is the average of the component scores that had data in the snapshot window.
                        `0.00` means the snapshot found no meaningful recent signal for that scope. Event counts show how many recent records fed each component.
                        City indicators use weather + news. Route indicators use flight + news, so those values should not be expected to match.
                    </p>
                </div>

                <button type="button" id="indicator-detail-toggle" class="secondary" aria-expanded="true">
                    Hide Details
                </button>
            </div>
        </div>
        <div class="grid triple">
            <div class="panel">
                <h3>Airport Indicators</h3>
                <div class="scroll">
                    <table>
                        <thead>
                        <tr>
                            <th>Airport</th>
                            <th>As Of</th>
                            <th>Combined</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($airportIndicators as $indicator)
                            <tr>
                                <td>
                                    <strong>{{ $indicator->airport?->iata ?? 'n/a' }}</strong>
                                    <div class="meta">{{ $indicator->airport?->city?->name ?? 'n/a' }}</div>
                                    <div class="meta indicator-breakdown">
                                        Weather {{ number_format((float) ($indicator->weather_score ?? 0), 2) }}
                                        ({{ $indicator->supporting_factors['weather']['events_count'] ?? 0 }} events)<br>
                                        Flight {{ number_format((float) ($indicator->flight_score ?? 0), 2) }}
                                        ({{ $indicator->supporting_factors['flight']['events_count'] ?? 0 }} events)<br>
                                        News {{ number_format((float) ($indicator->news_score ?? 0), 2) }}
                                        ({{ $indicator->supporting_factors['news']['events_count'] ?? 0 }} events)
                                    </div>
                                </td>
                                <td class="meta">{{ $indicator->as_of?->toDayDateTimeString() ?? 'n/a' }}</td>
                                <td class="indicator-score">{{ number_format($indicator->combined_score, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="empty">No airport indicators yet.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel">
                <h3>City Indicators</h3>
                <p class="meta">Scope: city-level weather + news only.</p>
                <div class="scroll">
                    <table>
                        <thead>
                        <tr>
                            <th>City</th>
                            <th>As Of</th>
                            <th>Combined</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($cityIndicators as $indicator)
                            <tr>
                                <td>
                                    <strong>{{ $indicator->city?->name ?? 'n/a' }}</strong>
                                    <div class="meta">{{ $indicator->city?->country?->name ?? 'n/a' }}</div>
                                    <div class="meta indicator-breakdown">
                                        Weather {{ number_format((float) ($indicator->weather_score ?? 0), 2) }}
                                        ({{ $indicator->supporting_factors['weather']['events_count'] ?? 0 }} events)<br>
                                        News {{ number_format((float) ($indicator->news_score ?? 0), 2) }}
                                        ({{ $indicator->supporting_factors['news']['events_count'] ?? 0 }} events)
                                    </div>
                                </td>
                                <td class="meta">{{ $indicator->as_of?->toDayDateTimeString() ?? 'n/a' }}</td>
                                <td class="indicator-score">{{ number_format($indicator->combined_score, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="empty">No city indicators yet.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel">
                <h3>Route Indicators</h3>
                <p class="meta">Scope: route-level flight + news only.</p>
                <div class="scroll">
                    <table>
                        <thead>
                        <tr>
                            <th>Route</th>
                            <th>Travel Date</th>
                            <th>Combined</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($routeIndicators as $indicator)
                            <tr>
                                <td>
                                    <strong>
                                        {{ $indicator->route?->originAirport?->iata ?? 'n/a' }}
                                        →
                                        {{ $indicator->route?->destinationAirport?->iata ?? 'n/a' }}
                                    </strong>
                                    <div class="meta">
                                        {{ $indicator->route?->originAirport?->city?->name ?? 'n/a' }}
                                        to
                                        {{ $indicator->route?->destinationAirport?->city?->name ?? 'n/a' }}
                                    </div>
                                    <div class="meta indicator-breakdown">
                                        Flight {{ number_format((float) ($indicator->flight_score ?? 0), 2) }}
                                        ({{ $indicator->supporting_factors['flight']['events_count'] ?? 0 }} events)<br>
                                        News {{ number_format((float) ($indicator->news_score ?? 0), 2) }}
                                        ({{ $indicator->supporting_factors['news']['events_count'] ?? 0 }} events)
                                    </div>
                                </td>
                                <td class="meta">{{ $indicator->travel_date?->format('Y-m-d') ?? 'Overall' }}</td>
                                <td class="indicator-score">{{ number_format($indicator->combined_score, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="empty">No route indicators yet.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <section>
        <h2>Failed Jobs</h2>
        <div class="scroll">
            <table>
                <thead>
                <tr>
                    <th>Queue</th>
                    <th>Connection</th>
                    <th>Failed At</th>
                    <th>Exception</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($failedJobs as $job)
                    <tr>
                        <td><strong>{{ $job->queue }}</strong></td>
                        <td class="meta">{{ $job->connection }}</td>
                        <td class="meta">{{ $job->failed_at?->toDayDateTimeString() ?? 'n/a' }}</td>
                        <td class="meta">{{ \Illuminate\Support\Str::limit($job->exception, 180) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty">No failed jobs recorded.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>
<script>
    (() => {
        const toggle = document.getElementById('indicator-detail-toggle');

        if (!toggle) {
            return;
        }

        const breakdowns = Array.from(document.querySelectorAll('.indicator-breakdown'));

        toggle.addEventListener('click', () => {
            const shouldHide = toggle.getAttribute('aria-expanded') === 'true';

            breakdowns.forEach((element) => {
                element.classList.toggle('is-hidden', shouldHide);
            });

            toggle.setAttribute('aria-expanded', shouldHide ? 'false' : 'true');
            toggle.textContent = shouldHide ? 'Show Details' : 'Hide Details';
        });
    })();
</script>
</body>
</html>
