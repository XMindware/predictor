<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\City;
use App\Models\Country;
use App\Models\Route;
use App\Models\RouteIndicator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteIndicatorModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_indicators_persist_with_relationships_and_casts(): void
    {
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

        $indicator = RouteIndicator::create([
            'route_id' => $route->id,
            'as_of' => '2026-03-19 12:00:00',
            'travel_date' => '2026-03-21',
            'window_hours' => 24,
            'flight_score' => 6.25,
            'news_score' => 5.75,
            'combined_score' => 6.0,
            'supporting_factors' => [
                'flight' => ['delay_average_minutes' => 22, 'cancellation_rate' => 1.8],
                'news' => ['headline_count' => 2, 'severity' => 5.75],
            ],
        ]);

        $this->assertTrue($indicator->route->is($route));
        $this->assertTrue($route->indicators->contains($indicator));
        $this->assertSame('2026-03-19 12:00:00', $indicator->as_of->format('Y-m-d H:i:s'));
        $this->assertSame('2026-03-21', $indicator->travel_date?->format('Y-m-d'));
        $this->assertSame(24, $indicator->window_hours);
        $this->assertSame(6.25, $indicator->flight_score);
        $this->assertSame(5.75, $indicator->news_score);
        $this->assertSame(6.0, $indicator->combined_score);
        $this->assertSame(
            [
                'flight' => ['delay_average_minutes' => 22, 'cancellation_rate' => 1.8],
                'news' => ['headline_count' => 2, 'severity' => 5.75],
            ],
            $indicator->supporting_factors
        );
    }
}
