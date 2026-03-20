<?php

namespace App\Services\Providers\Adapters;

use App\Contracts\Providers\WeatherProviderInterface;
use App\Data\Providers\WeatherData;

class StubWeatherProvider implements WeatherProviderInterface
{
    public function fetchWeather(array $criteria = []): array
    {
        $locationCode = (string) ($criteria['location_code'] ?? 'UNKNOWN');
        $providerSlug = (string) ($criteria['provider_slug'] ?? 'stub-weather');

        return [
            new WeatherData(
                providerSlug: $providerSlug,
                locationCode: $locationCode,
                externalReference: sprintf('weather:%s:%s', $providerSlug, $locationCode),
                timezone: (string) ($criteria['timezone'] ?? 'UTC'),
                observedAt: now()->toIso8601String(),
                temperatureCelsius: 28.4,
                precipitationProbability: 0.2,
                condition: 'clear',
                meta: [
                    'date_window_days' => $criteria['date_window_days'] ?? null,
                    'watch_target_id' => $criteria['watch_target_id'] ?? null,
                ],
            ),
        ];
    }
}
