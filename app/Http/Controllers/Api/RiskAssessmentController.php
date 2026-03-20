<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RiskAssessmentRequest;
use App\Http\Resources\Api\RiskAssessmentResource;
use App\Services\QueryCacheService;
use App\Services\RiskScoringService;

class RiskAssessmentController extends Controller
{
    public function __invoke(
        RiskAssessmentRequest $request,
        RiskScoringService $riskScoringService,
        QueryCacheService $queryCacheService,
    ): RiskAssessmentResource
    {
        $validated = $request->validated();
        $assessment = $queryCacheService->remember(
            'risk-assessment',
            $validated,
            function () use ($validated, $riskScoringService): array {
                $origin = $validated['origin_airport'] ?? $validated['origin_city'];
                $destination = $validated['destination_airport'] ?? $validated['destination_city'];
                $assessment = $riskScoringService->calculate(
                    $origin,
                    $destination,
                    $validated['travel_date'],
                );
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
