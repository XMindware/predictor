<?php

namespace App\Services\Providers\Normalizers;

use App\Models\Airport;
use App\Models\FlightEvent;
use App\Models\RawProviderPayload;
use App\Models\Route;
use App\Models\WatchTarget;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FlightPayloadNormalizer
{
    public function normalize(RawProviderPayload $payload): int
    {
        if ($payload->source_type !== 'flights') {
            throw new InvalidArgumentException('Raw payload source type must be flights.');
        }

        /** @var list<array<string, mixed>> $items */
        $items = $payload->payload['items'] ?? [];
        $watchTarget = $this->watchTarget($payload);
        $originAirport = $this->resolveAirport($watchTarget->originAirport, $watchTarget->originCity?->airports()->orderBy('id')->first());
        $destinationAirport = $this->resolveAirport($watchTarget->destinationAirport, $watchTarget->destinationCity?->airports()->orderBy('id')->first());

        if (! $originAirport) {
            return 0;
        }

        $route = $destinationAirport
            ? Route::query()
                ->where('origin_airport_id', $originAirport->id)
                ->where('destination_airport_id', $destinationAirport->id)
                ->first()
            : null;

        return DB::transaction(function () use ($payload, $items, $originAirport, $destinationAirport, $route): int {
            $payload->flightEvents()->delete();

            foreach ($items as $item) {
                FlightEvent::create([
                    'route_id' => $route?->id,
                    'origin_airport_id' => $originAirport->id,
                    'destination_airport_id' => $destinationAirport?->id,
                    'airline_code' => $item['carrier_code'] ?? null,
                    'event_time' => $item['departure_at'] ?? $payload->fetched_at,
                    'travel_date' => isset($item['departure_at']) ? substr((string) $item['departure_at'], 0, 10) : null,
                    'cancellation_rate' => Arr::get($item, 'meta.cancellation_rate'),
                    'delay_average_minutes' => Arr::get($item, 'meta.delay_average_minutes'),
                    'disruption_score' => $this->disruptionScore($item),
                    'summary' => $this->summary($item, $originAirport, $destinationAirport),
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
    private function disruptionScore(array $item): float
    {
        $cancellationRate = (float) Arr::get($item, 'meta.cancellation_rate', 0);
        $delayAverage = (float) Arr::get($item, 'meta.delay_average_minutes', 0);
        $stops = (int) ($item['stops'] ?? 0);

        return round(min(10, 1 + ($cancellationRate * 0.8) + ($delayAverage / 15) + ($stops * 1.5)), 2);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function summary(array $item, Airport $originAirport, ?Airport $destinationAirport): string
    {
        $destination = $destinationAirport?->iata ?? 'destination';
        $carrier = $item['carrier_code'] ?? 'carrier';
        $flightNumber = $item['flight_number'] ?? 'unknown';

        return sprintf(
            'Flight %s%s from %s to %s analyzed for disruption.',
            $carrier,
            $flightNumber,
            $originAirport->iata,
            $destination
        );
    }

    private function watchTarget(RawProviderPayload $payload): WatchTarget
    {
        $watchTargetId = $payload->payload['watch_target_id'] ?? null;

        if (! is_int($watchTargetId) && ! ctype_digit((string) $watchTargetId)) {
            throw new InvalidArgumentException('Flight payload is missing watch_target_id.');
        }

        return WatchTarget::query()
            ->with(['originCity.airports', 'originAirport', 'destinationCity.airports', 'destinationAirport'])
            ->findOrFail((int) $watchTargetId);
    }

    private function resolveAirport(?Airport $preferredAirport, ?Airport $fallbackAirport): ?Airport
    {
        return $preferredAirport ?? $fallbackAirport;
    }
}
