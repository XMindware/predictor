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
        // PostgreSQL: cast the JSONB field to integer for the JOIN.
        // The watch_target_id is stored as a JSON number inside
        // raw_provider_payloads.payload.
        DB::statement("
            UPDATE news_events ne
            SET
                city_id    = COALESCE(wt.destination_city_id,    wt.origin_city_id),
                airport_id = COALESCE(wt.destination_airport_id, wt.origin_airport_id)
            FROM raw_provider_payloads rpp
            JOIN watch_targets wt
                ON wt.id = (rpp.payload->>'watch_target_id')::int
            WHERE ne.raw_payload_id = rpp.id
              AND rpp.source_type   = 'news'
              AND wt.destination_city_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        // Reverse: restore origin attribution for route watch targets.
        DB::statement("
            UPDATE news_events ne
            SET
                city_id    = wt.origin_city_id,
                airport_id = wt.origin_airport_id
            FROM raw_provider_payloads rpp
            JOIN watch_targets wt
                ON wt.id = (rpp.payload->>'watch_target_id')::int
            WHERE ne.raw_payload_id = rpp.id
              AND rpp.source_type   = 'news'
              AND wt.destination_city_id IS NOT NULL
        ");
    }
};
