<?php

namespace App\Jobs;

use App\Models\Provider;
use App\Models\RawProviderPayload;
use App\Models\WatchTarget;
use App\Services\Providers\ProviderAdapterRegistry;

class FetchFlightDataJob extends AbstractFetchProviderDataJob
{
    protected function sourceType(): string
    {
        return 'flights';
    }

    protected function buildCriteria(Provider $provider, WatchTarget $watchTarget): array
    {
        return [
            'provider_slug' => $provider->slug,
            'watch_target_id' => $watchTarget->id,
            'origin_code' => $watchTarget->originAirport?->iata ?? $watchTarget->originCity->name,
            'destination_code' => $watchTarget->destinationAirport?->iata ?? $watchTarget->destinationCity?->name ?? 'ANY',
            'date_window_days' => $watchTarget->date_window_days,
        ];
    }

    protected function fetchItems(ProviderAdapterRegistry $registry, Provider $provider, array $criteria): array
    {
        return $registry->flights($provider)->searchFlights($criteria);
    }

    protected function dispatchNormalization(RawProviderPayload $payload): void
    {
        NormalizeFlightPayloadJob::dispatch($payload->id);
    }
}
