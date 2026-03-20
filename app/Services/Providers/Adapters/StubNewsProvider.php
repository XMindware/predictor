<?php

namespace App\Services\Providers\Adapters;

use App\Contracts\Providers\NewsProviderInterface;
use App\Data\Providers\NewsData;

class StubNewsProvider implements NewsProviderInterface
{
    public function fetchNews(array $criteria = []): array
    {
        $providerSlug = (string) ($criteria['provider_slug'] ?? 'stub-news');
        $headlineContext = (string) ($criteria['headline_context'] ?? 'route updates');

        return [
            new NewsData(
                providerSlug: $providerSlug,
                title: sprintf('Operational update for %s', $headlineContext),
                externalReference: sprintf('news:%s:%s', $providerSlug, str_replace(' ', '-', strtolower($headlineContext))),
                summary: 'Stub article used to exercise the ingestion pipeline.',
                url: 'https://example.com/provider-news',
                publishedAt: now()->toIso8601String(),
                topics: ['operations', 'monitoring'],
                meta: [
                    'watch_target_id' => $criteria['watch_target_id'] ?? null,
                    'date_window_days' => $criteria['date_window_days'] ?? null,
                ],
            ),
        ];
    }
}
