<?php

namespace Database\Seeders;

use App\Models\Provider;
use Illuminate\Database\Seeder;

class ProviderRegistrySeeder extends Seeder
{
    /**
     * Seed baseline external providers for weather, flights, and news.
     */
    public function run(): void
    {
        $providers = [
            [
                'name' => 'OpenWeather',
                'slug' => 'openweather',
                'service' => 'weather',
                'driver' => 'rest',
                'active' => true,
                'notes' => 'Default weather provider.',
                'configs' => [
                    'base_url' => ['value' => 'https://api.openweathermap.org'],
                    'timeout_seconds' => ['value' => '10'],
                    'units' => ['value' => 'metric'],
                ],
                'credentials' => [
                    'api_key' => ['value' => null, 'is_secret' => true],
                ],
            ],
            [
                'name' => 'FlightStats',
                'slug' => 'flightstats',
                'service' => 'flights',
                'driver' => 'rest',
                'active' => true,
                'notes' => 'Primary flight search provider.',
                'configs' => [
                    'base_url' => ['value' => 'https://api.flightstats.com'],
                    'timeout_seconds' => ['value' => '15'],
                    'max_days_ahead' => ['value' => '3'],
                    'max_flights' => ['value' => '10'],
                ],
                'credentials' => [
                    'app_id' => ['value' => null, 'is_secret' => true],
                    'app_key' => ['value' => null, 'is_secret' => true],
                ],
            ],
            [
                'name' => 'NewsAPI',
                'slug' => 'newsapi',
                'service' => 'news',
                'driver' => 'rest',
                'active' => true,
                'notes' => 'Default travel and disruption news provider.',
                'configs' => [
                    'base_url' => ['value' => 'https://newsapi.org'],
                    'timeout_seconds' => ['value' => '10'],
                    'language' => ['value' => 'en'],
                    'page_size' => ['value' => '10'],
                ],
                'credentials' => [
                    'api_key' => ['value' => null, 'is_secret' => true],
                ],
            ],
        ];

        collect($providers)->each(function (array $providerData): void {
            $provider = Provider::query()->updateOrCreate(
                ['slug' => $providerData['slug']],
                [
                    'name' => $providerData['name'],
                    'service' => $providerData['service'],
                    'driver' => $providerData['driver'],
                    'active' => $providerData['active'],
                    'notes' => $providerData['notes'],
                ],
            );

            collect($providerData['configs'])->each(function (array $configData, string $key) use ($provider): void {
                $provider->configs()->updateOrCreate(
                    ['key' => $key],
                    ['value' => $configData['value']],
                );
            });

            collect($providerData['credentials'])->each(function (array $credentialData, string $key) use ($provider): void {
                $provider->credentials()->updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $credentialData['value'],
                        'is_secret' => $credentialData['is_secret'],
                    ],
                );
            });
        });
    }
}
