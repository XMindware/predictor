<?php

namespace App\Services\Providers\Adapters;

use App\Contracts\Providers\NewsProviderInterface;
use App\Data\Providers\NewsData;
use App\Models\RssNewsSource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * RSS / Atom news provider.
 *
 * Feed URLs are loaded from the `rss_news_sources` database table instead of
 * a JSON blob in `provider_configs`.  This gives super-admins first-class
 * CRUD control over every individual feed without touching code or config.
 *
 * Feed selection per run:
 *  - All active rows where iata IS NULL  (global defaults)
 *  - All active rows where iata = origin IATA
 *  - All active rows where iata = destination IATA
 *
 * Provider config keys still respected (stored in provider_configs):
 *  - timeout_seconds        HTTP timeout per feed (default 12)
 *  - max_articles_per_feed  cap before relevance filtering (default 20)
 *  - min_relevance_hits     keyword hits needed to pass filter (default 1)
 *  - relevance_keywords     comma-separated; falls back to DEFAULT_KEYWORDS
 */
class RssNewsProvider extends ConfiguredHttpProvider implements NewsProviderInterface
{
    // ── Relevance keywords ────────────────────────────────────────────────────

    /** @var list<string> */
    private const DEFAULT_KEYWORDS = [
        // English
        'airport', 'flight', 'airline', 'delay', 'cancel', 'diverted',
        'diversion', 'disruption', 'closure', 'evacuation', 'storm',
        'hurricane', 'cyclone', 'tornado', 'flood', 'fog', 'snow', 'ice',
        'wind', 'lightning', 'turbulence', 'grounded', 'runway',
        // Spanish
        'aeropuerto', 'vuelo', 'aerolinea', 'aerolineas', 'retraso',
        'cancelacion', 'cancelado', 'tormenta', 'huracan', 'inundacion',
        'niebla', 'nieve', 'viento', 'rayo', 'pista', 'desvio',
    ];

    // ── Main interface method ─────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $criteria
     * @return list<NewsData>
     */
    public function fetchNews(array $criteria = []): array
    {
        $this->resetExchangeLog();

        $originIata      = strtoupper((string) ($criteria['origin_iata']      ?? ''));
        $destinationIata = strtoupper((string) ($criteria['destination_iata'] ?? ''));
        $windowDays      = max(1, (int) ($criteria['date_window_days'] ?? 7));
        $cutoff          = Carbon::now()->subDays($windowDays);

        // Load URLs from DB (global + origin + destination feeds)
        $iatas    = array_filter([$originIata, $destinationIata]);
        $feedUrls = RssNewsSource::urlsFor($iatas);

        if ($feedUrls === []) {
            Log::info('[RssNewsProvider] No active RSS sources found for IATAs: ' . implode(', ', $iatas ?: ['(none)']));

            return [];
        }

        $keywords   = $this->resolveKeywords();
        $maxPerFeed = $this->integerConfig('max_articles_per_feed', 20);
        $minHits    = $this->integerConfig('min_relevance_hits', 1);

        $items = [];

        foreach ($feedUrls as $feedUrl) {
            try {
                $feedItems = $this->fetchFeed($feedUrl, $maxPerFeed, $cutoff, $keywords, $minHits, $criteria);
                $items     = array_merge($items, $feedItems);
            } catch (\Throwable $e) {
                Log::warning('[RssNewsProvider] Feed fetch failed', [
                    'url'   => $feedUrl,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $items;
    }

    // ── Feed fetching & parsing ───────────────────────────────────────────────

    /**
     * @param  list<string>          $keywords
     * @param  array<string, mixed>  $criteria
     * @return list<NewsData>
     */
    private function fetchFeed(
        string $feedUrl,
        int $maxPerFeed,
        Carbon $cutoff,
        array $keywords,
        int $minHits,
        array $criteria,
    ): array {
        $response = $this->client([
            'Accept'     => 'application/rss+xml, application/atom+xml, application/xml, text/xml, */*',
            'User-Agent' => 'Predictor/1.0 (RSS ingestion bot)',
        ])->get($feedUrl);

        $this->recordExchange('GET', $feedUrl, [], [], $response);

        if (! $response->successful()) {
            return [];
        }

        $previous = libxml_use_internal_errors(true);
        $xml      = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOERROR);
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            Log::warning('[RssNewsProvider] Failed to parse XML', ['url' => $feedUrl]);

            return [];
        }

        $entries = $this->extractEntries($xml);
        $parsed  = [];
        $count   = 0;

        foreach ($entries as $entry) {
            if ($count >= $maxPerFeed) {
                break;
            }

            $item = $this->toNewsData($entry, $feedUrl, $criteria);

            if ($item === null) {
                continue;
            }

            if ($item->publishedAt !== null && Carbon::parse($item->publishedAt)->lt($cutoff)) {
                continue;
            }

            if (! $this->isRelevant($item, $keywords, $minHits)) {
                continue;
            }

            $parsed[] = $item;
            $count++;
        }

        return $parsed;
    }

    /**
     * Extract items from RSS 2.0, RSS 1.0 (RDF), or Atom 1.0 feed.
     *
     * @return iterable<\SimpleXMLElement>
     */
    private function extractEntries(\SimpleXMLElement $xml): iterable
    {
        if (isset($xml->channel->item)) {
            return $xml->channel->item;
        }

        if (isset($xml->item)) {
            return $xml->item;
        }

        $xml->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
        $atomEntries = $xml->xpath('//atom:entry') ?: [];

        if (count($atomEntries) > 0) {
            return $atomEntries;
        }

        return isset($xml->entry) ? $xml->entry : [];
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    private function toNewsData(\SimpleXMLElement $entry, string $feedUrl, array $criteria): ?NewsData
    {
        $title = $this->textOf($entry, ['title']);

        if ($title === '') {
            return null;
        }

        $url = $this->textOf($entry, ['link', 'url', 'id']);

        if ($url === '' && isset($entry->link['href'])) {
            $url = (string) $entry->link['href'];
        }

        $summary = trim(strip_tags($this->textOf($entry, ['description', 'summary', 'content', 'content:encoded'])));

        $rawDate     = $this->textOf($entry, ['pubDate', 'published', 'updated', 'dc:date']);
        $publishedAt = null;

        if ($rawDate !== '') {
            try {
                $publishedAt = Carbon::parse($rawDate)->toIso8601String();
            } catch (\Throwable) {
            }
        }

        return new NewsData(
            providerSlug:      $this->provider->slug,
            title:             $title,
            externalReference: sprintf('rss:%s:%s', $this->provider->slug, sha1($url !== '' ? $url : $title)),
            summary:           $summary,
            url:               $url,
            publishedAt:       $publishedAt,
            topics:            $this->extractTopics($title, $summary),
            meta: [
                'watch_target_id' => $criteria['watch_target_id'] ?? null,
                'source_name'     => parse_url($feedUrl, PHP_URL_HOST) ?? $feedUrl,
                'feed_url'        => $feedUrl,
            ],
        );
    }

    // ── Topic classification ──────────────────────────────────────────────────

    /** @return list<string> */
    private function extractTopics(string $title, string $summary): array
    {
        $hay    = strtolower($title . ' ' . $summary);
        $topics = [];

        if (preg_match('/storm|weather|rain|snow|ice|fog|huracan|tormenta|inundacion|niebla|nieve/', $hay)) {
            $topics[] = 'weather';
        }

        if (preg_match('/airport|delay|disruption|cancel|closure|runway|aeropuerto|retraso|cancelad|pista/', $hay)) {
            $topics[] = 'operations';
        }

        if (preg_match('/airline|carrier|aerolinea|aerolineas/', $hay)) {
            $topics[] = 'airline';
        }

        if ($topics === []) {
            $topics[] = 'monitoring';
        }

        $topics[] = 'monitoring';

        return array_values(array_unique($topics));
    }

    // ── Relevance filtering ───────────────────────────────────────────────────

    /** @param list<string> $keywords */
    private function isRelevant(NewsData $item, array $keywords, int $minHits): bool
    {
        if ($minHits <= 0) {
            return true;
        }

        $haystack = strtolower($item->title . ' ' . $item->summary);
        $hits     = 0;

        foreach ($keywords as $keyword) {
            if (str_contains($haystack, $keyword)) {
                if (++$hits >= $minHits) {
                    return true;
                }
            }
        }

        return false;
    }

    // ── Config helpers ────────────────────────────────────────────────────────

    /** @return list<string> */
    private function resolveKeywords(): array
    {
        $raw = $this->optionalConfig('relevance_keywords', '');

        if ($raw !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $raw))));
        }

        return self::DEFAULT_KEYWORDS;
    }

    // ── SimpleXML helpers ─────────────────────────────────────────────────────

    /**
     * Try candidate element names and return the first non-empty text value.
     *
     * @param  list<string>  $candidates
     */
    private function textOf(\SimpleXMLElement $entry, array $candidates): string
    {
        foreach ($candidates as $name) {
            if (str_contains($name, ':')) {
                [$ns, $local] = explode(':', $name, 2);

                try {
                    $val = trim((string) ($entry->children($ns, true)->$local ?? ''));
                    if ($val !== '') {
                        return $val;
                    }
                } catch (\Throwable) {
                }

                continue;
            }

            $val = trim((string) ($entry->$name ?? ''));
            if ($val !== '') {
                return $val;
            }
        }

        return '';
    }
}
