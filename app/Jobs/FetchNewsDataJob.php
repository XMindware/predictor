<?php

namespace App\Jobs;

use App\Models\Provider;
use App\Models\RawProviderPayload;
use App\Models\WatchTarget;
use App\Services\Providers\ProviderAdapterRegistry;

class FetchNewsDataJob extends AbstractFetchProviderDataJob
{
    protected function sourceType(): string
    {
        return 'news';
    }

    protected function buildCriteria(Provider $provider, WatchTarget $watchTarget): array
    {
        $headlineContext = implode(' to ', array_filter([
            $watchTarget->originAirport?->iata ?? $watchTarget->originCity->name,
            $watchTarget->destinationAirport?->iata ?? $watchTarget->destinationCity?->name,
        ]));

        return [
            'provider_slug' => $provider->slug,
            'watch_target_id' => $watchTarget->id,
            'headline_context' => $headlineContext !== '' ? $headlineContext : $watchTarget->originCity->name,
            'date_window_days' => $watchTarget->date_window_days,
        ];
    }

    protected function fetchItems(ProviderAdapterRegistry $registry, Provider $provider, array $criteria): array
    {
        return $registry->news($provider)->fetchNews($criteria);
    }

    protected function dispatchNormalization(RawProviderPayload $payload): void
    {
        NormalizeNewsPayloadJob::dispatch($payload->id);
    }
}
