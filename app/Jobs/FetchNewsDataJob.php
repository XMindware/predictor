<?php

namespace App\Jobs;

use App\Models\MonitoredDestination;
use App\Models\Provider;
use App\Models\RawProviderPayload;
use App\Models\WatchTarget;
use App\Services\Providers\Adapters\NewsApiProvider;
use App\Services\Providers\ProviderAdapterRegistry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FetchNewsDataJob extends AbstractFetchProviderDataJob
{
    protected function sourceType(): string
    {
        return 'news';
    }

    // ── Override handle() to manage NewsAPI → RSS fallback ───────────────────

    /**
     * Custom handle replaces the abstract base behaviour so we can:
     *
     *  1. Run NewsAPI (if active and not rate-limited).
     *  2. Detect whether a rate-limit window is now active (either pre-existing
     *     or freshly triggered by this run).
     *  3. When rate-limited, run the RSS provider only for watch targets whose
     *     *destination* airport is in the monitored-destinations list — which
     *     is exactly where we have city-specific RSS feeds configured.
     *  4. When NOT rate-limited, run RSS for all watch targets as usual
     *     (broad supplemental coverage alongside NewsAPI).
     */
    public function handle(ProviderAdapterRegistry $registry): void
    {
        $watchTargets = $this->watchTargets();

        if ($watchTargets->isEmpty()) {
            return;
        }

        /** @var Collection<int, Provider> $newsProviders */
        $newsProviders = Provider::query()
            ->where('service', 'news')
            ->where('active', true)
            ->get();

        $newsApiProvider = $newsProviders->firstWhere('slug', 'newsapi');
        $rssProvider     = $newsProviders->firstWhere('slug', 'rss-news');

        // ── Step 1: NewsAPI ───────────────────────────────────────────────────
        $rateLimitedBefore = Cache::has(NewsApiProvider::RATE_LIMIT_CACHE_KEY);

        if ($newsApiProvider && ! $rateLimitedBefore) {
            $this->ingestProvider($registry, $newsApiProvider, $watchTargets);
        }

        // Re-check: the NewsAPI run may have just triggered a rate-limit.
        $isRateLimited = Cache::has(NewsApiProvider::RATE_LIMIT_CACHE_KEY);

        // ── Step 2: RSS ───────────────────────────────────────────────────────
        if (! $rssProvider) {
            if ($isRateLimited) {
                Log::warning('[FetchNewsDataJob] NewsAPI is rate-limited but no active rss-news provider found. No news will be ingested this cycle.');
            }

            return;
        }

        if ($isRateLimited) {
            // Fallback mode: restrict RSS to watch targets whose destination is
            // in the monitored-destinations table (those are the cities we have
            // city-specific RSS feeds for).
            $monitoredIatas  = MonitoredDestination::activeIatas();
            $rssWatchTargets = $watchTargets->filter(
                fn (WatchTarget $wt): bool =>
                    $wt->destinationAirport !== null
                    && in_array($wt->destinationAirport->iata, $monitoredIatas, true)
            );

            if ($rssWatchTargets->isEmpty()) {
                Log::warning('[FetchNewsDataJob] NewsAPI rate-limited but no monitored-destination watch targets found for RSS fallback.');

                return;
            }

            Log::info(sprintf(
                '[FetchNewsDataJob] NewsAPI rate-limited — running RSS fallback for %d monitored-destination watch target(s): %s',
                $rssWatchTargets->count(),
                $rssWatchTargets->pluck('destinationAirport.iata')->unique()->implode(', '),
            ));

            $this->ingestProvider($registry, $rssProvider, $rssWatchTargets);
        } else {
            // Normal operation: RSS runs for all watch targets (broad coverage).
            $this->ingestProvider($registry, $rssProvider, $watchTargets);
        }
    }

    // ── AbstractFetchProviderDataJob contract ─────────────────────────────────

    protected function buildCriteria(Provider $provider, WatchTarget $watchTarget): array
    {
        $originIata      = $watchTarget->originAirport?->iata ?? '';
        $destinationIata = $watchTarget->destinationAirport?->iata ?? '';

        $headlineContext = implode(' to ', array_filter([
            $originIata ?: ($watchTarget->originCity->name ?? ''),
            $destinationIata ?: ($watchTarget->destinationCity?->name ?? ''),
        ]));

        return [
            'provider_slug'    => $provider->slug,
            'watch_target_id'  => $watchTarget->id,
            // Used by NewsApiProvider for keyword search
            'headline_context' => $headlineContext !== '' ? $headlineContext : ($watchTarget->originCity->name ?? ''),
            // Used by RssNewsProvider for city-targeted feed lookup
            'origin_iata'      => $originIata,
            'destination_iata' => $destinationIata,
            'origin_city'      => $watchTarget->originCity->name ?? '',
            'destination_city' => $watchTarget->destinationCity?->name ?? '',
            'date_window_days' => $watchTarget->date_window_days,
        ];
    }

    protected function fetchItems(ProviderAdapterRegistry $registry, Provider $provider, array $criteria): array
    {
        $adapter = $registry->news($provider);
        $items   = $adapter->fetchNews($criteria);
        $this->captureExchangeLog($adapter);

        return $items;
    }

    protected function dispatchNormalization(RawProviderPayload $payload): void
    {
        NormalizeNewsPayloadJob::dispatch($payload->id);
    }
}
