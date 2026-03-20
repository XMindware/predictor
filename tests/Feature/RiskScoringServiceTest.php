<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\AirportIndicator;
use App\Models\City;
use App\Models\CityIndicator;
use App\Models\Country;
use App\Models\RiskQuerySnapshot;
use App\Models\Route;
use App\Models\RouteIndicator;
use App\Models\ScoringProfile;
use App\Services\RiskScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RiskScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_weighted_risk_from_route_and_airport_indicators(): void
    {
        Carbon::setTestNow('2026-03-20 09:00:00');

        [$originAirport, $destinationAirport, $route] = $this->setUpLocations();
        $this->setUpProfile();

        AirportIndicator::create([
            'airport_id' => $originAirport->id,
            'as_of' => '2026-03-20 08:00:00',
            'window_hours' => 24,
            'weather_score' => 6.0,
            'flight_score' => 7.0,
            'news_score' => 4.0,
            'combined_score' => 5.67,
            'supporting_factors' => ['source' => 'origin'],
        ]);

        AirportIndicator::create([
            'airport_id' => $destinationAirport->id,
            'as_of' => '2026-03-20 08:00:00',
            'window_hours' => 24,
            'weather_score' => 4.0,
            'flight_score' => 3.0,
            'news_score' => 2.0,
            'combined_score' => 3.0,
            'supporting_factors' => ['source' => 'destination'],
        ]);

        RouteIndicator::create([
            'route_id' => $route->id,
            'as_of' => '2026-03-20 08:00:00',
            'travel_date' => '2026-03-25',
            'window_hours' => 24,
            'flight_score' => 8.0,
            'news_score' => 6.0,
            'combined_score' => 7.0,
            'supporting_factors' => ['route' => true],
        ]);

        $result = app(RiskScoringService::class)->calculate('CUN', 'MID', '2026-03-25');

        $this->assertSame(6.72, $result['score']);
        $this->assertSame('medium', $result['risk_level']);
        $this->assertSame('high', $result['confidence']['level']);
        $this->assertSame(1.0, $result['confidence']['score']);
        $this->assertSame(8.0, $result['components']['flight']['score']);
        $this->assertSame(5.0, $result['components']['weather']['score']);
        $this->assertSame(6.0, $result['components']['news']['score']);
        $this->assertSame(8.33, $result['components']['date_proximity']['score']);
        $this->assertSame($route->id, $result['resolved']['route']['id']);
        $this->assertContains('Derived risk level is medium.', $result['explanations']);
        $this->assertTrue($result['snapshot']['persisted']);

        $snapshot = RiskQuerySnapshot::query()->sole();

        $this->assertSame($route->id, $snapshot->route_id);
        $this->assertSame($originAirport->id, $snapshot->origin_airport_id);
        $this->assertSame($destinationAirport->id, $snapshot->destination_airport_id);
        $this->assertSame('2026-03-25', $snapshot->travel_date?->format('Y-m-d'));
        $this->assertSame(6.72, $snapshot->score);
        $this->assertSame('medium', $snapshot->risk_level);
        $this->assertSame('high', $snapshot->confidence_level);
        $this->assertSame(8, $snapshot->factors['components']['flight']['score']);
        $this->assertContains('Derived risk level is medium.', $snapshot->factors['explanations']);
    }

    public function test_it_falls_back_to_city_indicators_and_reports_partial_confidence(): void
    {
        Carbon::setTestNow('2026-03-20 09:00:00');

        [$originAirport, $destinationAirport] = $this->setUpLocations();
        $this->setUpProfile();

        CityIndicator::create([
            'city_id' => $originAirport->city_id,
            'as_of' => '2026-03-20 08:00:00',
            'window_hours' => 24,
            'weather_score' => 7.0,
            'news_score' => 4.0,
            'combined_score' => 5.5,
            'supporting_factors' => ['city' => 'origin'],
        ]);

        CityIndicator::create([
            'city_id' => $destinationAirport->city_id,
            'as_of' => '2026-03-20 08:00:00',
            'window_hours' => 24,
            'weather_score' => 5.0,
            'news_score' => 2.0,
            'combined_score' => 3.5,
            'supporting_factors' => ['city' => 'destination'],
        ]);

        $result = app(RiskScoringService::class)->calculate('Cancun', 'Merida', null);

        $this->assertSame(2.4, $result['score']);
        $this->assertSame('minimal', $result['risk_level']);
        $this->assertSame('medium', $result['confidence']['level']);
        $this->assertSame(0.53, $result['confidence']['score']);
        $this->assertSame(0.5, $result['confidence']['available_weight']);
        $this->assertFalse($result['components']['flight']['data_present']);
        $this->assertSame(6.0, $result['components']['weather']['score']);
        $this->assertSame(3.0, $result['components']['news']['score']);
        $this->assertContains('No recent flight signal was available for this itinerary.', $result['explanations']);
        $this->assertFalse($result['snapshot']['persisted']);
        $this->assertDatabaseCount('risk_query_snapshots', 0);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @return array{0: Airport, 1: Airport, 2: Route}
     */
    private function setUpLocations(): array
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

        return [$originAirport, $destinationAirport, $route];
    }

    private function setUpProfile(): void
    {
        ScoringProfile::create([
            'name' => 'Default Risk Profile',
            'version' => 'v1',
            'weights' => [
                'flight' => 0.45,
                'weather' => 0.30,
                'news' => 0.20,
                'date_proximity' => 0.05,
            ],
            'thresholds' => [
                'low' => 3.0,
                'medium' => 6.0,
                'high' => 8.0,
            ],
            'active' => true,
        ]);
    }
}
