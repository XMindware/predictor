<?php

namespace App\Services\Providers\Adapters;

use App\Contracts\Providers\NewsProviderInterface;
use App\Data\Providers\NewsData;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NewsApiProvider extends ConfiguredHttpProvider implements NewsProviderInterface
{
    /**
     * Cache key used to track an active rate-limit window.
     * Other parts of the codebase (e.g. FetchNewsDataJob) can read this key
     * to decide whether to skip NewsAPI and route straight to RSS.
     */
    public const RATE_LIMIT_CACHE_KEY = 'provider:newsapi:rate_limited';

    /**
     * How long (seconds) to stay in rate-limited mode when no Retry-After
     * header is provided by the API.  NewsAPI developer accounts reset every
     * 12 hours, so we default to that.
     */
    private const DEFAULT_BACKOFF_SECONDS = 12 * 3600;

    public function fetchNews(array $criteria = []): array
    {
        $this->resetExchangeLog();

        // ── Rate-limit fast-exit ──────────────────────────────────────────────
        if (Cache::has(self::RATE_LIMIT_CACHE_KEY)) {
            Log::info('[NewsApiProvider] Rate-limit window active — skipping, RSS fallback will handle monitored cities.');

            return [];
        }

        $headlineContext = trim((string) ($criteria['headline_context'] ?? 'travel disruption'));
        $windowDays = min(30, max(1, (int) ($criteria['date_window_days'] ?? 7)));
        $headers = [
            'X-Api-Key' => $this->requiredCredential('api_key'),
        ];
        $query = [
            'q'        => $headlineContext,
            'from'     => Carbon::now()->subDays($windowDays)->toIso8601String(),
            'sortBy'   => $this->optionalConfig('sort_by', 'publishedAt'),
            'language' => $this->optionalConfig('language', 'en'),
            'pageSize' => $this->integerConfig('page_size', 10),
            'searchIn' => 'title,description,content',
        ];

        $response = $this->client($headers)->get('/v2/everything', $query);
        $this->recordExchange('GET', '/v2/everything', $query, $headers, $response);

        // ── Rate-limit detection ──────────────────────────────────────────────
        // NewsAPI returns 426 (Upgrade Required) or 429 for rate limits, but
        // sometimes a 200 with {"status":"error","code":"rateLimited"} body.
        $isRateLimited = in_array($response->status(), [426, 429], true)
            || (string) ($response->json('code') ?? '') === 'rateLimited'
            || str_contains(strtolower((string) ($response->json('message') ?? '')), 'too many requests');

        if ($isRateLimited) {
            $retryAfter = (int) ($response->header('Retry-After') ?: self::DEFAULT_BACKOFF_SECONDS);
            $retryAfter = max(60, $retryAfter); // always wait at least 1 minute

            Cache::put(self::RATE_LIMIT_CACHE_KEY, now()->addSeconds($retryAfter)->toIso8601String(), $retryAfter);

            Log::warning('[NewsApiProvider] Rate limited. Backing off for ' . round($retryAfter / 3600, 1) . 'h. RSS fallback will handle monitored cities.');

            return [];
        }

        // ── Normal error handling ─────────────────────────────────────────────
        $body = $response->throw()->json();

        return collect($body['articles'] ?? [])
            ->map(function (array $article) use ($criteria, $headlineContext): NewsData {
                $title = trim((string) ($article['title'] ?? 'Untitled article'));
                $url   = (string) ($article['url'] ?? '');

                return new NewsData(
                    providerSlug:      $this->provider->slug,
                    title:             $title,
                    externalReference: sprintf('news:%s:%s', $this->provider->slug, sha1($url !== '' ? $url : $title)),
                    summary:           (string) ($article['description'] ?? $article['content'] ?? ''),
                    url:               $url,
                    publishedAt:       $article['publishedAt'] ?? null,
                    topics:            $this->topics($article, $headlineContext),
                    meta: [
                        'watch_target_id' => $criteria['watch_target_id'] ?? null,
                        'source_name'     => $article['source']['name'] ?? null,
                    ],
                );
            })
            ->filter(fn (NewsData $item): bool => $item->title !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $article
     * @return list<string>
     */
    private function topics(array $article, string $headlineContext): array
    {
        $haystack = strtolower(implode(' ', array_filter([
            $headlineContext,
            (string) ($article['title'] ?? ''),
            (string) ($article['description'] ?? ''),
        ])));

        $topics = [];

        if (str_contains($haystack, 'storm') || str_contains($haystack, 'weather')
            || str_contains($haystack, 'rain') || str_contains($haystack, 'snow')) {
            $topics[] = 'weather';
        }

        if (str_contains($haystack, 'airport') || str_contains($haystack, 'delay')
            || str_contains($haystack, 'disruption') || str_contains($haystack, 'cancel')) {
            $topics[] = 'operations';
        }

        if (str_contains($haystack, 'airline') || str_contains($haystack, 'carrier')) {
            $topics[] = 'airline';
        }

        if ($topics === []) {
            $topics[] = 'monitoring';
        }

        $topics[] = 'monitoring';

        return array_values(array_unique($topics));
    }
}
