<?php

namespace Tests;

use App\Models\Provider;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function configureLiveProvider(Provider $provider): void
    {
        match ($provider->slug) {
            'openweather' => $this->seedProviderConnection($provider, [
                'configs' => [
                    'base_url' => 'https://api.openweathermap.org',
                    'timeout_seconds' => '10',
                    'units' => 'metric',
                ],
                'credentials' => [
                    'api_key' => 'weather-test-key',
                ],
            ]),
            'flightstats' => $this->seedProviderConnection($provider, [
                'configs' => [
                    'base_url' => 'https://api.flightstats.com',
                    'timeout_seconds' => '15',
                    'max_days_ahead' => '3',
                    'max_flights' => '10',
                ],
                'credentials' => [
                    'app_id' => 'flightstats-app-id',
                    'app_key' => 'flightstats-app-key',
                ],
            ]),
            'newsapi' => $this->seedProviderConnection($provider, [
                'configs' => [
                    'base_url' => 'https://newsapi.org',
                    'timeout_seconds' => '10',
                    'language' => 'en',
                    'page_size' => '10',
                ],
                'credentials' => [
                    'api_key' => 'newsapi-test-key',
                ],
            ]),
            default => null,
        };
    }

    protected function fakeLiveProviderResponses(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            'https://api.openweathermap.org/data/2.5/forecast*' => Http::response([
                'list' => [[
                    'dt' => 1774123200,
                    'main' => ['temp' => 28.4],
                    'pop' => 0.2,
                    'weather' => [['main' => 'Clear']],
                    'wind' => ['speed' => 4.5],
                    'rain' => ['3h' => 0.0],
                ]],
            ], 200),
            'https://newsapi.org/v2/everything*' => Http::response([
                'status' => 'ok',
                'articles' => [[
                    'source' => ['id' => 'provider', 'name' => 'Provider News'],
                    'author' => 'System',
                    'title' => 'Operational update for monitored travel',
                    'description' => 'Travel operations remain under observation.',
                    'url' => 'https://example.com/provider-news',
                    'publishedAt' => '2026-03-19T14:00:00Z',
                    'content' => 'Travel operations remain under observation.',
                ]],
            ], 200),
            'https://api.flightstats.com/flex/flightstatus/rest/v2/json/route/status/*' => Http::response([
                'flightStatuses' => [[
                    'flightId' => 123456,
                    'carrierFsCode' => 'XX',
                    'flightNumber' => '100',
                    'status' => 'S',
                    'departureAirportFsCode' => 'CUN',
                    'arrivalAirportFsCode' => 'MID',
                    'operationalTimes' => [
                        'scheduledGateDeparture' => ['dateLocal' => '2026-03-20T08:00:00.000'],
                        'scheduledGateArrival' => ['dateLocal' => '2026-03-20T11:00:00.000'],
                    ],
                    'delays' => [
                        'departureGateDelayMinutes' => 15,
                    ],
                ]],
            ], 200),
        ]);
    }

    /**
     * @param  array{
     *     configs: array<string, string>,
     *     credentials: array<string, string>
     * }  $connection
     */
    private function seedProviderConnection(Provider $provider, array $connection): void
    {
        foreach ($connection['configs'] as $key => $value) {
            $provider->configs()->updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }

        foreach ($connection['credentials'] as $key => $value) {
            $provider->credentials()->updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'is_secret' => true],
            );
        }
    }
}
