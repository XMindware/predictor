<?php

namespace Tests\Feature;

use App\Models\IngestionRun;
use App\Models\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngestionRunTest extends TestCase
{
    use RefreshDatabase;

    public function test_ingestion_runs_track_provider_lifecycle_metadata_and_errors(): void
    {
        $provider = Provider::create([
            'name' => 'OpenWeather',
            'slug' => 'openweather',
            'service' => 'weather',
            'driver' => 'rest',
            'active' => true,
        ]);

        $run = IngestionRun::create([
            'provider_id' => $provider->id,
            'source_type' => 'weather',
            'status' => 'failed',
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
            'request_meta' => [
                'route' => 'CUN-MIA',
                'window_days' => 7,
            ],
            'response_meta' => [
                'http_status' => 503,
                'attempts' => 2,
            ],
            'error_message' => 'Provider timeout',
        ]);

        $this->assertTrue($run->provider->is($provider));
        $this->assertTrue($provider->ingestionRuns->contains($run));
        $this->assertSame('weather', $run->source_type);
        $this->assertSame('failed', $run->status);
        $this->assertSame('CUN-MIA', $run->request_meta['route']);
        $this->assertSame(503, $run->response_meta['http_status']);
        $this->assertSame('Provider timeout', $run->error_message);
        $this->assertNotNull($run->started_at);
        $this->assertNotNull($run->finished_at);
    }
}
