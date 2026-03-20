<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    public function test_health_endpoint_returns_platform_status(): void
    {
        Config::set('app.env', 'testing');
        Config::set('app.name', 'Predictor');
        Config::set('database.default', 'pgsql');
        Config::set('database.connections.pgsql.database', 'predictor');
        Config::set('database.redis.client', 'phpredis');
        Config::set('database.redis.default.host', 'redis');
        Config::set('queue.default', 'redis');
        Config::set('queue.connections.redis.connection', 'default');
        Config::set('queue.connections.redis.queue', 'default');
        Config::set('cache.default', 'array');

        Cache::forever('health:scheduler:last_seen', now()->toIso8601String());
        Cache::forever('health:queue-worker:last_seen', now()->toIso8601String());

        DB::shouldReceive('connection->select')->once()->with('select 1')->andReturn([['?column?' => 1]]);
        DB::shouldReceive('connection->getSchemaBuilder->hasTable')->once()->with('migrations')->andReturn(true);
        DB::shouldReceive('table->pluck->all')->once()->andReturn([
            '0001_01_01_000000_create_users_table',
            '0001_01_01_000001_create_cache_table',
            '0001_01_01_000002_create_jobs_table',
            '2026_03_19_201000_create_personal_access_tokens_table',
            '2026_03_19_202000_create_geography_tables',
            '2026_03_19_203000_create_routes_table',
            '2026_03_19_204000_create_watch_targets_table',
            '2026_03_19_205000_create_providers_table',
            '2026_03_19_205100_create_provider_credentials_table',
            '2026_03_19_205200_create_provider_configs_table',
            '2026_03_19_210000_create_ingestion_runs_table',
            '2026_03_19_211000_create_raw_provider_payloads_table',
            '2026_03_19_212000_create_weather_events_table',
            '2026_03_19_212100_create_flight_events_table',
            '2026_03_19_212200_create_news_events_table',
            '2026_03_19_213000_add_normalized_at_to_raw_provider_payloads_table',
            '2026_03_19_214000_create_airport_indicators_table',
            '2026_03_19_215000_create_city_indicators_table',
            '2026_03_19_216000_create_route_indicators_table',
            '2026_03_19_217000_create_scoring_profiles_table',
            '2026_03_19_218000_create_risk_query_snapshots_table',
        ]);
        Redis::shouldReceive('connection->ping')->once()->withNoArgs()->andReturn('PONG');

        $this->getJson('/api/health')
            ->assertOk()
            ->assertJson([
                'status' => 'ok',
                'app' => 'Predictor',
                'environment' => 'testing',
                'checks' => [
                    'database' => [
                        'status' => 'ok',
                        'connection' => 'pgsql',
                        'database' => 'predictor',
                    ],
                    'redis' => [
                        'status' => 'ok',
                        'client' => 'phpredis',
                        'connection' => 'default',
                        'host' => 'redis',
                    ],
                    'migrations' => [
                        'status' => 'ok',
                        'ran' => 21,
                        'pending' => 0,
                    ],
                    'queue' => [
                        'status' => 'ok',
                        'connection' => 'redis',
                        'queue' => 'default',
                        'max_age_seconds' => 180,
                    ],
                    'scheduler' => [
                        'status' => 'ok',
                        'frequency' => 'every_minute',
                        'max_age_seconds' => 180,
                    ],
                ],
            ])
            ->assertJsonStructure([
                'timestamp',
                'checks' => [
                    'queue' => ['last_seen', 'age_seconds'],
                    'scheduler' => ['last_seen', 'age_seconds'],
                ],
            ]);
    }
}
