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
            'score' => $this['score'],
            'risk_level' => $this['risk_level'],
            'confidence' => $this['confidence'],
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
