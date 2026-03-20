<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\City;
use App\Models\Country;
use App\Models\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_model_persists_and_resolves_airport_relationships(): void
    {
        $country = Country::create([
            'name' => 'Mexico',
        ]);

        $city = City::create([
            'country_id' => $country->id,
            'name' => 'Cancun',
        ]);

        $originAirport = Airport::create([
            'country_id' => $country->id,
            'city_id' => $city->id,
            'name' => 'Cancun International Airport',
            'iata' => 'CUN',
            'icao' => 'MMUN',
            'timezone' => 'America/Cancun',
        ]);

        $destinationAirport = Airport::create([
            'country_id' => $country->id,
            'city_id' => $city->id,
            'name' => 'Cozumel International Airport',
            'iata' => 'CZM',
            'icao' => 'MMCZ',
            'timezone' => 'America/Cancun',
        ]);

        $route = Route::create([
            'origin_airport_id' => $originAirport->id,
            'destination_airport_id' => $destinationAirport->id,
            'active' => true,
            'notes' => 'Primary route for risk scoring.',
        ]);

        $this->assertTrue($route->originAirport->is($originAirport));
        $this->assertTrue($route->destinationAirport->is($destinationAirport));
        $this->assertTrue($originAirport->originRoutes->contains($route));
        $this->assertTrue($destinationAirport->destinationRoutes->contains($route));
        $this->assertTrue($route->active);
        $this->assertSame('Primary route for risk scoring.', $route->notes);
    }
}
