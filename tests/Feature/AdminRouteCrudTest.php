<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\User;
use Database\Seeders\BasicGeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRouteCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_users_are_redirected_away_from_route_admin_routes(): void
    {
        $this->get('/admin/routes')
            ->assertRedirect('/login');
    }

    public function test_authenticated_users_can_create_route_records_and_matching_watch_targets(): void
    {
        $this->seed(BasicGeographySeeder::class);

        $user = User::factory()->create();
        $originAirport = Airport::query()->where('iata', 'JFK')->firstOrFail();
        $destinationAirport = Airport::query()->where('iata', 'CUN')->firstOrFail();
        $csrf = 'route-create-token';

        $this->actingAs($user)
            ->withSession(['_token' => $csrf])
            ->post(route('admin.routes.store'), [
                '_token' => $csrf,
                'origin_airport_id' => $originAirport->id,
                'destination_airport_id' => $destinationAirport->id,
                'active' => '1',
                'notes' => 'Primary inbound route',
            ])
            ->assertRedirect(route('admin.routes.index'));

        $this->assertDatabaseHas('routes', [
            'origin_airport_id' => $originAirport->id,
            'destination_airport_id' => $destinationAirport->id,
            'active' => true,
            'notes' => 'Primary inbound route',
        ]);
        $this->assertDatabaseHas('watch_targets', [
            'origin_city_id' => $originAirport->city_id,
            'origin_airport_id' => $originAirport->id,
            'destination_city_id' => $destinationAirport->city_id,
            'destination_airport_id' => $destinationAirport->id,
            'enabled' => true,
        ]);
    }

    public function test_authenticated_users_can_update_routes_and_sync_their_watch_targets(): void
    {
        $this->seed(BasicGeographySeeder::class);

        $user = User::factory()->create();
        $jfk = Airport::query()->where('iata', 'JFK')->firstOrFail();
        $cun = Airport::query()->where('iata', 'CUN')->firstOrFail();
        $yyz = Airport::query()->where('iata', 'YYZ')->firstOrFail();
        $route = \App\Models\Route::query()->create([
            'origin_airport_id' => $jfk->id,
            'destination_airport_id' => $cun->id,
            'active' => true,
            'notes' => 'Seed route',
        ]);
        \App\Models\WatchTarget::query()->create([
            'origin_city_id' => $jfk->city_id,
            'origin_airport_id' => $jfk->id,
            'destination_city_id' => $cun->city_id,
            'destination_airport_id' => $cun->id,
            'enabled' => true,
            'monitoring_priority' => 8,
            'date_window_days' => 10,
        ]);
        $csrf = 'route-update-token';

        $this->actingAs($user)
            ->withSession(['_token' => $csrf])
            ->put(route('admin.routes.update', $route), [
                '_token' => $csrf,
                'origin_airport_id' => $yyz->id,
                'destination_airport_id' => $cun->id,
                'active' => '0',
                'notes' => 'Updated route',
            ])
            ->assertRedirect(route('admin.routes.index'));

        $route->refresh();

        $this->assertSame($yyz->id, $route->origin_airport_id);
        $this->assertFalse($route->active);
        $this->assertSame('Updated route', $route->notes);
        $this->assertDatabaseMissing('watch_targets', [
            'origin_airport_id' => $jfk->id,
            'destination_airport_id' => $cun->id,
        ]);
        $this->assertDatabaseHas('watch_targets', [
            'origin_airport_id' => $yyz->id,
            'destination_airport_id' => $cun->id,
            'enabled' => false,
        ]);
    }

    public function test_authenticated_users_can_delete_routes_and_their_matching_watch_targets(): void
    {
        $this->seed(BasicGeographySeeder::class);

        $user = User::factory()->create();
        $originAirport = Airport::query()->where('iata', 'JFK')->firstOrFail();
        $destinationAirport = Airport::query()->where('iata', 'CUN')->firstOrFail();
        $route = \App\Models\Route::query()->create([
            'origin_airport_id' => $originAirport->id,
            'destination_airport_id' => $destinationAirport->id,
            'active' => true,
        ]);
        $watchTarget = \App\Models\WatchTarget::query()->create([
            'origin_city_id' => $originAirport->city_id,
            'origin_airport_id' => $originAirport->id,
            'destination_city_id' => $destinationAirport->city_id,
            'destination_airport_id' => $destinationAirport->id,
            'enabled' => true,
            'monitoring_priority' => 8,
            'date_window_days' => 10,
        ]);
        $csrf = 'route-delete-token';

        $this->actingAs($user)
            ->withSession(['_token' => $csrf])
            ->delete(route('admin.routes.destroy', $route), [
                '_token' => $csrf,
            ])
            ->assertRedirect(route('admin.routes.index'));

        $this->assertDatabaseMissing('routes', [
            'id' => $route->id,
        ]);
        $this->assertDatabaseMissing('watch_targets', [
            'id' => $watchTarget->id,
        ]);
    }
}
