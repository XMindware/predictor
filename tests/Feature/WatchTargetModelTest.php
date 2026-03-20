<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\City;
use App\Models\Country;
use App\Models\WatchTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WatchTargetModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_watch_target_supports_city_and_airport_scoped_monitoring_pairs(): void
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

        $watchTarget = WatchTarget::create([
            'origin_city_id' => $originCity->id,
            'origin_airport_id' => $originAirport->id,
            'destination_city_id' => $destinationCity->id,
            'destination_airport_id' => $destinationAirport->id,
            'enabled' => true,
            'monitoring_priority' => 10,
            'date_window_days' => 21,
        ]);

        $cityOnlyWatchTarget = WatchTarget::create([
            'origin_city_id' => $originCity->id,
            'enabled' => false,
            'monitoring_priority' => 3,
            'date_window_days' => 14,
        ]);

        $this->assertTrue($watchTarget->originCity->is($originCity));
        $this->assertTrue($watchTarget->originAirport->is($originAirport));
        $this->assertTrue($watchTarget->destinationCity->is($destinationCity));
        $this->assertTrue($watchTarget->destinationAirport->is($destinationAirport));
        $this->assertTrue($originCity->originWatchTargets->contains($watchTarget));
        $this->assertTrue($destinationCity->destinationWatchTargets->contains($watchTarget));
        $this->assertTrue($originAirport->originWatchTargets->contains($watchTarget));
        $this->assertTrue($destinationAirport->destinationWatchTargets->contains($watchTarget));
        $this->assertTrue($watchTarget->enabled);
        $this->assertSame(10, $watchTarget->monitoring_priority);
        $this->assertSame(21, $watchTarget->date_window_days);
        $this->assertNull($cityOnlyWatchTarget->originAirport);
        $this->assertNull($cityOnlyWatchTarget->destinationCity);
        $this->assertNull($cityOnlyWatchTarget->destinationAirport);
        $this->assertFalse($cityOnlyWatchTarget->enabled);
    }
}
