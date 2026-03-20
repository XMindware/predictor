<?php

namespace App\Services\Providers\Adapters;

use App\Contracts\Providers\NewsProviderInterface;
use App\Data\Providers\NewsData;
use Illuminate\Support\Carbon;

class NewsApiProvider extends ConfiguredHttpProvider implements NewsProviderInterface
{
    public function fetchNews(array $criteria = []): array
    {
        $headlineContext = trim((string) ($criteria['headline_context'] ?? 'travel disruption'));
        $windowDays = min(30, max(1, (int) ($criteria['date_window_days'] ?? 7)));

        $response = $this->client([
            'X-Api-Key' => $this->requiredCredential('api_key'),
        ])
            ->get('/v2/everything', [
                'q' => $headlineContext,
                'from' => Carbon::now()->subDays($windowDays)->toIso8601String(),
                'sortBy' => $this->optionalConfig('sort_by', 'publishedAt'),
                'language' => $this->optionalConfig('language', 'en'),
                'pageSize' => $this->integerConfig('page_size', 10),
                'searchIn' => 'title,description,content',
            ])
            ->throw()
            ->json();

        return collect($response['articles'] ?? [])
            ->map(function (array $article) use ($criteria, $headlineContext): NewsData {
                $title = trim((string) ($article['title'] ?? 'Untitled article'));
                $url = (string) ($article['url'] ?? '');

                return new NewsData(
                    providerSlug: $this->provider->slug,
                    title: $title,
                    externalReference: sprintf('news:%s:%s', $this->provider->slug, sha1($url !== '' ? $url : $title)),
                    summary: (string) ($article['description'] ?? $article['content'] ?? ''),
                    url: $url,
                    publishedAt: $article['publishedAt'] ?? null,
                    topics: $this->topics($article, $headlineContext),
                    meta: [
                        'watch_target_id' => $criteria['watch_target_id'] ?? null,
                        'source_name' => $article['source']['name'] ?? null,
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

        if (str_contains($haystack, 'storm') || str_contains($haystack, 'weather') || str_contains($haystack, 'rain') || str_contains($haystack, 'snow')) {
            $topics[] = 'weather';
        }

        if (str_contains($haystack, 'airport') || str_contains($haystack, 'delay') || str_contains($haystack, 'disruption') || str_contains($haystack, 'cancel')) {
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
