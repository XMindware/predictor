<?php

/**
 * Resolve an env string like "CUN,SJD , MEX" into a clean array of IATAs.
 *
 * @return list<string>
 */
function resolveBaseAirports(): array
{
    $raw = env('BASE_AIRPORTS', env('BASE_AIRPORT_IATA', 'CUN'));

    return collect(explode(',', (string) $raw))
        ->map(fn (string $v): string => strtoupper(trim($v)))
        ->filter()
        ->unique()
        ->values()
        ->all();
}

return [
    // ── Single base airport (kept for backwards-compat with API / v1 endpoints) ──
    'base_airport_iata' => strtoupper((string) env('BASE_AIRPORT_IATA', 'CUN')),

    // ── Multi-destination: comma-separated list of monitored destination IATAs ──
    // e.g.  BASE_AIRPORTS=CUN,SJD   — routes and watch targets will be created
    // for all airports listed here.  If unset, falls back to BASE_AIRPORT_IATA.
    'base_airports' => resolveBaseAirports(),

    // ── API scoring / ranking ─────────────────────────────────────────────────
    'v1_risk_window_hours' => (int) env('V1_RISK_WINDOW_HOURS', 72),
    'v1_route_risk_limit'  => (int) env('V1_ROUTE_RISK_LIMIT', 10),

    // ── Ingestion schedule overrides (minutes) ────────────────────────────────
    // Set these in .env to tune fetch frequency per source type.
    'schedule' => [
        'news_interval_minutes'    => (int) env('SCHEDULE_NEWS_MINUTES',    30),
        'weather_interval_minutes' => (int) env('SCHEDULE_WEATHER_MINUTES', 30),
        'flights_interval_minutes' => (int) env('SCHEDULE_FLIGHTS_MINUTES', 15),
    ],
];
