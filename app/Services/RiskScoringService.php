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
        $originResolution = $this->resolveEndpoint($origin);
        $destinationResolution = $this->resolveEndpoint($destination);
        $route = $this->resolveRoute($originResolution, $destinationResolution);

        if ($route) {
            $originResolution['airport'] ??= $route->originAirport;
            $originResolution['city'] ??= $route->originAirport?->city;
            $destinationResolution['airport'] ??= $route->destinationAirport;
            $destinationResolution['city'] ??= $route->destinationAirport?->city;
        }

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

        $result = [
            'score' => $finalScore,
            'risk_level' => $riskLevel,
            'confidence' => $confidence,
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
            'explanations' => $this->explanations($components, $riskLevel, $confidence),
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
     * @param  array<string, mixed>  $confidence
     * @return list<string>
     */
    private function explanations(array $components, string $riskLevel, array $confidence): array
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
                'components' => $result['components'],
                'weighted_contributions' => $result['weighted_contributions'],
                'confidence' => $result['confidence'],
                'explanations' => $result['explanations'],
                'profile' => $result['profile'],
            ],
            'generated_at' => now(),
        ]);
    }
}
