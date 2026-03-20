<?php

namespace Database\Seeders;

use App\Models\WeatherEvent;
use Database\Seeders\Concerns\SeedsDemoSourceData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class WeatherSourceSeeder extends Seeder
{
    use SeedsDemoSourceData;

    public function run(): void
    {
        $provider = $this->provider('openweather');
        $run = $this->seedRun($provider, 'weather');

        $forecasts = [
            ['origin' => 'CUN', 'condition' => 'RAIN', 'severity' => 6.8, 'temperature' => 29.1, 'precipitation_mm' => 11.4, 'wind_speed' => 18.0],
            ['origin' => 'MID', 'condition' => 'CLEAR', 'severity' => 2.2, 'temperature' => 32.0, 'precipitation_mm' => 0.0, 'wind_speed' => 8.0],
            ['origin' => 'MIA', 'condition' => 'THUNDERSTORM', 'severity' => 7.4, 'temperature' => 27.3, 'precipitation_mm' => 14.6, 'wind_speed' => 22.0],
            ['origin' => 'JFK', 'condition' => 'SNOW', 'severity' => 7.9, 'temperature' => 2.8, 'precipitation_mm' => 5.2, 'wind_speed' => 20.0],
            ['origin' => 'MEX', 'condition' => 'CLOUDY', 'severity' => 4.1, 'temperature' => 21.5, 'precipitation_mm' => 1.2, 'wind_speed' => 12.0],
        ];

        foreach ($forecasts as $index => $forecast) {
            $originAirport = $this->airport($forecast['origin']);
            $watchTarget = $this->cityWatchTarget($forecast['origin']);
            $eventTime = Carbon::now()->subHours($index + 1);
            $forecastFor = Carbon::now()->addHours(($index + 1) * 4);
            $externalReference = sprintf('seed:weather:%s:slot-%d', $forecast['origin'], $index + 1);

            $payload = $this->seedPayload(
                $provider,
                $run,
                'weather',
                $externalReference,
                [
                    'watch_target_id' => $watchTarget->id,
                    'items' => [[
                        'provider_slug' => $provider->slug,
                        'location_code' => $originAirport->iata,
                        'observed_at' => $eventTime->toIso8601String(),
                        'condition' => strtolower($forecast['condition']),
                        'temperature_celsius' => $forecast['temperature'],
                        'precipitation_probability' => min(1, $forecast['precipitation_mm'] / 20),
                        'meta' => [
                            'precipitation_mm' => $forecast['precipitation_mm'],
                            'wind_speed' => $forecast['wind_speed'],
                        ],
                    ]],
                ],
                $eventTime->toDateTimeString(),
            );

            WeatherEvent::query()->updateOrCreate(
                [
                    'raw_payload_id' => $payload->id,
                ],
                [
                    'city_id' => $originAirport->city_id,
                    'airport_id' => $originAirport->id,
                    'event_time' => $eventTime,
                    'forecast_for' => $forecastFor,
                    'severity_score' => $forecast['severity'],
                    'condition_code' => $forecast['condition'],
                    'summary' => sprintf('Seeded %s conditions for %s.', strtolower($forecast['condition']), $originAirport->iata),
                    'temperature' => $forecast['temperature'],
                    'precipitation_mm' => $forecast['precipitation_mm'],
                    'wind_speed' => $forecast['wind_speed'],
                    'source_provider_id' => $provider->id,
                ],
            );
        }
    }
}
