<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RouteRiskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'rank' => $this['rank'],
            'route_id' => $this['route_id'],
            'origin' => $this['origin'],
            'destination' => $this['destination'],
            'travel_date' => $this['travel_date'],
            'score' => $this['score'],
            'risk_level' => $this['risk_level'],
            'confidence' => $this['confidence'],
            'factors' => $this['factors'],
            'summaries' => $this['summaries'],
            'snapshot' => $this['snapshot'],
        ];
    }
}
