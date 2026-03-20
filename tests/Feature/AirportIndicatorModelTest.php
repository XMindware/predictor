<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\AirportIndicator;
use App\Models\City;
use App\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AirportIndicatorModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_airport_indicators_persist_with_relationships_and_casts(): void
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
        ]);

        $indicator = AirportIndicator::create([
            'airport_id' => $airport->id,
            'as_of' => '2026-03-19 12:00:00',
            'window_hours' => 24,
            'weather_score' => 7.25,
            'flight_score' => 5.5,
            'news_score' => 6.75,
            'combined_score' => 6.5,
            'supporting_factors' => [
                'weather' => ['condition' => 'RAIN', 'severity' => 7.25],
                'flight' => ['delay_average_minutes' => 18],
                'news' => ['headline_count' => 3],
            ],
        ]);

        $this->assertTrue($indicator->airport->is($airport));
        $this->assertTrue($airport->indicators->contains($indicator));
        $this->assertSame('2026-03-19 12:00:00', $indicator->as_of->format('Y-m-d H:i:s'));
        $this->assertSame(24, $indicator->window_hours);
        $this->assertSame(7.25, $indicator->weather_score);
        $this->assertSame(5.5, $indicator->flight_score);
        $this->assertSame(6.75, $indicator->news_score);
        $this->assertSame(6.5, $indicator->combined_score);
        $this->assertSame(
            [
                'weather' => ['condition' => 'RAIN', 'severity' => 7.25],
                'flight' => ['delay_average_minutes' => 18],
                'news' => ['headline_count' => 3],
            ],
            $indicator->supporting_factors
        );
    }
}
