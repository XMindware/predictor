# Predictor

Predictor is a short-term travel disruption risk engine for revenue and operations teams.

The MVP is intentionally narrow:
- next `72` hours only
- monitored routes only
- route-level and airport-level disruption, not passenger-level itinerary prediction
- deterministic weights and rules, not ML

The product framing is: estimate short-term travel disruption risk and probable no-show uplift, not deterministic no-show prediction.

## MVP Surface

Public API:
- `GET /api/health`
- `POST /api/tokens`
- `POST /api/risk-assessment`
- `GET /api/routes/risk`

Internal admin:
- `/dashboard`
- `/admin/providers`
- `/admin/routes`
- `/admin/ops`
- `/admin/data-inspection`

## Stack

- Laravel 11
- PostgreSQL
- Redis
- Sanctum for API tokens
- queue worker for ingestion and aggregation jobs
- Laravel Scheduler for recurring polling

## First-Time Setup

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Create the default internal user:

```bash
php artisan db:seed --class=SuperAdminSeeder
```

Seed baseline geography, routes, provider registry, and scoring profile:

```bash
php artisan db:seed --class=BasicGeographySeeder
php artisan db:seed --class=DefaultRouteSeeder
php artisan db:seed --class=ProviderRegistrySeeder
php artisan db:seed --class=ScoringProfileSeeder
```

Important: `php artisan db:seed` runs demo source seeders too:
- `WeatherSourceSeeder`
- `FlightSourceSeeder`
- `NewsSourceSeeder`

Use that only when you want demo data. For live provider testing, seed the baseline classes above individually instead.

## Key Env Vars

- `BASE_AIRPORT_IATA`
  Used as the base destination airport for seeded routes and several ops tools.
- `V1_RISK_WINDOW_HOURS`
  Public API time horizon. Default `72`.
- `V1_ROUTE_RISK_LIMIT`
  Max number of ranked routes returned by `/api/routes/risk`. Default `10`.
- `QUERY_CACHE_TTL_SECONDS`
  Cache TTL for public risk queries.
- `SUPERADMIN_NAME`
- `SUPERADMIN_EMAIL`
- `SUPERADMIN_PASSWORD`

## Provider Registry

Providers are stored in:
- `providers`
- `provider_credentials`
- `provider_configs`

You can manage them from `/admin/providers` or seed defaults with:

```bash
php artisan db:seed --class=ProviderRegistrySeeder
```

Default providers:
- `openweather`
- `flightstats`
- `newsapi`

### Required Provider Config

OpenWeather:
- credential: `api_key`
- configs:
  - `base_url`
  - `timeout_seconds`
  - `units`
- note: airports must have `latitude` and `longitude`

FlightStats:
- credentials:
  - `app_id`
  - `app_key`
- configs:
  - `base_url`
  - `timeout_seconds`
  - `max_days_ahead`
  - `max_flights`
- note: current implementation uses route status lookups and only supports near-term route-level monitoring

NewsAPI:
- credential: `api_key`
- configs:
  - `base_url`
  - `timeout_seconds`
  - `language`
  - `page_size`
  - optional `sort_by`

## How Providers Are Used

Weather:
- fetched by airport using lat/lng
- stored as raw payloads
- normalized into `weather_events`

Flights:
- fetched by monitored origin/destination route
- stored as raw payloads
- normalized into `flight_events`

News:
- fetched by monitored city context
- stored as raw payloads
- normalized into `news_events`

## Seed Strategy

Baseline seeders:
- `SuperAdminSeeder`
- `BasicGeographySeeder`
- `DefaultRouteSeeder`
- `ProviderRegistrySeeder`
- `ScoringProfileSeeder`

Demo source-data seeders:
- `WeatherSourceSeeder`
- `FlightSourceSeeder`
- `NewsSourceSeeder`

Recommended live setup:

```bash
php artisan migrate:fresh
php artisan db:seed --class=SuperAdminSeeder
php artisan db:seed --class=BasicGeographySeeder
php artisan db:seed --class=DefaultRouteSeeder
php artisan db:seed --class=ProviderRegistrySeeder
php artisan db:seed --class=ScoringProfileSeeder
```

If you previously seeded demo source data and want to switch to real providers, clear the demo data first or use `migrate:fresh`.

## Manual Provider Testing

From `/admin/providers`:
- open a provider
- fill credentials/configs
- click `Test API`

Current test behavior:
- weather tests a fetch against `BASE_AIRPORT_IATA`
- flights test a sample route like `JFK -> BASE_AIRPORT_IATA`
- news tests a disruption/travel query for the base airport

From `/admin/ops`:
- `Re-fetch Weather For City`
- `Re-fetch News For City`
- `Re-fetch Flights For Route`
- `Rebuild Indicators`
- `Recompute Risk`
- `Query City Score`

These tools are the fastest way to validate the pipeline end to end without waiting for the scheduler.

## Scheduler

Defined in `routes/console.php`.

Current cadence:
- `health:heartbeat` every minute
- `ingestion:fetch-flights` every 15 minutes
- `ingestion:fetch-weather` every 30 minutes
- `ingestion:fetch-news` every 30 minutes
- `ingestion:retry-normalization` every 10 minutes
- `indicators:build-airports` hourly at `:05`
- `indicators:build-cities` hourly at `:10`
- `indicators:build-routes` hourly at `:15`
- `cache:warm-popular-routes` hourly at `:20`
- `health:check-stale-data` every 15 minutes
- `monitoring:refresh-alerts` every 5 minutes

For automation to work you need both:
- scheduler:
  - `php artisan schedule:work`
  - or cron with `php artisan schedule:run`
- queue worker:
  - `php artisan queue:work`

## Public API Behavior

`POST /api/risk-assessment`
- requires Sanctum token
- only accepts travel dates within the next `V1_RISK_WINDOW_HOURS`
- only supports active monitored routes
- returns:
  - score
  - risk level
  - confidence
  - freshness
  - drivers
  - probable no-show uplift
  - recommended action

`GET /api/routes/risk`
- requires Sanctum token
- ranks only monitored routes
- caps output at `V1_ROUTE_RISK_LIMIT`
- same 72-hour travel window constraint

## What To Inspect When Ingestion Breaks

Start in `/admin/ops`:
- `Providers`
  Confirm provider is active and has credentials/config rows.
- `Ingestion Runs`
  Check status, payload count, records created, timing, and error message.
- `Latest Indicators`
  Confirm new airport/city/route snapshots are still being built.
- `Failed Jobs`
  Check queue failures.

Then use `/admin/data-inspection`:
- choose city and date
- inspect normalized weather, news, and flight rows for that slice

Then inspect these tables directly if needed:
- `ingestion_runs`
- `raw_provider_payloads`
- `weather_events`
- `flight_events`
- `news_events`
- `airport_indicators`
- `city_indicators`
- `route_indicators`

Typical failure flow:
1. provider credentials/config wrong
2. ingestion run fails or returns zero payloads
3. raw payload exists but normalization did not happen
4. events exist but indicators were not rebuilt
5. indicators exist but route is not monitored, so public API rejects it

## Useful Commands

```bash
php artisan migrate
php artisan db:seed --class=ProviderRegistrySeeder
php artisan db:seed --class=DefaultRouteSeeder
php artisan ingestion:fetch-weather
php artisan ingestion:fetch-flights
php artisan ingestion:fetch-news
php artisan ingestion:retry-normalization
php artisan indicators:build-airports
php artisan indicators:build-cities
php artisan indicators:build-routes
php artisan health:check-stale-data
php artisan monitoring:refresh-alerts
php artisan test
```

If local tests start trying to use the wrong DB host, clear cached bootstrap state:

```bash
php artisan config:clear
php artisan route:clear
```

## Recommended Ops Flow

1. Seed baseline geography, routes, providers, and scoring profile.
2. Fill provider credentials/configs in `/admin/providers`.
3. Use `Test API` for each provider.
4. Use `/admin/ops` manual triggers to fetch weather, flights, and news.
5. Rebuild indicators.
6. Inspect `/admin/data-inspection`.
7. Recompute risk and verify the decision output.
8. Enable scheduler and queue worker for continuous operation.
