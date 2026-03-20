<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\AirportIndicator;
use App\Models\City;
use App\Models\CityIndicator;
use App\Models\Country;
use App\Models\FailedJob;
use App\Models\IngestionRun;
use App\Models\Provider;
use App\Models\Route;
use App\Models\RouteIndicator;
use App\Models\User;
use App\Models\WatchTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOpsPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_users_are_redirected_away_from_operations_panel(): void
    {
        $this->get('/admin/ops')
            ->assertRedirect('/login');
    }

    public function test_authenticated_users_can_inspect_ops_datasets_from_the_operations_panel(): void
    {
        $user = User::factory()->create();

        $provider = Provider::create([
            'name' => 'OpenSky',
            'slug' => 'opensky',
            'service' => 'flight',
            'driver' => 'rest',
            'active' => true,
        ]);

        $country = Country::create([
            'name' => 'Mexico',
        ]);

        $originCity = City::create([
            'country_id' => $country->id,
            'name' => 'Cancun',
        ]);

        $destinationCity = City::create([
            'country_id' => $country->id,
            'name' => 'Merida',
        ]);

        $originAirport = Airport::create([
            'country_id' => $country->id,
            'city_id' => $originCity->id,
            'name' => 'Cancun International Airport',
            'iata' => 'CUN',
            'icao' => 'MMUN',
            'timezone' => 'America/Cancun',
        ]);

        $destinationAirport = Airport::create([
            'country_id' => $country->id,
            'city_id' => $destinationCity->id,
            'name' => 'Merida International Airport',
            'iata' => 'MID',
            'icao' => 'MMMD',
            'timezone' => 'America/Merida',
        ]);

        $route = Route::create([
            'origin_airport_id' => $originAirport->id,
            'destination_airport_id' => $destinationAirport->id,
            'active' => true,
        ]);

        WatchTarget::create([
            'origin_city_id' => $originCity->id,
            'origin_airport_id' => $originAirport->id,
            'destination_city_id' => $destinationCity->id,
            'destination_airport_id' => $destinationAirport->id,
            'enabled' => true,
            'monitoring_priority' => 8,
            'date_window_days' => 14,
        ]);

        IngestionRun::create([
            'provider_id' => $provider->id,
            'source_type' => 'flight',
            'status' => 'failed',
            'started_at' => now()->subMinutes(20),
            'finished_at' => now()->subMinutes(18),
            'error_message' => 'Provider timed out.',
        ]);

        AirportIndicator::create([
            'airport_id' => $originAirport->id,
            'as_of' => now()->subMinutes(30),
            'window_hours' => 24,
            'weather_score' => 4.0,
            'flight_score' => 6.0,
            'news_score' => 5.0,
            'combined_score' => 5.0,
            'supporting_factors' => [
                'weather' => ['events_count' => 2],
                'flight' => ['events_count' => 3],
                'news' => ['events_count' => 1],
            ],
        ]);

        CityIndicator::create([
            'city_id' => $originCity->id,
            'as_of' => now()->subMinutes(30),
            'window_hours' => 24,
            'weather_score' => 4.0,
            'news_score' => 5.0,
            'combined_score' => 4.5,
            'supporting_factors' => [
                'weather' => ['events_count' => 2],
                'news' => ['events_count' => 1],
            ],
        ]);

        RouteIndicator::create([
            'route_id' => $route->id,
            'as_of' => now()->subMinutes(30),
            'travel_date' => now()->addDays(7)->toDateString(),
            'window_hours' => 24,
            'flight_score' => 6.0,
            'news_score' => 5.0,
            'combined_score' => 5.5,
            'supporting_factors' => [
                'flight' => ['events_count' => 4],
                'news' => ['events_count' => 2],
            ],
        ]);

        FailedJob::create([
            'uuid' => (string) str()->uuid(),
            'connection' => 'redis',
            'queue' => 'default',
            'payload' => '{"job":"Example"}',
            'exception' => 'RuntimeException: Queue worker failed while processing payload.',
            'failed_at' => now()->subMinutes(3),
        ]);

        $this->actingAs($user)
            ->get(route('admin.ops.index'))
            ->assertOk()
            ->assertSee('Operations Panel')
            ->assertSee('Re-fetch News For City')
            ->assertSee('OpenSky')
            ->assertSee('Cancun')
            ->assertSee('Merida')
            ->assertSee('Provider timed out.')
            ->assertSee('How to read these values')
            ->assertSee('Hide Details')
            ->assertSee('5.00')
            ->assertSee('4.50')
            ->assertSee('5.50')
            ->assertSee('Weather 4.00')
            ->assertSee('Flight 6.00')
            ->assertSee('News 5.00')
            ->assertSee('2 events')
            ->assertSee('4 events')
            ->assertSee('RuntimeException: Queue worker failed while processing payload.');
    }
}
