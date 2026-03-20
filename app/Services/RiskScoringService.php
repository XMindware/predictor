<?php

namespace App\Services;

use App\Models\Airport;
use App\Models\AirportIndicator;
use App\Models\City;
use App\Models\CityIndicator;
use App\Models\RiskQuerySnapshot;
use App\Models\Route;
use App\Models\RouteIndicator;
use App\Models\ScoringProfile;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use RuntimeException;

class RiskScoringService
{
    /**
     * @param  Airport|City|array<string, mixed>|string  $origin
     * @param  Airport|City|array<string, mixed>|string  $destination
     * @param  CarbonInterface|string|null  $travelDate
     * @return array<string, mixed>
     */
    public function calculate(Airport|City|array|string $origin, Airport|City|array|string $destination, CarbonInterface|string|null $travelDate): array
    {
        $profile = ScoringProfile::query()->active()->latest('id')->first();

        if (! $profile) {
            throw new RuntimeException('An active scoring profile is required to calculate risk.');
        }

        $normalizedTravelDate = $this->normalizeTravelDate($travelDate);
        $context = $this->resolveContext($origin, $destination);
        $originResolution = $context['origin'];
        $destinationResolution = $context['destination'];
        $route = $context['route'];

        $originAirportIndicator = $this->latestAirportIndicator($originResolution['airport'] ?? null);
        $destinationAirportIndicator = $this->latestAirportIndicator($destinationResolution['airport'] ?? null);
        $originCityIndicator = $this->latestCityIndicator($originResolution['city'] ?? null);
        $destinationCityIndicator = $this->latestCityIndicator($destinationResolution['city'] ?? null);
        $routeIndicator = $this->latestRouteIndicator($route, $normalizedTravelDate);

        $components = [
            'flight' => $this->flightComponent($routeIndicator, $originAirportIndicator, $destinationAirportIndicator),
            'weather' => $this->weatherComponent(
                $originAirportIndicator,
                $destinationAirportIndicator,
                $originCityIndicator,
                $destinationCityIndicator
            ),
            'news' => $this->newsComponent(
                $routeIndicator,
                $originAirportIndicator,
                $destinationAirportIndicator,
                $originCityIndicator,
                $destinationCityIndicator
            ),
            'date_proximity' => $this->dateProximityComponent($normalizedTravelDate),
        ];

        $weights = $profile->weights;
        $weightedContributions = [];
        $finalScore = 0.0;

        foreach ($weights as $factor => $weight) {
            $componentScore = (float) ($components[$factor]['score'] ?? 0);
            $weightedContributions[$factor] = round($componentScore * (float) $weight, 2);
            $finalScore += $weightedContributions[$factor];
        }

        $finalScore = round($finalScore, 2);
        $riskLevel = $this->riskLevel($finalScore, $profile->thresholds);
        $confidence = $this->confidence($components, $weights, $normalizedTravelDate !== null);
        $freshness = $this->freshness($components);
        $drivers = $this->drivers($components, $weightedContributions);
        $probableNoShowUplift = $this->probableNoShowUplift($finalScore, $confidence);
        $recommendedAction = $this->recommendedAction($riskLevel, $confidence, $freshness, $drivers);

        $result = [
            'assessment_type' => 'short_term_travel_disruption_risk',
            'scoring_mode' => 'deterministic_rules',
            'product_framing' => 'Estimate of short-term travel disruption risk and probable no-show uplift, not a deterministic no-show prediction.',
            'score' => $finalScore,
            'risk_level' => $riskLevel,
            'confidence' => $confidence,
            'freshness' => $freshness,
            'profile' => [
                'name' => $profile->name,
                'version' => $profile->version,
                'weights' => $weights,
                'thresholds' => $profile->thresholds,
            ],
            'resolved' => [
                'origin' => $this->resolvedPayload($originResolution),
                'destination' => $this->resolvedPayload($destinationResolution),
                'route' => $route ? [
                    'id' => $route->id,
                    'origin_airport_id' => $route->origin_airport_id,
                    'destination_airport_id' => $route->destination_airport_id,
                ] : null,
                'travel_date' => $normalizedTravelDate?->toDateString(),
            ],
            'components' => $components,
            'weighted_contributions' => $weightedContributions,
            'drivers' => $drivers,
            'probable_no_show_uplift' => $probableNoShowUplift,
            'recommended_action' => $recommendedAction,
            'explanations' => $this->explanations(
                $components,
                $riskLevel,
                $confidence,
                $freshness,
                $probableNoShowUplift,
                $recommendedAction
            ),
        ];

        $snapshot = $this->persistSnapshot(
            $result,
            $originResolution,
            $destinationResolution,
            $route,
            $normalizedTravelDate
        );

        $result['snapshot'] = [
            'persisted' => $snapshot !== null,
            'id' => $snapshot?->id,
            'generated_at' => $snapshot?->generated_at?->toIso8601String(),
        ];

        return $result;
    }

    /**
     * @param  Airport|City|array<string, mixed>|string  $origin
     * @param  Airport|City|array<string, mixed>|string  $destination
     * @return array{
     *     origin: array{airport: ?Airport, city: ?City},
     *     destination: array{airport: ?Airport, city: ?City},
     *     route: ?Route
     * }
     */
    public function resolveContext(Airport|City|array|string $origin, Airport|City|array|string $destination): array
    {
        $originResolution = $this->resolveEndpoint($origin);
        $destinationResolution = $this->resolveEndpoint($destination);
        $route = $this->resolveRoute($originResolution, $destinationResolution);

        if ($route) {
            $originResolution['airport'] ??= $route->originAirport;
            $originResolution['city'] ??= $route->originAirport?->city;
            $destinationResolution['airport'] ??= $route->destinationAirport;
            $destinationResolution['city'] ??= $route->destinationAirport?->city;
        }

        return [
            'origin' => $originResolution,
            'destination' => $destinationResolution,
            'route' => $route,
        ];
    }

    /**
     * @param  Airport|City|array<string, mixed>|string  $input
     * @return array{airport: ?Airport, city: ?City}
     */
    private function resolveEndpoint(Airport|City|array|string $input): array
    {
        if ($input instanceof Airport) {
            return [
                'airport' => $input->loadMissing('city'),
                'city' => $input->city,
            ];
        }

        if ($input instanceof City) {
            return [
                'airport' => null,
                'city' => $input,
            ];
        }

        if (is_string($input)) {
            return $this->resolveFromString($input);
        }

        return $this->resolveFromArray($input);
    }

    /**
     * @return array{airport: ?Airport, city: ?City}
     */
    private function resolveFromString(string $input): array
    {
        $normalized = trim($input);

        $airport = Airport::query()
            ->with('city')
            ->whereRaw('lower(iata) = ?', [strtolower($normalized)])
            ->orWhereRaw('lower(icao) = ?', [strtolower($normalized)])
            ->orWhereRaw('lower(name) = ?', [strtolower($normalized)])
            ->first();

        if ($airport) {
            return [
                'airport' => $airport,
                'city' => $airport->city,
            ];
        }

        $city = City::query()
            ->whereRaw('lower(name) = ?', [strtolower($normalized)])
            ->first();

        if ($city) {
            return [
                'airport' => null,
                'city' => $city,
            ];
        }

        throw new InvalidArgumentException(sprintf('Unable to resolve location "%s".', $input));
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{airport: ?Airport, city: ?City}
     */
    private function resolveFromArray(array $input): array
    {
        $airport = null;
        $city = null;

        if (isset($input['airport_id'])) {
            $airport = Airport::query()->with('city')->find($input['airport_id']);
        } elseif (isset($input['airport_iata'])) {
            $airport = Airport::query()
                ->with('city')
                ->whereRaw('lower(iata) = ?', [strtolower((string) $input['airport_iata'])])
                ->first();
        } elseif (isset($input['airport'])) {
            return $this->resolveEndpoint((string) $input['airport']);
        }

        if (isset($input['city_id'])) {
            $city = City::query()->find($input['city_id']);
        } elseif (isset($input['city'])) {
            $city = City::query()
                ->whereRaw('lower(name) = ?', [strtolower((string) $input['city'])])
                ->first();
        }

        if ($airport) {
            $city ??= $airport->city;
        }

        if (! $airport && ! $city) {
            throw new InvalidArgumentException('Unable to resolve array endpoint input.');
        }

        return [
            'airport' => $airport,
            'city' => $city,
        ];
    }

    /**
     * @param  array{airport: ?Airport, city: ?City}  $origin
     * @param  array{airport: ?Airport, city: ?City}  $destination
     */
    private function resolveRoute(array $origin, array $destination): ?Route
    {
        if ($origin['airport'] && $destination['airport']) {
            return Route::query()
                ->with(['originAirport.city', 'destinationAirport.city'])
                ->where('origin_airport_id', $origin['airport']->id)
                ->where('destination_airport_id', $destination['airport']->id)
                ->first();
        }

        if (! $origin['city'] || ! $destination['city']) {
            return null;
        }

        return Route::query()
            ->with(['originAirport.city', 'destinationAirport.city'])
            ->whereHas('originAirport', fn ($query) => $query->where('city_id', $origin['city']->id))
            ->whereHas('destinationAirport', fn ($query) => $query->where('city_id', $destination['city']->id))
            ->where('active', true)
            ->first();
    }

    private function latestAirportIndicator(?Airport $airport): ?AirportIndicator
    {
        if (! $airport) {
            return null;
        }

        return $airport->indicators()->latest('as_of')->latest('id')->first();
    }

    private function latestCityIndicator(?City $city): ?CityIndicator
    {
        if (! $city) {
            return null;
        }

        return $city->indicators()->latest('as_of')->latest('id')->first();
    }

    private function latestRouteIndicator(?Route $route, ?CarbonInterface $travelDate): ?RouteIndicator
    {
        if (! $route) {
            return null;
        }

        if ($travelDate) {
            $datedIndicator = $route->indicators()
                ->whereDate('travel_date', $travelDate->toDateString())
                ->latest('as_of')
                ->latest('id')
                ->first();

            if ($datedIndicator) {
                return $datedIndicator;
            }
        }

        return $route->indicators()
            ->whereNull('travel_date')
            ->latest('as_of')
            ->latest('id')
            ->first();
    }

    private function flightComponent(
        ?RouteIndicator $routeIndicator,
        ?AirportIndicator $originAirportIndicator,
        ?AirportIndicator $destinationAirportIndicator,
    ): array {
        if ($routeIndicator) {
            return [
                'score' => $routeIndicator->flight_score,
                'source' => $routeIndicator->travel_date ? 'route_indicator:travel_date' : 'route_indicator:overall',
                'data_present' => true,
                'as_of' => $routeIndicator->as_of?->toIso8601String(),
            ];
        }

        $airportScores = collect([$originAirportIndicator?->flight_score, $destinationAirportIndicator?->flight_score])
            ->filter(fn ($score) => $score !== null)
            ->map(fn ($score) => (float) $score)
            ->values();

        return [
            'score' => round((float) ($airportScores->avg() ?? 0), 2),
            'source' => $airportScores->isEmpty() ? 'missing' : 'airport_indicators',
            'data_present' => $airportScores->isNotEmpty(),
            'as_of' => $originAirportIndicator?->as_of?->toIso8601String() ?? $destinationAirportIndicator?->as_of?->toIso8601String(),
        ];
    }

    private function weatherComponent(
        ?AirportIndicator $originAirportIndicator,
        ?AirportIndicator $destinationAirportIndicator,
        ?CityIndicator $originCityIndicator,
        ?CityIndicator $destinationCityIndicator,
    ): array {
        $scores = collect([
            $originAirportIndicator?->weather_score ?? $originCityIndicator?->weather_score,
            $destinationAirportIndicator?->weather_score ?? $destinationCityIndicator?->weather_score,
        ])
            ->filter(fn ($score) => $score !== null)
            ->map(fn ($score) => (float) $score)
            ->values();

        $usesAirportData = $originAirportIndicator || $destinationAirportIndicator;

        return [
            'score' => round((float) ($scores->avg() ?? 0), 2),
            'source' => $scores->isEmpty() ? 'missing' : ($usesAirportData ? 'airport_indicators' : 'city_indicators'),
            'data_present' => $scores->isNotEmpty(),
            'as_of' => $originAirportIndicator?->as_of?->toIso8601String()
                ?? $destinationAirportIndicator?->as_of?->toIso8601String()
                ?? $originCityIndicator?->as_of?->toIso8601String()
                ?? $destinationCityIndicator?->as_of?->toIso8601String(),
        ];
    }

    private function newsComponent(
        ?RouteIndicator $routeIndicator,
        ?AirportIndicator $originAirportIndicator,
        ?AirportIndicator $destinationAirportIndicator,
        ?CityIndicator $originCityIndicator,
        ?CityIndicator $destinationCityIndicator,
    ): array {
        if ($routeIndicator) {
            return [
                'score' => $routeIndicator->news_score,
                'source' => 'route_indicator',
                'data_present' => true,
                'as_of' => $routeIndicator->as_of?->toIso8601String(),
            ];
        }

        $scores = collect([
            $originAirportIndicator?->news_score ?? $originCityIndicator?->news_score,
            $destinationAirportIndicator?->news_score ?? $destinationCityIndicator?->news_score,
        ])
            ->filter(fn ($score) => $score !== null)
            ->map(fn ($score) => (float) $score)
            ->values();

        $usesAirportData = $originAirportIndicator || $destinationAirportIndicator;

        return [
            'score' => round((float) ($scores->avg() ?? 0), 2),
            'source' => $scores->isEmpty() ? 'missing' : ($usesAirportData ? 'airport_indicators' : 'city_indicators'),
            'data_present' => $scores->isNotEmpty(),
            'as_of' => $originAirportIndicator?->as_of?->toIso8601String()
                ?? $destinationAirportIndicator?->as_of?->toIso8601String()
                ?? $originCityIndicator?->as_of?->toIso8601String()
                ?? $destinationCityIndicator?->as_of?->toIso8601String(),
        ];
    }

    private function dateProximityComponent(?CarbonInterface $travelDate): array
    {
        if (! $travelDate) {
            return [
                'score' => 0.0,
                'source' => 'missing',
                'data_present' => false,
                'days_until' => null,
            ];
        }

        $daysUntil = max(0, now()->startOfDay()->diffInDays($travelDate->copy()->startOfDay(), false));
        $score = round(max(0, 10 - (min(30, $daysUntil) / 3)), 2);

        return [
            'score' => $score,
            'source' => 'travel_date',
            'data_present' => true,
            'days_until' => $daysUntil,
        ];
    }

    /**
     * @param  array<string, mixed>  $thresholds
     */
    private function riskLevel(float $score, array $thresholds): string
    {
        $high = (float) ($thresholds['high'] ?? 8);
        $medium = (float) ($thresholds['medium'] ?? 6);
        $low = (float) ($thresholds['low'] ?? 3);

        return match (true) {
            $score >= $high => 'high',
            $score >= $medium => 'medium',
            $score >= $low => 'low',
            default => 'minimal',
        };
    }

    /**
     * @param  array<string, array<string, mixed>>  $components
     * @param  array<string, mixed>  $weights
     * @return array<string, mixed>
     */
    private function confidence(array $components, array $weights, bool $hasTravelDate): array
    {
        $relevantWeights = collect($weights)
            ->filter(function (mixed $weight, string $factor) use ($hasTravelDate): bool {
                return $factor !== 'date_proximity' || $hasTravelDate;
            });
        $availableWeight = $relevantWeights
            ->filter(fn (mixed $weight, string $factor) => (bool) ($components[$factor]['data_present'] ?? false))
            ->sum(fn (mixed $weight) => (float) $weight);
        $possibleWeight = (float) $relevantWeights->sum(fn (mixed $weight) => (float) $weight);
        $score = $possibleWeight > 0 ? round($availableWeight / $possibleWeight, 2) : 0.0;

        return [
            'score' => $score,
            'level' => match (true) {
                $score >= 0.75 => 'high',
                $score >= 0.5 => 'medium',
                default => 'low',
            },
            'available_weight' => round($availableWeight, 2),
            'possible_weight' => round($possibleWeight, 2),
            'coverage' => collect($components)
                ->map(fn (array $component) => (bool) ($component['data_present'] ?? false))
                ->all(),
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $components
     * @return array<string, mixed>
     */
    private function freshness(array $components): array
    {
        $ages = collect($components)
            ->reject(fn (array $component, string $factor): bool => $factor === 'date_proximity')
            ->map(function (array $component, string $factor): array {
                $asOf = $component['as_of'] ?? null;

                if (! $asOf) {
                    return [
                        'factor' => $factor,
                        'as_of' => null,
                        'minutes_old' => null,
                    ];
                }

                $timestamp = Carbon::parse((string) $asOf);

                return [
                    'factor' => $factor,
                    'as_of' => $timestamp->toIso8601String(),
                    'minutes_old' => now()->diffInMinutes($timestamp, false) * -1,
                ];
            })
            ->values();

        $minutes = $ages
            ->pluck('minutes_old')
            ->filter(fn (mixed $value): bool => $value !== null)
            ->map(fn (mixed $value): int => max(0, (int) $value))
            ->values();

        if ($minutes->isEmpty()) {
            return [
                'level' => 'unknown',
                'latest_signal_at' => null,
                'stalest_signal_at' => null,
                'minutes_since_latest_signal' => null,
                'minutes_since_stalest_signal' => null,
                'component_ages' => $ages->keyBy('factor')->all(),
            ];
        }

        $latestMinutes = (int) $minutes->min();
        $stalestMinutes = (int) $minutes->max();
        $knownAges = $ages
            ->filter(fn (array $age): bool => $age['minutes_old'] !== null)
            ->values();

        return [
            'level' => match (true) {
                $stalestMinutes <= 180 => 'fresh',
                $stalestMinutes <= 720 => 'aging',
                default => 'stale',
            },
            'latest_signal_at' => $knownAges
                ->sortBy('minutes_old')
                ->first()['as_of'] ?? null,
            'stalest_signal_at' => $knownAges
                ->sortByDesc('minutes_old')
                ->first()['as_of'] ?? null,
            'minutes_since_latest_signal' => $latestMinutes,
            'minutes_since_stalest_signal' => $stalestMinutes,
            'component_ages' => $ages->keyBy('factor')->all(),
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $components
     * @param  array<string, float>  $weightedContributions
     * @return list<array<string, mixed>>
     */
    private function drivers(array $components, array $weightedContributions): array
    {
        return collect($weightedContributions)
            ->map(function (float $contribution, string $factor) use ($components): array {
                $component = $components[$factor] ?? [];

                return [
                    'factor' => $factor,
                    'component_score' => round((float) ($component['score'] ?? 0), 2),
                    'weighted_contribution' => round($contribution, 2),
                    'source' => (string) ($component['source'] ?? 'unknown'),
                    'data_present' => (bool) ($component['data_present'] ?? false),
                    'as_of' => $component['as_of'] ?? null,
                ];
            })
            ->sortByDesc('weighted_contribution')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $confidence
     * @return array<string, mixed>
     */
    private function probableNoShowUplift(float $score, array $confidence): array
    {
        $estimate = round($score * 0.3, 1);
        $spread = match ($confidence['level'] ?? 'low') {
            'high' => 0.4,
            'medium' => 0.8,
            default => 1.2,
        };

        return [
            'estimate_percent' => $estimate,
            'range_percent' => [
                'low' => round(max(0, $estimate - $spread), 1),
                'high' => round($estimate + $spread, 1),
            ],
            'method' => 'heuristic_from_disruption_risk',
            'framing' => 'Directional estimate of probable no-show uplift from short-term disruption risk, not a deterministic no-show forecast.',
        ];
    }

    /**
     * @param  array<string, mixed>  $confidence
     * @param  array<string, mixed>  $freshness
     * @param  list<array<string, mixed>>  $drivers
     * @return array<string, mixed>
     */
    private function recommendedAction(string $riskLevel, array $confidence, array $freshness, array $drivers): array
    {
        $primaryDriver = $drivers[0]['factor'] ?? null;

        if (($freshness['level'] ?? 'unknown') === 'stale' || ($confidence['level'] ?? 'low') === 'low') {
            return [
                'code' => 'manual_review',
                'summary' => 'Review this market manually before changing inventory or pricing because coverage or freshness is weak.',
                'primary_driver' => $primaryDriver,
            ];
        }

        return match ($riskLevel) {
            'high' => [
                'code' => 'tighten_exposure',
                'summary' => 'Reduce exposure on this market and prepare for disruption-driven no-show uplift.',
                'primary_driver' => $primaryDriver,
            ],
            'medium' => [
                'code' => 'watch_and_adjust',
                'summary' => 'Keep this market under active watch and adjust commercial decisions if the main drivers worsen.',
                'primary_driver' => $primaryDriver,
            ],
            default => [
                'code' => 'hold_strategy',
                'summary' => 'Hold the current strategy and keep monitoring for changes in disruption signals.',
                'primary_driver' => $primaryDriver,
            ],
        };
    }

    /**
     * @param  array<string, array<string, mixed>>  $components
     * @param  array<string, mixed>  $confidence
     * @return list<string>
     */
    private function explanations(
        array $components,
        string $riskLevel,
        array $confidence,
        array $freshness,
        array $probableNoShowUplift,
        array $recommendedAction,
    ): array
    {
        $messages = [];

        foreach ($components as $factor => $component) {
            if (! ($component['data_present'] ?? false)) {
                $messages[] = sprintf('No recent %s signal was available for this itinerary.', str_replace('_', ' ', $factor));

                continue;
            }

            $messages[] = sprintf(
                '%s contributed %.2f using %s.',
                ucfirst(str_replace('_', ' ', $factor)),
                (float) ($component['score'] ?? 0),
                (string) ($component['source'] ?? 'unknown source')
            );
        }

        $messages[] = sprintf('Derived risk level is %s.', $riskLevel);
        $messages[] = sprintf(
            'Confidence is %s with %.0f%% weighted data coverage.',
            $confidence['level'],
            ((float) ($confidence['score'] ?? 0)) * 100
        );
        $messages[] = match ($freshness['level'] ?? 'unknown') {
            'fresh' => sprintf(
                'Data freshness is strong; the stalest contributing signal is %d minutes old.',
                (int) ($freshness['minutes_since_stalest_signal'] ?? 0)
            ),
            'aging' => sprintf(
                'Data is aging; the stalest contributing signal is %d minutes old.',
                (int) ($freshness['minutes_since_stalest_signal'] ?? 0)
            ),
            'stale' => sprintf(
                'Data is stale; the stalest contributing signal is %d minutes old.',
                (int) ($freshness['minutes_since_stalest_signal'] ?? 0)
            ),
            default => 'Data freshness could not be established from the available signals.',
        };
        $messages[] = sprintf(
            'Probable no-show uplift is estimated at %.1f%%, with a directional range of %.1f%% to %.1f%%.',
            (float) ($probableNoShowUplift['estimate_percent'] ?? 0),
            (float) ($probableNoShowUplift['range_percent']['low'] ?? 0),
            (float) ($probableNoShowUplift['range_percent']['high'] ?? 0),
        );
        $messages[] = sprintf(
            'Recommended action: %s.',
            (string) ($recommendedAction['summary'] ?? 'Continue monitoring this itinerary.')
        );

        return $messages;
    }

    /**
     * @param  array{airport: ?Airport, city: ?City}  $resolution
     * @return array<string, mixed>
     */
    private function resolvedPayload(array $resolution): array
    {
        return [
            'airport' => $resolution['airport'] ? [
                'id' => $resolution['airport']->id,
                'iata' => $resolution['airport']->iata,
                'name' => $resolution['airport']->name,
            ] : null,
            'city' => $resolution['city'] ? [
                'id' => $resolution['city']->id,
                'name' => $resolution['city']->name,
            ] : null,
        ];
    }

    private function normalizeTravelDate(CarbonInterface|string|null $travelDate): ?CarbonInterface
    {
        if ($travelDate === null || $travelDate === '') {
            return null;
        }

        return $travelDate instanceof CarbonInterface
            ? $travelDate
            : Carbon::parse($travelDate)->startOfDay();
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array{airport: ?Airport, city: ?City}  $originResolution
     * @param  array{airport: ?Airport, city: ?City}  $destinationResolution
     */
    private function persistSnapshot(
        array $result,
        array $originResolution,
        array $destinationResolution,
        ?Route $route,
        ?CarbonInterface $travelDate,
    ): ?RiskQuerySnapshot {
        if (! $travelDate) {
            return null;
        }

        return RiskQuerySnapshot::query()->create([
            'origin_city_id' => $originResolution['city']?->id,
            'origin_airport_id' => $originResolution['airport']?->id,
            'destination_city_id' => $destinationResolution['city']?->id,
            'destination_airport_id' => $destinationResolution['airport']?->id,
            'route_id' => $route?->id,
            'travel_date' => $travelDate->toDateString(),
            'score' => $result['score'],
            'risk_level' => $result['risk_level'],
            'confidence_level' => $result['confidence']['level'],
            'factors' => [
                'assessment_type' => $result['assessment_type'],
                'scoring_mode' => $result['scoring_mode'],
                'product_framing' => $result['product_framing'],
                'scope' => $result['scope'] ?? null,
                'components' => $result['components'],
                'weighted_contributions' => $result['weighted_contributions'],
                'confidence' => $result['confidence'],
                'freshness' => $result['freshness'],
                'drivers' => $result['drivers'],
                'probable_no_show_uplift' => $result['probable_no_show_uplift'],
                'recommended_action' => $result['recommended_action'],
                'explanations' => $result['explanations'],
                'profile' => $result['profile'],
            ],
            'generated_at' => now(),
        ]);
    }
}
