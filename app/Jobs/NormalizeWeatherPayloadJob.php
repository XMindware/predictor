<?php

namespace App\Jobs;

use App\Models\RawProviderPayload;
use App\Services\Providers\Normalizers\WeatherPayloadNormalizer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NormalizeWeatherPayloadJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $rawProviderPayloadId,
    ) {
    }

    public function handle(WeatherPayloadNormalizer $normalizer): void
    {
        $payload = RawProviderPayload::query()->findOrFail($this->rawProviderPayloadId);

        $normalizer->normalize($payload);
    }
}
