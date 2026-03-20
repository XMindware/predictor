<?php

namespace Tests\Feature;

use Database\Seeders\BasicGeographySeeder;
use Database\Seeders\DefaultRouteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultRouteSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_route_seeder_creates_active_inbound_routes_and_watch_targets_for_the_base_airport(): void
    {
        config()->set('operations.base_airport_iata', 'CUN');

        $this->seed(BasicGeographySeeder::class);
        $this->seed(DefaultRouteSeeder::class);

        $this->assertDatabaseHas('routes', [
            'origin_airport_id' => \App\Models\Airport::where('iata', 'JFK')->value('id'),
            'destination_airport_id' => \App\Models\Airport::where('iata', 'CUN')->value('id'),
            'active' => true,
        ]);
        $this->assertDatabaseHas('watch_targets', [
            'origin_airport_id' => \App\Models\Airport::where('iata', 'YYZ')->value('id'),
            'destination_airport_id' => \App\Models\Airport::where('iata', 'CUN')->value('id'),
            'enabled' => true,
            'date_window_days' => 10,
        ]);
        $this->assertDatabaseMissing('routes', [
            'origin_airport_id' => \App\Models\Airport::where('iata', 'CUN')->value('id'),
            'destination_airport_id' => \App\Models\Airport::where('iata', 'CUN')->value('id'),
        ]);

        $this->assertSame(
            \App\Models\Airport::count() - 1,
            \App\Models\Route::count(),
        );
    }
}
