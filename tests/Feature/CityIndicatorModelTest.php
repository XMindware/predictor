<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\CityIndicator;
use App\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CityIndicatorModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_city_indicators_persist_with_relationships_and_casts(): void
    {
        $country = Country::create([
            'name' => 'Mexico',
        ]);

        $city = City::create([
            'country_id' => $country->id,
            'name' => 'Cancun',
        ]);

        $indicator = CityIndicator::create([
            'city_id' => $city->id,
            'as_of' => '2026-03-19 12:00:00',
            'window_hours' => 24,
            'weather_score' => 7.25,
            'news_score' => 6.75,
            'combined_score' => 7.0,
            'supporting_factors' => [
                'weather' => ['condition' => 'RAIN', 'severity' => 7.25],
                'news' => ['headline_count' => 4, 'severity' => 6.75],
            ],
        ]);

        $this->assertTrue($indicator->city->is($city));
        $this->assertTrue($city->indicators->contains($indicator));
        $this->assertSame('2026-03-19 12:00:00', $indicator->as_of->format('Y-m-d H:i:s'));
        $this->assertSame(24, $indicator->window_hours);
        $this->assertSame(7.25, $indicator->weather_score);
        $this->assertSame(6.75, $indicator->news_score);
        $this->assertSame(7.0, $indicator->combined_score);
        $this->assertSame(
            [
                'weather' => ['condition' => 'RAIN', 'severity' => 7.25],
                'news' => ['headline_count' => 4, 'severity' => 6.75],
            ],
            $indicator->supporting_factors
        );
    }
}
