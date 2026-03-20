<?php

namespace Tests\Feature;

use App\Models\FailedJob;
use App\Models\IngestionRun;
use App\Models\Provider;
use App\Models\User;
use App\Support\PlatformHealth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MonitoringDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_view_ingestion_runs_failed_jobs_and_stale_warnings_on_dashboard(): void
    {
        Config::set('cache.default', 'array');
        Carbon::setTestNow('2026-03-19 14:00:00');

        $user = User::factory()->create();
        $provider = Provider::create([
            'name' => 'OpenSky',
            'slug' => 'opensky',
            'service' => 'flight',
            'driver' => 'rest',
            'active' => true,
        ]);

        IngestionRun::create([
            'provider_id' => $provider->id,
            'source_type' => 'flight',
            'status' => 'failed',
            'started_at' => now()->subMinutes(20),
            'finished_at' => now()->subMinutes(18),
            'error_message' => 'Provider timed out.',
        ]);

        FailedJob::create([
            'uuid' => (string) str()->uuid(),
            'connection' => 'redis',
            'queue' => 'default',
            'payload' => '{"job":"Example"}',
            'exception' => 'RuntimeException: Queue worker failed while processing payload.',
            'failed_at' => now()->subMinutes(3),
        ]);

        Cache::forever(PlatformHealth::STALE_DATA_REPORT_CACHE_KEY, [
            'status' => 'degraded',
            'checked_at' => now()->toIso8601String(),
            'checks' => [
                'weather_payloads' => [
                    'status' => 'error',
                    'age_minutes' => 95,
                ],
                'normalization_backlog' => [
                    'status' => 'error',
                    'pending_count' => 4,
                ],
            ],
        ]);

        Cache::forever(PlatformHealth::FAILURE_ALERTS_CACHE_KEY, [
            [
                'level' => 'error',
                'title' => 'Recent failed queue jobs',
                'message' => '1 job(s) failed in the last hour.',
                'type' => 'failed_jobs',
            ],
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Operations Monitoring')
            ->assertSee('OpenSky')
            ->assertSee('Provider timed out.')
            ->assertSee('Recent failed queue jobs')
            ->assertSee('Latest payload is 95 minute(s) old.')
            ->assertSee('Pending normalization backlog');

        Carbon::setTestNow();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
