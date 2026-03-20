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
            'assessment_type' => $this['assessment_type'],
            'scoring_mode' => $this['scoring_mode'],
            'product_framing' => $this['product_framing'],
            'scope' => $this['scope'],
            'score' => $this['score'],
            'risk_level' => $this['risk_level'],
            'confidence' => $this['confidence'],
            'freshness' => $this['freshness'],
            'drivers' => $this['drivers'],
            'probable_no_show_uplift' => $this['probable_no_show_uplift'],
            'recommended_action' => $this['recommended_action'],
            'factors' => $this['factors'],
            'summaries' => $this['summaries'],
            'snapshot' => $this['snapshot'],
        ];
    }
}
