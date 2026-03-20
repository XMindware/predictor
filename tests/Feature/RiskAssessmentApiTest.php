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
use App\Models\WatchTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RiskAssessmentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_risk_assessment_endpoint_requires_sanctum_authentication(): void
    {
        $this->postJson('/api/risk-assessment', [
            'origin_airport' => 'CUN',
            'destination_airport' => 'MID',
            'travel_date' => '2026-03-22',
        ])->assertUnauthorized();
    }

    public function test_risk_assessment_endpoint_validates_origin_and_destination_inputs(): void
    {
        $token = User::factory()->create()->createToken('api-test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/risk-assessment', [
                'travel_date' => '2026-03-22',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['origin', 'destination']);
    }

    public function test_risk_assessment_endpoint_returns_score_confidence_factors_and_summaries(): void
    {
        Carbon::setTestNow('2026-03-20 09:00:00');

        $token = User::factory()->create()->createToken('api-test')->plainTextToken;
        [$originAirport, $destinationAirport, $route] = $this->setUpIndicators();

        $this->withToken($token)
            ->postJson('/api/risk-assessment', [
                'origin_airport' => 'CUN',
                'destination_airport' => 'MID',
                'travel_date' => '2026-03-22',
                'airline_code' => 'AM',
            ])
            ->assertOk()
            ->assertJson([
                'data' => [
                    'assessment_type' => 'short_term_travel_disruption_risk',
                    'scoring_mode' => 'deterministic_rules',
                    'scope' => [
                        'travel_window_hours' => 72,
                        'monitored_routes_only' => true,
                    ],
                    'score' => 6.77,
                    'risk_level' => 'medium',
                    'confidence' => [
                        'level' => 'high',
                    ],
                    'freshness' => [
                        'level' => 'fresh',
                        'minutes_since_latest_signal' => 60,
                        'minutes_since_stalest_signal' => 60,
                    ],
                    'drivers' => [
                        [
                            'factor' => 'flight',
                            'weighted_contribution' => 3.6,
                        ],
                    ],
                    'probable_no_show_uplift' => [
                        'estimate_percent' => 2.0,
                        'method' => 'heuristic_from_disruption_risk',
                    ],
                    'recommended_action' => [
                        'code' => 'watch_and_adjust',
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
                        'travel_date' => '2026-03-22',
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
                    'assessment_type',
                    'scoring_mode',
                    'product_framing',
                    'scope',
                    'score',
                    'risk_level',
                    'confidence' => ['score', 'level', 'available_weight', 'possible_weight', 'coverage'],
                    'freshness' => [
                        'level',
                        'latest_signal_at',
                        'stalest_signal_at',
                        'minutes_since_latest_signal',
                        'minutes_since_stalest_signal',
                        'component_ages',
                    ],
                    'drivers',
                    'probable_no_show_uplift' => [
                        'estimate_percent',
                        'range_percent' => ['low', 'high'],
                        'method',
                        'framing',
                    ],
                    'recommended_action' => ['code', 'summary', 'primary_driver'],
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
        $this->assertSame('2026-03-22', $snapshot->travel_date?->format('Y-m-d'));
        $this->assertSame('medium', $snapshot->risk_level);
        $this->assertSame('high', $snapshot->confidence_level);
        $this->assertSame('short_term_travel_disruption_risk', $snapshot->factors['assessment_type']);
        $this->assertSame('deterministic_rules', $snapshot->factors['scoring_mode']);
        $this->assertSame('watch_and_adjust', $snapshot->factors['recommended_action']['code']);
    }

    public function test_risk_assessment_endpoint_reuses_cached_result_for_repeated_queries(): void
    {
        Carbon::setTestNow('2026-03-20 09:00:00');

        $token = User::factory()->create()->createToken('api-test')->plainTextToken;
        $this->setUpIndicators();

        $payload = [
            'origin_airport' => 'CUN',
            'destination_airport' => 'MID',
            'travel_date' => '2026-03-22',
            'airline_code' => 'AM',
        ];

        $this->withToken($token)->postJson('/api/risk-assessment', $payload)->assertOk();
        $this->withToken($token)->postJson('/api/risk-assessment', $payload)->assertOk();

        $this->assertDatabaseCount('risk_query_snapshots', 1);
    }

    public function test_risk_assessment_endpoint_rejects_dates_outside_the_v1_window(): void
    {
        Carbon::setTestNow('2026-03-20 09:00:00');

        $token = User::factory()->create()->createToken('api-test')->plainTextToken;
        $this->setUpIndicators();

        $this->withToken($token)
            ->postJson('/api/risk-assessment', [
                'origin_airport' => 'CUN',
                'destination_airport' => 'MID',
                'travel_date' => '2026-03-25',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['travel_date']);
    }

    public function test_risk_assessment_endpoint_rejects_unmonitored_routes(): void
    {
        Carbon::setTestNow('2026-03-20 09:00:00');

        $token = User::factory()->create()->createToken('api-test')->plainTextToken;
        [$originAirport, $destinationAirport] = $this->setUpIndicators();

        WatchTarget::query()->delete();

        $this->withToken($token)
            ->postJson('/api/risk-assessment', [
                'origin_airport' => $originAirport->iata,
                'destination_airport' => $destinationAirport->iata,
                'travel_date' => '2026-03-22',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['route']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
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

        WatchTarget::create([
            'origin_city_id' => $originCity->id,
            'origin_airport_id' => $originAirport->id,
            'destination_city_id' => $destinationCity->id,
            'destination_airport_id' => $destinationAirport->id,
            'enabled' => true,
            'monitoring_priority' => 10,
            'date_window_days' => 3,
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
            'travel_date' => '2026-03-22',
            'window_hours' => 24,
            'flight_score' => 8.0,
            'news_score' => 6.0,
            'combined_score' => 7.0,
            'supporting_factors' => ['route' => true],
        ]);

        return [$originAirport, $destinationAirport, $route];
    }
}
