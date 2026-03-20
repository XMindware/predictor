<?php

namespace App\Services\Providers\Normalizers;

use App\Models\NewsEvent;
use App\Models\RawProviderPayload;
use App\Models\WatchTarget;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class NewsPayloadNormalizer
{
    public function normalize(RawProviderPayload $payload): int
    {
        if ($payload->source_type !== 'news') {
            throw new InvalidArgumentException('Raw payload source type must be news.');
        }

        /** @var list<array<string, mixed>> $items */
        $items = $payload->payload['items'] ?? [];
        $watchTarget = $this->watchTarget($payload);

        return DB::transaction(function () use ($payload, $watchTarget, $items): int {
            $payload->newsEvents()->delete();

            foreach ($items as $item) {
                $topics = $item['topics'] ?? [];

                NewsEvent::create([
                    'city_id' => $watchTarget->origin_city_id,
                    'airport_id' => $watchTarget->origin_airport_id,
                    'airline_code' => $item['airline_code'] ?? null,
                    'published_at' => $item['published_at'] ?? $payload->fetched_at,
                    'title' => (string) ($item['title'] ?? 'Untitled article'),
                    'summary' => (string) ($item['summary'] ?? ''),
                    'url' => (string) ($item['url'] ?? ''),
                    'category' => $topics[0] ?? 'general',
                    'severity_score' => $this->severityScore($item),
                    'relevance_score' => $this->relevanceScore($item, $watchTarget),
                    'source_provider_id' => $payload->provider_id,
                    'raw_payload_id' => $payload->id,
                ]);
            }

            $payload->forceFill([
                'normalized_at' => now(),
            ])->save();

            return count($items);
        });
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function severityScore(array $item): float
    {
        $title = strtolower((string) ($item['title'] ?? ''));
        $topics = collect($item['topics'] ?? [])->map(fn ($topic) => strtolower((string) $topic));

        $base = $topics->contains('weather') || $topics->contains('disruption') ? 6.5 : 4.5;

        if (str_contains($title, 'warning') || str_contains($title, 'alert')) {
            $base += 2;
        }

        return round(min(10, $base), 2);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function relevanceScore(array $item, WatchTarget $watchTarget): float
    {
        $topics = collect($item['topics'] ?? []);
        $score = 5 + min(3, $topics->count());

        if ($watchTarget->origin_airport_id || $watchTarget->destination_airport_id) {
            $score += 1;
        }

        return round(min(10, $score), 2);
    }

    private function watchTarget(RawProviderPayload $payload): WatchTarget
    {
        $watchTargetId = $payload->payload['watch_target_id'] ?? null;

        if (! is_int($watchTargetId) && ! ctype_digit((string) $watchTargetId)) {
            throw new InvalidArgumentException('News payload is missing watch_target_id.');
        }

        return WatchTarget::query()
            ->with(['originCity', 'originAirport', 'destinationCity', 'destinationAirport'])
            ->findOrFail((int) $watchTargetId);
    }
}
