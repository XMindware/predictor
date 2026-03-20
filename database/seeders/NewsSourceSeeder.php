<?php

namespace Database\Seeders;

use App\Models\NewsEvent;
use Database\Seeders\Concerns\SeedsDemoSourceData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class NewsSourceSeeder extends Seeder
{
    use SeedsDemoSourceData;

    public function run(): void
    {
        $provider = $this->provider('newsapi');
        $run = $this->seedRun($provider, 'news');

        $articles = [
            ['origin' => 'CUN', 'title' => 'Heavy rain watch issued for Cancun arrivals', 'category' => 'weather', 'severity' => 6.7, 'relevance' => 8.4],
            ['origin' => 'MID', 'title' => 'Merida airport operations stable ahead of holiday traffic', 'category' => 'operations', 'severity' => 3.1, 'relevance' => 6.8],
            ['origin' => 'MIA', 'title' => 'Miami carrier staffing shortage may affect departures', 'category' => 'airline', 'severity' => 5.9, 'relevance' => 7.5],
            ['origin' => 'JFK', 'title' => 'Snow system may disrupt New York to Caribbean routes', 'category' => 'weather', 'severity' => 7.6, 'relevance' => 8.8],
            ['origin' => 'MEX', 'title' => 'Security screening delays reported at Mexico City airport', 'category' => 'operations', 'severity' => 5.2, 'relevance' => 7.3],
        ];

        foreach ($articles as $index => $article) {
            $originAirport = $this->airport($article['origin']);
            $watchTarget = $this->cityWatchTarget($article['origin']);
            $publishedAt = Carbon::now()->subHours(($index + 1) * 3);
            $slug = str($article['title'])->slug('-');
            $externalReference = sprintf('seed:news:%s:%s', $article['origin'], $slug);

            $payload = $this->seedPayload(
                $provider,
                $run,
                'news',
                $externalReference,
                [
                    'watch_target_id' => $watchTarget->id,
                    'items' => [[
                        'provider_slug' => $provider->slug,
                        'title' => $article['title'],
                        'summary' => 'Seeded travel intelligence article for demos and internal testing.',
                        'url' => "https://example.com/demo-news/{$slug}",
                        'published_at' => $publishedAt->toIso8601String(),
                        'topics' => [$article['category'], 'monitoring'],
                    ]],
                ],
                $publishedAt->toDateTimeString(),
            );

            NewsEvent::query()->updateOrCreate(
                [
                    'raw_payload_id' => $payload->id,
                ],
                [
                    'city_id' => $originAirport->city_id,
                    'airport_id' => $originAirport->id,
                    'airline_code' => null,
                    'published_at' => $publishedAt,
                    'title' => $article['title'],
                    'summary' => 'Seeded travel intelligence article for demos and internal testing.',
                    'url' => "https://example.com/demo-news/{$slug}",
                    'category' => $article['category'],
                    'severity_score' => $article['severity'],
                    'relevance_score' => $article['relevance'],
                    'source_provider_id' => $provider->id,
                ],
            );
        }
    }
}
