<?php

namespace Database\Seeders;

use App\Models\IngestionRun;
use App\Models\NewsEvent;
use App\Models\RawProviderPayload;
use Illuminate\Database\Seeder;

/**
 * Cleans up any previously seeded demo news data.
 *
 * This seeder no longer inserts fake articles.
 * Real news is ingested by FetchNewsDataJob via NewsAPI / RSS providers.
 */
class NewsSourceSeeder extends Seeder
{
    use Concerns\SeedsDemoSourceData;

    public function run(): void
    {
        // ── Remove previously seeded demo news events ────────────────────────
        // Identify seeded raw payloads by their external_reference prefix.
        $seededPayloadIds = RawProviderPayload::query()
            ->where('source_type', 'news')
            ->where('external_reference', 'like', 'seed:news:%')
            ->pluck('id');

        if ($seededPayloadIds->isNotEmpty()) {
            NewsEvent::query()->whereIn('raw_payload_id', $seededPayloadIds)->delete();
            RawProviderPayload::query()->whereIn('id', $seededPayloadIds)->delete();
        }

        // Also catch any events with example.com demo URLs that may have been
        // inserted without a seeded payload reference.
        NewsEvent::query()
            ->where('url', 'like', 'https://example.com/demo-news/%')
            ->delete();

        // Clean up seeded ingestion runs (identified by the seeded error_message marker).
        IngestionRun::query()
            ->where('source_type', 'news')
            ->where('error_message', 'like', 'seed:%')
            ->delete();
    }
}
