<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RiskAssessmentRequest;
use App\Http\Resources\Api\RiskAssessmentResource;
use App\Services\MonitoredRouteService;
use App\Services\QueryCacheService;
use App\Services\RiskScoringService;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class RiskAssessmentController extends Controller
{
    public function __invoke(
        RiskAssessmentRequest $request,
        RiskScoringService $riskScoringService,
        MonitoredRouteService $monitoredRouteService,
        QueryCacheService $queryCacheService,
    ): RiskAssessmentResource
    {
        $validated = $request->validated();
        $origin = $validated['origin_airport'] ?? $validated['origin_city'];
        $destination = $validated['destination_airport'] ?? $validated['destination_city'];

        try {
            $context = $riskScoringService->resolveContext($origin, $destination);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'route' => $exception->getMessage(),
            ]);
        }

        if (! $context['route'] || ! $monitoredRouteService->isMonitored($context['route'])) {
            throw ValidationException::withMessages([
                'route' => 'V1 risk assessment only supports active monitored routes.',
            ]);
        }

        $assessment = $queryCacheService->remember(
            'risk-assessment',
            $validated,
            function () use ($validated, $riskScoringService, $origin, $destination): array {
                $assessment = $riskScoringService->calculate(
                    $origin,
                    $destination,
                    $validated['travel_date'],
                );
                $assessment['scope'] = [
                    'travel_window_hours' => (int) config('operations.v1_risk_window_hours', 72),
                    'monitored_routes_only' => true,
                    'entity_level' => 'route_and_airport',
                ];
                $assessment['query'] = [
                    'origin_city' => $validated['origin_city'] ?? null,
                    'origin_airport' => $validated['origin_airport'] ?? null,
                    'destination_city' => $validated['destination_city'] ?? null,
                    'destination_airport' => $validated['destination_airport'] ?? null,
                    'travel_date' => $validated['travel_date'],
                    'airline_code' => $validated['airline_code'] ?? null,
                ];

                return $assessment;
            },
        );

        return new RiskAssessmentResource($assessment);
    }
}
