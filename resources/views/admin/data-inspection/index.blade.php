<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fetched Data Inspection</title>
    <style>
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, sans-serif;
            background: #f5f7fb;
            color: #172033;
        }
        main {
            max-width: 1320px;
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
        section + section {
            margin-top: 24px;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }
        h1, h2, h3 {
            margin-top: 0;
        }
        p {
            color: #5b667a;
        }
        .actions {
            display: flex;
            gap: 12px;
            align-items: center;
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
        .grid {
            display: grid;
            gap: 24px;
        }
        .dual {
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        }
        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
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
        }
        .summary {
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
            font-size: 30px;
            color: #0f172a;
        }
        .metric span {
            display: block;
            margin-top: 6px;
            color: #5b667a;
            font-size: 14px;
        }
        .scroll {
            overflow-x: auto;
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
        .empty {
            color: #64748b;
            padding: 24px 0 8px;
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
            background: #dbeafe;
            color: #1d4ed8;
        }
    </style>
</head>
<body>
<main>
    <header>
        <div>
            <h1>Fetched Data Inspection</h1>
            <p>Select a city and date to inspect the normalized weather, news, and flight data currently stored for that slice.</p>
        </div>

        <div class="actions">
            <a href="{{ route('dashboard') }}" class="secondary">Back to Dashboard</a>
            <a href="{{ route('admin.ops.index') }}" class="secondary">Operations Panel</a>
            <a href="{{ route('admin.routes.index') }}" class="secondary">Routes</a>
        </div>
    </header>

    <section>
        <form method="GET" action="{{ route('admin.data-inspection.index') }}">
            <div class="grid dual">
                <label>
                    City
                    <select name="city_id" required>
                        <option value="">Select city</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}" @selected((string) request('city_id') === (string) $city->id)>
                                {{ $city->name }}{{ $city->country ? ', '.$city->country->name : '' }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label>
                    Date
                    <input type="date" name="date" value="{{ $selectedDate ?? '' }}" required>
                </label>
            </div>

            <div class="actions" style="margin-top: 18px;">
                <button type="submit">Load Data</button>
            </div>
        </form>

        @if ($selectedCity && $selectedDate)
            <div class="summary">
                <div class="metric">
                    <strong>{{ $weatherEvents->count() }}</strong>
                    <span>Weather rows</span>
                </div>
                <div class="metric">
                    <strong>{{ $newsEvents->count() }}</strong>
                    <span>News rows</span>
                </div>
                <div class="metric">
                    <strong>{{ $flightEvents->count() }}</strong>
                    <span>Flight rows</span>
                </div>
                <div class="metric">
                    <strong>{{ $selectedCity->name }}</strong>
                    <span>{{ $selectedCity->country?->name }} · {{ $selectedDate }}</span>
                </div>
            </div>
        @endif
    </section>

    @if ($selectedCity && $selectedDate)
        <section>
            <div class="actions" style="justify-content: space-between; margin-bottom: 16px;">
                <div>
                    <h2>Weather Data</h2>
                    <p>Rows are filtered by `forecast_for` on the selected date for the selected city.</p>
                </div>
                <span class="eyebrow">{{ $weatherEvents->count() }} rows</span>
            </div>

            @if ($weatherEvents->isEmpty())
                <p class="empty">No weather rows were found for this city and date.</p>
            @else
                <div class="scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Airport</th>
                                <th>Forecast For</th>
                                <th>Condition</th>
                                <th>Severity</th>
                                <th>Details</th>
                                <th>Source</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($weatherEvents as $event)
                                <tr>
                                    <td>
                                        <strong>{{ $event->airport?->iata ?? 'City-level' }}</strong>
                                        <div class="meta">{{ $event->airport?->name ?? $selectedCity->name }}</div>
                                    </td>
                                    <td>
                                        <strong>{{ $event->forecast_for?->toDayDateTimeString() ?? 'n/a' }}</strong>
                                        <div class="meta">Fetched {{ $event->event_time?->toDayDateTimeString() ?? 'n/a' }}</div>
                                    </td>
                                    <td>
                                        <strong>{{ $event->condition_code }}</strong>
                                        <div class="meta">{{ $event->summary }}</div>
                                    </td>
                                    <td>{{ number_format($event->severity_score, 2) }}</td>
                                    <td class="meta">
                                        Temp: {{ number_format($event->temperature, 1) }} C<br>
                                        Precip: {{ $event->precipitation_mm !== null ? number_format($event->precipitation_mm, 1).' mm' : 'n/a' }}<br>
                                        Wind: {{ $event->wind_speed !== null ? number_format($event->wind_speed, 1) : 'n/a' }}
                                    </td>
                                    <td class="meta">
                                        Provider: {{ $event->sourceProvider?->name ?? 'n/a' }}<br>
                                        Payload: {{ $event->rawPayload?->external_reference ?? 'n/a' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section>
            <div class="actions" style="justify-content: space-between; margin-bottom: 16px;">
                <div>
                    <h2>News Data</h2>
                    <p>Rows are filtered by `published_at` on the selected date for the selected city.</p>
                </div>
                <span class="eyebrow">{{ $newsEvents->count() }} rows</span>
            </div>

            @if ($newsEvents->isEmpty())
                <p class="empty">No news rows were found for this city and date.</p>
            @else
                <div class="scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Published</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Severity</th>
                                <th>Relevance</th>
                                <th>Source</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($newsEvents as $event)
                                <tr>
                                    <td>
                                        <strong>{{ $event->published_at?->toDayDateTimeString() ?? 'n/a' }}</strong>
                                        <div class="meta">{{ $event->airport?->iata ?? $selectedCity->name }}</div>
                                    </td>
                                    <td>
                                        <strong>{{ $event->title }}</strong>
                                        <div class="meta">{{ $event->summary }}</div>
                                    </td>
                                    <td>{{ $event->category }}</td>
                                    <td>{{ number_format($event->severity_score, 2) }}</td>
                                    <td>{{ number_format($event->relevance_score, 2) }}</td>
                                    <td class="meta">
                                        Provider: {{ $event->sourceProvider?->name ?? 'n/a' }}<br>
                                        Payload: {{ $event->rawPayload?->external_reference ?? 'n/a' }}<br>
                                        <a href="{{ $event->url }}" target="_blank" rel="noreferrer" class="secondary" style="margin-top: 8px;">Open URL</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section>
            <div class="actions" style="justify-content: space-between; margin-bottom: 16px;">
                <div>
                    <h2>Flight Data</h2>
                    <p>Rows are filtered by `travel_date` for routes whose origin airport belongs to the selected city.</p>
                </div>
                <span class="eyebrow">{{ $flightEvents->count() }} rows</span>
            </div>

            @if ($flightEvents->isEmpty())
                <p class="empty">No flight rows were found for this city and date.</p>
            @else
                <div class="scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Route</th>
                                <th>Travel Date</th>
                                <th>Airline</th>
                                <th>Disruption</th>
                                <th>Operational Detail</th>
                                <th>Source</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($flightEvents as $event)
                                <tr>
                                    <td>
                                        <strong>{{ $event->originAirport?->iata ?? 'n/a' }} → {{ $event->destinationAirport?->iata ?? 'n/a' }}</strong>
                                        <div class="meta">
                                            {{ $event->originAirport?->city?->name ?? 'n/a' }} to {{ $event->destinationAirport?->city?->name ?? 'n/a' }}
                                        </div>
                                    </td>
                                    <td>
                                        <strong>{{ $event->travel_date?->toDateString() ?? 'n/a' }}</strong>
                                        <div class="meta">Fetched {{ $event->event_time?->toDayDateTimeString() ?? 'n/a' }}</div>
                                    </td>
                                    <td>{{ $event->airline_code ?? 'n/a' }}</td>
                                    <td>{{ number_format($event->disruption_score, 2) }}</td>
                                    <td class="meta">
                                        Delay avg: {{ $event->delay_average_minutes !== null ? number_format($event->delay_average_minutes, 1).' min' : 'n/a' }}<br>
                                        Cancel rate: {{ $event->cancellation_rate !== null ? number_format($event->cancellation_rate, 2) : 'n/a' }}<br>
                                        {{ $event->summary }}
                                    </td>
                                    <td class="meta">
                                        Provider: {{ $event->sourceProvider?->name ?? 'n/a' }}<br>
                                        Payload: {{ $event->rawPayload?->external_reference ?? 'n/a' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif
</main>
</body>
</html>
