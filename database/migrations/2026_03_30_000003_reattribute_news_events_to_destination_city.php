<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Re-attribute existing news events to the destination city/airport.
 *
 * Previously the normalizer stored `city_id = watch_target.origin_city_id`,
 * which meant all news fetched for a route like MIA → SJD was filed under
 * Miami instead of San José del Cabo.  The CityNewsImpactService queries
 * `WHERE city_id = $destination_airport->city_id`, so SJD (and every other
 * monitored destination other than CUN) showed zero events.
 *
 * This migration traces each news_event back through:
 *   news_events → raw_provider_payloads → watch_targets
 * and updates city_id / airport_id to the destination values whenever a
 * destination exists on the watch target.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->updateNewsEventAttribution(
            fn (object $watchTarget): array => [
                'city_id' => $watchTarget->destination_city_id ?? $watchTarget->origin_city_id,
                'airport_id' => $watchTarget->destination_airport_id ?? $watchTarget->origin_airport_id,
            ],
        );
    }

    public function down(): void
    {
        $this->updateNewsEventAttribution(
            fn (object $watchTarget): array => [
                'city_id' => $watchTarget->origin_city_id,
                'airport_id' => $watchTarget->origin_airport_id,
            ],
        );
    }

    /**
     * Re-attribute news events using query-builder updates so the migration
     * runs on PostgreSQL, MySQL, and SQLite.
     *
     * @param  callable(object): array{city_id: mixed, airport_id: mixed}  $attributes
     */
    private function updateNewsEventAttribution(callable $attributes): void
    {
        DB::table('raw_provider_payloads')
            ->select(['id', 'payload'])
            ->where('source_type', 'news')
            ->orderBy('id')
            ->chunkById(100, function ($payloads) use ($attributes): void {
                $watchTargetIds = $payloads
                    ->map(fn (object $payload): ?int => $this->extractWatchTargetId($payload->payload))
                    ->filter()
                    ->unique()
                    ->values();

                if ($watchTargetIds->isEmpty()) {
                    return;
                }

                $watchTargets = DB::table('watch_targets')
                    ->select([
                        'id',
                        'origin_city_id',
                        'origin_airport_id',
                        'destination_city_id',
                        'destination_airport_id',
                    ])
                    ->whereIn('id', $watchTargetIds)
                    ->get()
                    ->keyBy('id');

                foreach ($payloads as $payload) {
                    $watchTargetId = $this->extractWatchTargetId($payload->payload);

                    if ($watchTargetId === null) {
                        continue;
                    }

                    $watchTarget = $watchTargets->get($watchTargetId);

                    if ($watchTarget === null || $watchTarget->destination_city_id === null) {
                        continue;
                    }

                    DB::table('news_events')
                        ->where('raw_payload_id', $payload->id)
                        ->update($attributes($watchTarget));
                }
            });
    }

    private function extractWatchTargetId(mixed $payload): ?int
    {
        if (is_string($payload)) {
            $payload = json_decode($payload, true);
        } elseif (is_object($payload)) {
            $payload = (array) $payload;
        }

        if (! is_array($payload)) {
            return null;
        }

        $watchTargetId = $payload['watch_target_id'] ?? null;

        return is_numeric($watchTargetId) ? (int) $watchTargetId : null;
    }
};
