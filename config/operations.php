<?php

return [
    'base_airport_iata' => strtoupper((string) env('BASE_AIRPORT_IATA', 'CUN')),
    'v1_risk_window_hours' => (int) env('V1_RISK_WINDOW_HOURS', 72),
    'v1_route_risk_limit' => (int) env('V1_ROUTE_RISK_LIMIT', 10),
];
