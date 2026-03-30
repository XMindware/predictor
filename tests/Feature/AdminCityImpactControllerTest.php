<?php

namespace Tests\Feature;

use App\Jobs\FetchFlightDataJob;
use App\Jobs\FetchNewsDataJob;
use App\Jobs\FetchWeatherDataJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class AdminCityImpactControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_city_fetch_queues_ingestion_jobs_on_database_connection(): void
    {
        Bus::fake();

        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->from('/admin/cities/CUN')
            ->post(route('admin.cities.trigger-fetch', ['iata' => 'CUN']))
            ->assertRedirect('/admin/cities/CUN')
            ->assertSessionHas('status', 'Fetch jobs dispatched for CUN. Results will appear after the queue processes them.');

        Bus::assertDispatched(FetchNewsDataJob::class, fn (FetchNewsDataJob $job): bool => $job->connection === 'database');
        Bus::assertDispatched(FetchWeatherDataJob::class, fn (FetchWeatherDataJob $job): bool => $job->connection === 'database');
        Bus::assertDispatched(FetchFlightDataJob::class, fn (FetchFlightDataJob $job): bool => $job->connection === 'database');
    }
}
