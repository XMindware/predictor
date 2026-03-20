<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RiskAssessmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
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
            'factors' => [
                'components' => $this['components'],
                'weighted_contributions' => $this['weighted_contributions'],
            ],
            'summaries' => $this['explanations'],
            'resolved' => $this['resolved'],
            'snapshot' => $this['snapshot'],
            'query' => $this['query'],
        ];
    }
}
