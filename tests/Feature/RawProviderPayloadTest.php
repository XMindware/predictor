<?php

namespace Tests\Feature;

use App\Models\IngestionRun;
use App\Models\Provider;
use App\Models\RawProviderPayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RawProviderPayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_raw_provider_payloads_link_runs_providers_and_original_payloads(): void
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
            'status' => 'completed',
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
            'request_meta' => ['city' => 'CUN'],
            'response_meta' => ['http_status' => 200],
        ]);

        $payload = RawProviderPayload::create([
            'provider_id' => $provider->id,
            'source_type' => 'weather',
            'external_reference' => 'weather:forecast:CUN',
            'payload' => [
                'forecast' => ['rain_probability' => 0.3],
                'provider_status' => 'ok',
            ],
            'fetched_at' => now(),
            'ingestion_run_id' => $run->id,
        ]);

        $this->assertTrue($payload->provider->is($provider));
        $this->assertTrue($payload->ingestionRun->is($run));
        $this->assertTrue($provider->rawPayloads->contains($payload));
        $this->assertTrue($run->rawPayloads->contains($payload));
        $this->assertSame('weather', $payload->source_type);
        $this->assertSame('weather:forecast:CUN', $payload->external_reference);
        $this->assertSame('ok', $payload->payload['provider_status']);
        $this->assertSame(0.3, $payload->payload['forecast']['rain_probability']);
        $this->assertNotNull($payload->fetched_at);
    }
}
