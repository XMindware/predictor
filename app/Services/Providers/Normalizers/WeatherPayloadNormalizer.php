<?php

namespace App\Services\Providers\Normalizers;

use App\Models\RawProviderPayload;
use App\Models\WeatherEvent;
use App\Models\WatchTarget;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WeatherPayloadNormalizer
{
    public function normalize(RawProviderPayload $payload): int
    {
        if ($payload->source_type !== 'weather') {
            throw new InvalidArgumentException('Raw payload source type must be weather.');
        }

        /** @var list<array<string, mixed>> $items */
        $items = $payload->payload['items'] ?? [];
        $watchTarget = $this->watchTarget($payload);

        return DB::transaction(function () use ($payload, $watchTarget, $items): int {
            $payload->weatherEvents()->delete();

            foreach ($items as $item) {
                WeatherEvent::create([
                    'city_id' => $watchTarget->origin_city_id,
                    'airport_id' => $watchTarget->origin_airport_id,
                    'event_time' => $item['observed_at'] ?? $payload->fetched_at,
                    'forecast_for' => $item['observed_at'] ?? $payload->fetched_at,
                    'severity_score' => $this->severityScore($item),
                    'condition_code' => strtoupper((string) ($item['condition'] ?? 'UNKNOWN')),
                    'summary' => $this->summary($watchTarget, $item),
                    'temperature' => $item['temperature_celsius'] ?? null,
                    'precipitation_mm' => Arr::get($item, 'meta.precipitation_mm'),
                    'wind_speed' => Arr::get($item, 'meta.wind_speed'),
                    'source_provider_id' => $payload->provider_id,
                    'raw_payload_id' => $payload->id,
                ]);
            }

            $payload->forceFill([
                'normalized_at' => now(),
            ])->save();

            return count($items);
        });
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function severityScore(array $item): float
    {
        $condition = strtolower((string) ($item['condition'] ?? 'unknown'));
        $conditionWeight = match ($condition) {
            'storm', 'hail', 'snow' => 7.0,
            'rain', 'thunderstorm' => 6.0,
            'cloudy', 'fog' => 4.0,
            'clear' => 2.0,
            default => 3.0,
        };

        $precipitationProbability = (float) ($item['precipitation_probability'] ?? 0);
        $windSpeed = (float) Arr::get($item, 'meta.wind_speed', 0);

        return round(min(10, $conditionWeight + ($precipitationProbability * 4) + ($windSpeed / 25)), 2);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function summary(WatchTarget $watchTarget, array $item): string
    {
        $scope = $watchTarget->originAirport?->iata ?? $watchTarget->originCity->name;
        $condition = ucfirst((string) ($item['condition'] ?? 'unknown'));

        return sprintf('%s conditions expected for %s.', $condition, $scope);
    }

    private function watchTarget(RawProviderPayload $payload): WatchTarget
    {
        $watchTargetId = $payload->payload['watch_target_id'] ?? null;

        if (! is_int($watchTargetId) && ! ctype_digit((string) $watchTargetId)) {
            throw new InvalidArgumentException('Weather payload is missing watch_target_id.');
        }

        return WatchTarget::query()
            ->with(['originCity', 'originAirport'])
            ->findOrFail((int) $watchTargetId);
    }
}
