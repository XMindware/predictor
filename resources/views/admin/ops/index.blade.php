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
        .result .panel {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.12);
        }
        .result .panel h3,
        .result .panel p,
        .result .panel li,
        .result .panel strong,
        .result .panel span {
            color: #f8fafc;
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
        details {
            border: 1px solid #dbe2ef;
            border-radius: 12px;
            background: #f8fbff;
            padding: 10px 12px;
        }
        details + details {
            margin-top: 10px;
        }
        summary {
            cursor: pointer;
            font-weight: 700;
            color: #172033;
        }
        .code-block {
            margin-top: 10px;
            padding: 12px;
            border-radius: 10px;
            background: #0f172a;
            color: #e2e8f0;
            overflow-x: auto;
            font-size: 13px;
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
        $flightFetchResult = $manualToolResult && ($manualToolResult['tool'] ?? null) === 're-fetch flights'
            ? $manualToolResult['details']
            : null;
        $cityRiskResult = $manualToolResult && ($manualToolResult['tool'] ?? null) === 'query city risk'
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
                @elseif ($flightFetchResult)
                    @php
                        $fetchedFlights = collect($flightFetchResult['fetched_flights'] ?? []);
                    @endphp
                    <div class="score-summary">
                        <div>
                            <strong>Flight Fetch Result</strong>
                            <p>
                                {{ $flightFetchResult['route'] ?? 'n/a' }} fetched from {{ $flightFetchResult['providers'] ?? 0 }} provider(s).
                            </p>
                            <p class="score-note">
                                Payloads created: {{ $flightFetchResult['payloads'] ?? 0 }} ·
                                flight records created: {{ $flightFetchResult['normalized_events'] ?? 0 }}
                            </p>
                        </div>

                        <div class="detail-grid">
                            <div class="detail-card">
                                <span>Route</span>
                                <strong>{{ $flightFetchResult['route'] ?? 'n/a' }}</strong>
                                <small>Manual re-fetch completed.</small>
                            </div>
                            <div class="detail-card">
                                <span>Providers</span>
                                <strong>{{ $flightFetchResult['providers'] ?? 0 }}</strong>
                                <small>Active provider(s) queried.</small>
                            </div>
                            <div class="detail-card">
                                <span>Payloads</span>
                                <strong>{{ $flightFetchResult['payloads'] ?? 0 }}</strong>
                                <small>Raw provider payload(s) stored.</small>
                            </div>
                            <div class="detail-card">
                                <span>Flight Records</span>
                                <strong>{{ $flightFetchResult['normalized_events'] ?? 0 }}</strong>
                                <small>Normalized flight event row(s) created.</small>
                            </div>
                        </div>

                        <div class="panel">
                            <h3>Fetched Flights</h3>
                            @if ($fetchedFlights->isEmpty())
                                <p class="empty">No flights were returned for this route during the manual fetch.</p>
                            @else
                                <div class="scroll">
                                    <table>
                                        <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Airline</th>
                                            <th>Delay</th>
                                            <th>Cancelled</th>
                                            <th>Score</th>
                                            <th>Provider</th>
                                            <th>Summary</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach ($fetchedFlights as $flight)
                                            <tr>
                                                <td>{{ $flight['travel_date'] ?? 'n/a' }}</td>
                                                <td>{{ $flight['airline_code'] ?? 'n/a' }}</td>
                                                <td>{{ number_format((float) ($flight['delay_average_minutes'] ?? 0), 1) }} min</td>
                                                <td>{{ ((float) ($flight['cancellation_rate'] ?? 0)) > 0 ? 'Yes' : 'No' }}</td>
                                                <td>{{ number_format((float) ($flight['disruption_score'] ?? 0), 2) }}</td>
                                                <td>{{ $flight['provider'] ?? 'n/a' }}</td>
                                                <td>{{ $flight['summary'] ?? 'n/a' }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                @elseif ($cityRiskResult)
                    @php
                        $primaryAssessment = $cityRiskResult['primary_assessment'] ?? [];
                        $cityDrivers = collect($primaryAssessment['drivers'] ?? [])->take(4);
                        $cityComponentAges = collect($primaryAssessment['freshness']['component_ages'] ?? []);
                        $citySources = $cityRiskResult['source_details'] ?? [];
                    @endphp
                    <div class="score-summary">
                        <div>
                            <strong>City Risk Summary</strong>
                            <p>
                                {{ $cityRiskResult['city'] }}{{ !empty($cityRiskResult['country']) ? ', '.$cityRiskResult['country'] : '' }}
                                for the next {{ $cityRiskResult['window_hours'] }} hours,
                                from {{ \Illuminate\Support\Carbon::parse($cityRiskResult['from_time'])->toDayDateTimeString() }}
                                to {{ \Illuminate\Support\Carbon::parse($cityRiskResult['to_time'])->toDayDateTimeString() }}.
                            </p>
                            <p class="score-note">
                                This evaluates monitored routes from the selected city into the base airport
                                {{ $cityRiskResult['base_airport_iata'] ?? 'n/a' }}
                                and highlights the highest assessed risk in that near-term window.
                            </p>
                            <p class="score-note">{{ $cityRiskResult['product_framing'] ?? 'Estimate of short-term travel disruption risk and probable no-show uplift.' }}</p>
                        </div>

                        <div class="detail-grid">
                            <div class="detail-card">
                                <span>Highest Window Risk</span>
                                <strong>{{ number_format((float) ($primaryAssessment['score'] ?? 0), 2) }}</strong>
                                <small>{{ strtoupper((string) ($primaryAssessment['risk_level'] ?? 'unknown')) }} on {{ $primaryAssessment['travel_date'] ?? 'n/a' }}</small>
                            </div>
                            <div class="detail-card">
                                <span>Lead Route</span>
                                <strong>{{ $primaryAssessment['route_label'] ?? 'n/a' }}</strong>
                                <small>{{ $cityRiskResult['routes_evaluated'] ?? 0 }} monitored route(s) evaluated</small>
                            </div>
                            <div class="detail-card">
                                <span>Confidence</span>
                                <strong>{{ strtoupper((string) ($primaryAssessment['confidence']['level'] ?? 'unknown')) }}</strong>
                                <small>{{ number_format(((float) ($primaryAssessment['confidence']['score'] ?? 0)) * 100, 0) }}% weighted coverage</small>
                            </div>
                            <div class="detail-card">
                                <span>Data Freshness</span>
                                <strong>{{ strtoupper((string) ($primaryAssessment['freshness']['level'] ?? 'unknown')) }}</strong>
                                <small>Stalest signal: {{ $primaryAssessment['freshness']['minutes_since_stalest_signal'] ?? 'n/a' }} min old</small>
                            </div>
                            <div class="detail-card">
                                <span>Probable No-show Uplift</span>
                                <strong>{{ number_format((float) ($primaryAssessment['probable_no_show_uplift']['estimate_percent'] ?? 0), 1) }}%</strong>
                                <small>
                                    Range {{ number_format((float) ($primaryAssessment['probable_no_show_uplift']['range_percent']['low'] ?? 0), 1) }}%
                                    to {{ number_format((float) ($primaryAssessment['probable_no_show_uplift']['range_percent']['high'] ?? 0), 1) }}%
                                </small>
                            </div>
                        </div>

                        <p class="risk-meta">
                            <strong>Recommended Action</strong><br>
                            {{ $primaryAssessment['recommended_action']['summary'] ?? 'Continue monitoring this market.' }}
                            @if (!empty($primaryAssessment['recommended_action']['primary_driver']))
                                Primary driver: {{ str_replace('_', ' ', $primaryAssessment['recommended_action']['primary_driver']) }}.
                            @endif
                        </p>

                        <div class="grid dual">
                            <div class="panel">
                                <h3>Source Details</h3>
                                <ul class="result-list">
                                    <li>
                                        Weather:
                                        {{ $citySources['weather']['events_found'] ?? 0 }} event(s)
                                        @if (!empty($citySources['weather']['top_condition']))
                                            · top condition {{ $citySources['weather']['top_condition'] }}
                                        @endif
                                        @if (($citySources['weather']['average_temperature'] ?? null) !== null)
                                            · avg temp {{ number_format((float) $citySources['weather']['average_temperature'], 1) }}C
                                        @endif
                                    </li>
                                    <li>
                                        News:
                                        {{ $citySources['news']['articles_found'] ?? 0 }} article(s)
                                        @if (!empty($citySources['news']['top_category']))
                                            · top category {{ $citySources['news']['top_category'] }}
                                        @endif
                                    </li>
                                    <li>
                                        Flights:
                                        {{ $citySources['flights']['total_records'] ?? 0 }} total
                                        · {{ $citySources['flights']['delayed_records'] ?? 0 }} delayed
                                        · {{ $citySources['flights']['cancelled_records'] ?? 0 }} cancelled
                                        @if (($citySources['flights']['average_delay_minutes'] ?? null) !== null)
                                            · avg delay {{ number_format((float) $citySources['flights']['average_delay_minutes'], 1) }} min
                                        @endif
                                    </li>
                                    <li>
                                        Airlines to {{ $cityRiskResult['base_airport_iata'] ?? 'destination' }}:
                                        @if (!empty($citySources['flights']['airlines']))
                                            {{ collect($citySources['flights']['airlines'])
                                                ->map(fn (array $airline): string => ($airline['code'] ?? 'UNKNOWN').' ('.($airline['records'] ?? 0).')')
                                                ->implode(', ') }}
                                        @else
                                            none found in the selected window
                                        @endif
                                    </li>
                                </ul>
                            </div>

                            <div class="panel">
                                <h3>Top Drivers</h3>
                                <ol class="driver-list">
                                    @forelse ($cityDrivers as $driver)
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
                                    @forelse ($cityComponentAges as $factor => $age)
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

                        <div class="grid dual">
                            <div class="panel">
                                <h3>Daily Outlook</h3>
                                <ul class="result-list">
                                    @foreach (($cityRiskResult['daily_outlook'] ?? []) as $day)
                                        <li>
                                            {{ $day['travel_date'] }}:
                                            {{ $day['route_label'] }} ·
                                            score {{ number_format((float) ($day['score'] ?? 0), 2) }} ·
                                            {{ strtoupper((string) ($day['risk_level'] ?? 'unknown')) }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="panel">
                                <h3>Route Outlook</h3>
                                <ul class="result-list">
                                    @foreach (($cityRiskResult['route_outlook'] ?? []) as $route)
                                        <li>
                                            {{ $route['route_label'] }}:
                                            top score {{ number_format((float) ($route['top_score'] ?? 0), 2) }}
                                            on {{ $route['travel_date'] ?? 'n/a' }} ·
                                            {{ strtoupper((string) ($route['risk_level'] ?? 'unknown')) }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>

                        <div class="panel">
                            <h3>Evaluation Summary</h3>
                            <ul class="result-list">
                                @foreach (($primaryAssessment['explanations'] ?? []) as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @else
                    <strong>{{ ucfirst($manualToolResult['tool']) }}</strong>
                    <pre>{{ json_encode($manualToolResult['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                @endif
            </div>
        @endif

        <div class="grid dual">
            <div class="panel">
                <h3>City Risk Window</h3>
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
                        Time window
                        <select name="time_window_hours">
                            <option value="">Next {{ config('operations.v1_risk_window_hours', 72) }} hours</option>
                            <option value="24">Next 24 hours</option>
                            <option value="48">Next 48 hours</option>
                            <option value="72">Next 72 hours</option>
                        </select>
                    </label>

                    <button type="submit">Get City Risk</button>
                    <p class="meta">Evaluates monitored routes from the selected city into the configured base airport over the near-term window and returns a readable risk assessment with confidence, freshness, drivers, probable no-show uplift, and recommended action.</p>
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
                    <tr>
                        <td colspan="6">
                            <details>
                                <summary>Run request / response details</summary>
                                <div class="meta" style="margin-top: 10px;">
                                    Request meta
                                </div>
                                <pre class="code-block">{{ json_encode($run->request_meta ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>

                                <div class="meta" style="margin-top: 12px;">
                                    Response meta
                                </div>
                                <pre class="code-block">{{ json_encode($run->response_meta ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>

                                @if ($run->rawPayloads->isNotEmpty())
                                    <div class="meta" style="margin-top: 12px;">
                                        Raw payloads stored for this run
                                    </div>

                                    @foreach ($run->rawPayloads as $payload)
                                        @php
                                            $items = $payload->payload['items'] ?? [];
                                            $criteria = $payload->payload['criteria'] ?? [];
                                            $httpExchanges = $payload->payload['http_exchanges'] ?? [];
                                        @endphp
                                        <details>
                                            <summary>
                                                Payload {{ $payload->id }}
                                                · fetched {{ $payload->fetched_at?->toDayDateTimeString() ?? 'n/a' }}
                                                · items {{ is_countable($items) ? count($items) : 0 }}
                                            </summary>

                                            @if (!empty($httpExchanges))
                                                @foreach ($httpExchanges as $index => $exchange)
                                                    <div class="meta" style="margin-top: 10px;">
                                                        API request payload #{{ $index + 1 }}
                                                    </div>
                                                    <pre class="code-block">{{ json_encode([
                                                        'requested_at' => $exchange['requested_at'] ?? null,
                                                        'method' => $exchange['method'] ?? null,
                                                        'url' => $exchange['url'] ?? null,
                                                        'request' => $exchange['request'] ?? [],
                                                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>

                                                    <div class="meta" style="margin-top: 12px;">
                                                        API response payload #{{ $index + 1 }}
                                                    </div>
                                                    <pre class="code-block">{{ json_encode($exchange['response'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                @endforeach
                                            @else
                                                <div class="meta" style="margin-top: 10px;">
                                                    API request payload
                                                </div>
                                                <pre class="code-block">{{ json_encode($criteria, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>

                                                <div class="meta" style="margin-top: 12px;">
                                                    API response payload
                                                </div>
                                                <pre class="code-block">{{ json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                            @endif

                                            <div class="meta" style="margin-top: 12px;">
                                                Normalized items stored
                                            </div>
                                            <pre class="code-block">{{ json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </details>
                                    @endforeach
                                @else
                                    <div class="meta" style="margin-top: 12px;">
                                        No raw payloads were stored for this run.
                                    </div>
                                @endif
                            </details>
                        </td>
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
