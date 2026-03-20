<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\City;
use App\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeographyModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_geography_models_persist_and_resolve_relationships(): void
    {
        $country = Country::create([
            'name' => 'Mexico',
        ]);

        $city = City::create([
            'country_id' => $country->id,
            'name' => 'Cancun',
        ]);

        $airport = Airport::create([
            'country_id' => $country->id,
            'city_id' => $city->id,
            'name' => 'Cancun International Airport',
            'iata' => 'CUN',
            'icao' => 'MMUN',
            'timezone' => 'America/Cancun',
            'latitude' => 21.0365,
            'longitude' => -86.8771,
        ]);

        $this->assertTrue($airport->city->is($city));
        $this->assertTrue($airport->country->is($country));
        $this->assertTrue($city->country->is($country));
        $this->assertTrue($country->airports->contains($airport));
        $this->assertTrue($country->cities->contains($city));
        $this->assertSame('CUN', $airport->iata);
        $this->assertSame('MMUN', $airport->icao);
        $this->assertSame('America/Cancun', $airport->timezone);
        $this->assertSame(21.0365, $airport->latitude);
        $this->assertSame(-86.8771, $airport->longitude);
    }
}
