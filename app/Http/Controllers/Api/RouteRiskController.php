<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RouteRiskIndexRequest;
use App\Http\Resources\Api\RouteRiskResource;
use App\Services\QueryCacheService;
use App\Services\RouteRiskRankingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class RouteRiskController extends Controller
{
    public function __invoke(
        RouteRiskIndexRequest $request,
        RouteRiskRankingService $routeRiskRankingService,
        QueryCacheService $queryCacheService,
    ): JsonResponse
    {
        $validated = $request->validated();

        try {
            $ranking = $queryCacheService->remember(
                'route-risk',
                $validated,
                fn (): array => $routeRiskRankingService->rank(
                    $validated['destination'],
                    $validated['date'],
                    $validated['limit'] ?? 10,
                ),
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'destination' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'data' => RouteRiskResource::collection(collect($ranking['data']))->resolve(),
            'meta' => [
                'destination' => $ranking['destination'],
                'travel_date' => $ranking['travel_date'],
                'count' => $ranking['count'],
                'scope' => $ranking['scope'],
            ],
        ]);
    }
}
