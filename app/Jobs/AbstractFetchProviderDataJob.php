<?php

namespace App\Jobs;

use App\Models\IngestionRun;
use App\Models\Provider;
use App\Models\RawProviderPayload;
use App\Models\WatchTarget;
use App\Services\Providers\ProviderAdapterRegistry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Throwable;

abstract class AbstractFetchProviderDataJob implements ShouldQueue
{
    use Queueable;

    public function handle(ProviderAdapterRegistry $registry): void
    {
        $watchTargets = $this->watchTargets();

        if ($watchTargets->isEmpty()) {
            return;
        }

        Provider::query()
            ->where('service', $this->sourceType())
            ->where('active', true)
            ->get()
            ->each(function (Provider $provider) use ($registry, $watchTargets): void {
                $this->ingestProvider($registry, $provider, $watchTargets);
            });
    }

    abstract protected function sourceType(): string;

    /**
     * @return array<string, mixed>
     */
    abstract protected function buildCriteria(Provider $provider, WatchTarget $watchTarget): array;

    /**
     * @param  array<string, mixed>  $criteria
     * @return array<int, object|array<string, mixed>>
     */
    abstract protected function fetchItems(ProviderAdapterRegistry $registry, Provider $provider, array $criteria): array;

    abstract protected function dispatchNormalization(RawProviderPayload $payload): void;

    /**
     * @return Collection<int, WatchTarget>
     */
    protected function watchTargets(): Collection
    {
        return WatchTarget::query()
            ->where('enabled', true)
            ->with(['originCity', 'originAirport', 'destinationCity', 'destinationAirport'])
            ->orderByDesc('monitoring_priority')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, WatchTarget>  $watchTargets
     */
    private function ingestProvider(ProviderAdapterRegistry $registry, Provider $provider, Collection $watchTargets): void
    {
        $ingestionRun = $provider->ingestionRuns()->create([
            'source_type' => $this->sourceType(),
            'status' => 'running',
            'started_at' => now(),
            'request_meta' => [
                'watch_target_ids' => $watchTargets->pluck('id')->all(),
                'watch_target_count' => $watchTargets->count(),
                'provider_slug' => $provider->slug,
            ],
        ]);

        $payloadCount = 0;

        try {
            foreach ($watchTargets as $watchTarget) {
                $criteria = $this->buildCriteria($provider, $watchTarget);
                $items = $this->fetchItems($registry, $provider, $criteria);

                if ($items === []) {
                    continue;
                }

                $payload = $provider->rawPayloads()->create([
                    'source_type' => $this->sourceType(),
                    'external_reference' => $this->externalReference($items),
                    'payload' => [
                        'watch_target_id' => $watchTarget->id,
                        'criteria' => $criteria,
                        'items' => $this->normalizeItems($items),
                    ],
                    'fetched_at' => now(),
                    'ingestion_run_id' => $ingestionRun->id,
                ]);

                $payloadCount++;

                $this->dispatchNormalization($payload);
            }

            $ingestionRun->update([
                'status' => 'completed',
                'finished_at' => now(),
                'response_meta' => [
                    'payload_count' => $payloadCount,
                    'provider_slug' => $provider->slug,
                ],
            ]);
        } catch (Throwable $exception) {
            $ingestionRun->update([
                'status' => 'failed',
                'finished_at' => now(),
                'response_meta' => [
                    'payload_count' => $payloadCount,
                    'provider_slug' => $provider->slug,
                ],
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * @param  array<int, object|array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function normalizeItems(array $items): array
    {
        return collect($items)
            ->map(function (object|array $item): array {
                if (is_array($item)) {
                    return $item;
                }

                if (method_exists($item, 'toArray')) {
                    /** @var array<string, mixed> $normalized */
                    $normalized = $item->toArray();

                    return $normalized;
                }

                return get_object_vars($item);
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, object|array<string, mixed>>  $items
     */
    private function externalReference(array $items): ?string
    {
        $first = $items[0] ?? null;

        if (is_array($first)) {
            return $first['external_reference'] ?? null;
        }

        return property_exists($first, 'externalReference') ? $first->externalReference : null;
    }
}
