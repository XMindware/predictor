<?php

namespace Database\Seeders\Concerns;

use App\Models\Airport;
use App\Models\IngestionRun;
use App\Models\Provider;
use App\Models\RawProviderPayload;
use App\Models\Route;
use App\Models\WatchTarget;
use Illuminate\Support\Collection;
use RuntimeException;

trait SeedsDemoSourceData
{
    protected function baseAirportIata(): string
    {
        return strtoupper((string) config('operations.base_airport_iata', 'CUN'));
    }

    protected function provider(string $slug): Provider
    {
        return Provider::query()
            ->where('slug', $slug)
            ->firstOrFail();
    }

    protected function airport(string $iata): Airport
    {
        $airport = Airport::query()
            ->with('city')
            ->where('iata', $iata)
            ->first();

        if ($airport) {
            return $airport;
        }

        throw new RuntimeException(sprintf('Airport "%s" is not available in the seeded geography.', $iata));
    }

    protected function baseAirport(): Airport
    {
        return $this->airport($this->baseAirportIata());
    }

    /**
     * @return Collection<int, Airport>
     */
    protected function nonBaseAirports(): Collection
    {
        $baseAirport = $this->baseAirport();

        return Airport::query()
            ->with('city')
            ->where('id', '!=', $baseAirport->id)
            ->orderBy('iata')
            ->get();
    }

    protected function route(string $originIata, string $destinationIata, ?string $notes = null): Route
    {
        $originAirport = $this->airport($originIata);
        $destinationAirport = $this->airport($destinationIata);

        return Route::query()->updateOrCreate(
            [
                'origin_airport_id' => $originAirport->id,
                'destination_airport_id' => $destinationAirport->id,
            ],
            [
                'active' => true,
                'notes' => $notes,
            ],
        );
    }

    protected function cityWatchTarget(string $originIata, int $priority = 6, int $dateWindowDays = 5): WatchTarget
    {
        $originAirport = $this->airport($originIata);

        return WatchTarget::query()->updateOrCreate(
            [
                'origin_city_id' => $originAirport->city_id,
                'origin_airport_id' => $originAirport->id,
                'destination_city_id' => null,
                'destination_airport_id' => null,
            ],
            [
                'enabled' => true,
                'monitoring_priority' => $priority,
                'date_window_days' => $dateWindowDays,
            ],
        );
    }

    protected function routeWatchTarget(Route $route, int $priority = 8, int $dateWindowDays = 10): WatchTarget
    {
        $route->loadMissing(['originAirport.city', 'destinationAirport.city']);

        return WatchTarget::query()->updateOrCreate(
            [
                'origin_city_id' => $route->originAirport?->city_id,
                'origin_airport_id' => $route->origin_airport_id,
                'destination_city_id' => $route->destinationAirport?->city_id,
                'destination_airport_id' => $route->destination_airport_id,
            ],
            [
                'enabled' => true,
                'monitoring_priority' => $priority,
                'date_window_days' => $dateWindowDays,
            ],
        );
    }

    protected function seedRun(Provider $provider, string $sourceType): IngestionRun
    {
        return IngestionRun::query()->updateOrCreate(
            [
                'provider_id' => $provider->id,
                'source_type' => $sourceType,
                'error_message' => "seed:{$sourceType}",
            ],
            [
                'status' => 'completed',
                'started_at' => now()->subMinutes(30),
                'finished_at' => now()->subMinutes(5),
                'request_meta' => [
                    'seeded' => true,
                    'source' => $sourceType,
                ],
                'response_meta' => [
                    'seeded' => true,
                ],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function seedPayload(
        Provider $provider,
        IngestionRun $run,
        string $sourceType,
        string $externalReference,
        array $payload,
        string $fetchedAt,
    ): RawProviderPayload {
        return RawProviderPayload::query()->updateOrCreate(
            [
                'provider_id' => $provider->id,
                'source_type' => $sourceType,
                'external_reference' => $externalReference,
            ],
            [
                'payload' => $payload,
                'fetched_at' => $fetchedAt,
                'normalized_at' => now(),
                'ingestion_run_id' => $run->id,
            ],
        );
    }
}
