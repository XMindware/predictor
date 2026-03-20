<?php

namespace App\Services\Providers\Adapters;

use App\Contracts\Providers\WeatherProviderInterface;
use App\Data\Providers\WeatherData;
use App\Models\Airport;
use Illuminate\Support\Carbon;
use RuntimeException;

class OpenWeatherProvider extends ConfiguredHttpProvider implements WeatherProviderInterface
{
    public function fetchWeather(array $criteria = []): array
    {
        $locationCode = strtoupper((string) ($criteria['location_code'] ?? ''));
        $airport = $this->airport($locationCode);
        $count = min(40, max(1, (int) ($criteria['date_window_days'] ?? 1)) * 8);

        $response = $this->client()
            ->get('/data/2.5/forecast', [
                'lat' => $airport->latitude,
                'lon' => $airport->longitude,
                'appid' => $this->requiredCredential('api_key'),
                'units' => $this->optionalConfig('units', 'metric'),
                'cnt' => $count,
            ])
            ->throw()
            ->json();

        $timezone = (string) ($criteria['timezone'] ?? $airport->timezone ?? 'UTC');

        return collect($response['list'] ?? [])
            ->map(function (array $entry) use ($criteria, $locationCode, $timezone): WeatherData {
                $timestamp = (int) ($entry['dt'] ?? Carbon::now()->timestamp);

                return new WeatherData(
                    providerSlug: $this->provider->slug,
                    locationCode: $locationCode,
                    externalReference: sprintf('weather:%s:%s:%d', $this->provider->slug, $locationCode, $timestamp),
                    timezone: $timezone,
                    observedAt: Carbon::createFromTimestampUTC($timestamp)->toIso8601String(),
                    temperatureCelsius: isset($entry['main']['temp']) ? (float) $entry['main']['temp'] : null,
                    precipitationProbability: isset($entry['pop']) ? (float) $entry['pop'] : null,
                    condition: strtolower((string) ($entry['weather'][0]['main'] ?? 'unknown')),
                    meta: [
                        'watch_target_id' => $criteria['watch_target_id'] ?? null,
                        'precipitation_mm' => $entry['rain']['3h'] ?? $entry['snow']['3h'] ?? 0.0,
                        'wind_speed' => isset($entry['wind']['speed']) ? (float) $entry['wind']['speed'] : null,
                    ],
                );
            })
            ->values()
            ->all();
    }

    private function airport(string $locationCode): Airport
    {
        $airport = Airport::query()
            ->where('iata', $locationCode)
            ->orWhere('icao', $locationCode)
            ->first();

        if (! $airport) {
            throw new RuntimeException(sprintf('Airport [%s] could not be found for weather lookup.', $locationCode));
        }

        if ($airport->latitude === null || $airport->longitude === null) {
            throw new RuntimeException(sprintf('Airport [%s] is missing latitude/longitude for weather lookup.', $locationCode));
        }

        return $airport;
    }
}
