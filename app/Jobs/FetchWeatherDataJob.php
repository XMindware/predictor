<?php

namespace App\Jobs;

use App\Models\Provider;
use App\Models\RawProviderPayload;
use App\Models\WatchTarget;
use App\Services\Providers\ProviderAdapterRegistry;

class FetchWeatherDataJob extends AbstractFetchProviderDataJob
{
    protected function sourceType(): string
    {
        return 'weather';
    }

    protected function buildCriteria(Provider $provider, WatchTarget $watchTarget): array
    {
        return [
            'provider_slug' => $provider->slug,
            'watch_target_id' => $watchTarget->id,
            'location_code' => $watchTarget->originAirport?->iata ?? $watchTarget->originCity->name,
            'timezone' => $watchTarget->originAirport?->timezone ?? 'UTC',
            'date_window_days' => $watchTarget->date_window_days,
        ];
    }

    protected function fetchItems(ProviderAdapterRegistry $registry, Provider $provider, array $criteria): array
    {
        return $registry->weather($provider)->fetchWeather($criteria);
    }

    protected function dispatchNormalization(RawProviderPayload $payload): void
    {
        NormalizeWeatherPayloadJob::dispatch($payload->id);
    }
}
