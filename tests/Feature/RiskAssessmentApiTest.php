<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\AirportIndicator;
use App\Models\City;
use App\Models\Country;
use App\Models\RiskQuerySnapshot;
use App\Models\Route;
use App\Models\RouteIndicator;
use App\Models\ScoringProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskAssessmentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_risk_assessment_endpoint_requires_sanctum_authentication(): void
    {
        $this->postJson('/api/risk-assessment', [
            'origin_airport' => 'CUN',
            'destination_airport' => 'MID',
            'travel_date' => '2026-03-25',
        ])->assertUnauthorized();
    }

    public function test_risk_assessment_endpoint_validates_origin_and_destination_inputs(): void
    {
        $token = User::factory()->create()->createToken('api-test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/risk-assessment', [
                'travel_date' => '2026-03-25',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['origin', 'destination']);
    }

    public function test_risk_assessment_endpoint_returns_score_confidence_factors_and_summaries(): void
    {
        $token = User::factory()->create()->createToken('api-test')->plainTextToken;
        [$originAirport, $destinationAirport, $route] = $this->setUpIndicators();

        $this->withToken($token)
            ->postJson('/api/risk-assessment', [
                'origin_airport' => 'CUN',
                'destination_airport' => 'MID',
                'travel_date' => '2026-03-25',
                'airline_code' => 'AM',
            ])
            ->assertOk()
            ->assertJson([
                'data' => [
                    'score' => 6.72,
                    'risk_level' => 'medium',
                    'confidence' => [
                        'level' => 'high',
                    ],
                    'factors' => [
                        'components' => [
                            'flight' => [
                                'score' => 8.0,
                            ],
                            'weather' => [
                                'score' => 5.0,
                            ],
                            'news' => [
                                'score' => 6.0,
                            ],
                        ],
                    ],
                    'query' => [
                        'origin_airport' => 'CUN',
                        'destination_airport' => 'MID',
                        'travel_date' => '2026-03-25',
                        'airline_code' => 'AM',
                    ],
                    'resolved' => [
                        'route' => [
                            'id' => $route->id,
                        ],
                    ],
                    'snapshot' => [
                        'persisted' => true,
                    ],
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'score',
                    'risk_level',
                    'confidence' => ['score', 'level', 'available_weight', 'possible_weight', 'coverage'],
                    'factors' => [
                        'components',
                        'weighted_contributions',
                    ],
                    'summaries',
                    'resolved',
                    'snapshot' => ['persisted', 'id', 'generated_at'],
                    'query',
                ],
            ]);

        $snapshot = RiskQuerySnapshot::query()->sole();

        $this->assertSame($originAirport->id, $snapshot->origin_airport_id);
        $this->assertSame($destinationAirport->id, $snapshot->destination_airport_id);
        $this->assertSame($route->id, $snapshot->route_id);
        $this->assertSame('2026-03-25', $snapshot->travel_date?->format('Y-m-d'));
        $this->assertSame('medium', $snapshot->risk_level);
        $this->assertSame('high', $snapshot->confidence_level);
    }

    public function test_risk_assessment_endpoint_reuses_cached_result_for_repeated_queries(): void
    {
        $token = User::factory()->create()->createToken('api-test')->plainTextToken;
        $this->setUpIndicators();

        $payload = [
            'origin_airport' => 'CUN',
            'destination_airport' => 'MID',
            'travel_date' => '2026-03-25',
            'airline_code' => 'AM',
        ];

        $this->withToken($token)->postJson('/api/risk-assessment', $payload)->assertOk();
        $this->withToken($token)->postJson('/api/risk-assessment', $payload)->assertOk();

        $this->assertDatabaseCount('risk_query_snapshots', 1);
    }

    /**
     * @return array{0: Airport, 1: Airport, 2: Route}
     */
    private function setUpIndicators(): array
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

        return [$originAirport, $destinationAirport, $route];
    }
}
