<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\City;
use App\Models\Country;
use App\Models\RiskQuerySnapshot;
use App\Models\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskQuerySnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_risk_query_snapshots_persist_with_location_relationships_and_factors(): void
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

        $snapshot = RiskQuerySnapshot::create([
            'origin_city_id' => $originCity->id,
            'origin_airport_id' => $originAirport->id,
            'destination_city_id' => $destinationCity->id,
            'destination_airport_id' => $destinationAirport->id,
            'route_id' => $route->id,
            'travel_date' => '2026-03-25',
            'score' => 6.72,
            'risk_level' => 'medium',
            'confidence_level' => 'high',
            'factors' => [
                'flight' => ['score' => 8.0, 'source' => 'route_indicator:travel_date'],
                'weather' => ['score' => 5.0, 'source' => 'airport_indicators'],
                'news' => ['score' => 6.0, 'source' => 'route_indicator'],
            ],
            'generated_at' => '2026-03-20 08:15:00',
        ]);

        $this->assertTrue($snapshot->originCity->is($originCity));
        $this->assertTrue($snapshot->originAirport->is($originAirport));
        $this->assertTrue($snapshot->destinationCity->is($destinationCity));
        $this->assertTrue($snapshot->destinationAirport->is($destinationAirport));
        $this->assertTrue($snapshot->route->is($route));
        $this->assertSame('2026-03-25', $snapshot->travel_date?->format('Y-m-d'));
        $this->assertSame(6.72, $snapshot->score);
        $this->assertSame('medium', $snapshot->risk_level);
        $this->assertSame('high', $snapshot->confidence_level);
        $this->assertSame('route_indicator', $snapshot->factors['news']['source']);
        $this->assertSame('2026-03-20 08:15:00', $snapshot->generated_at->format('Y-m-d H:i:s'));
    }
}
