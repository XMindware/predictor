<?php

namespace App\Jobs;

use App\Models\RawProviderPayload;
use App\Services\Providers\Normalizers\NewsPayloadNormalizer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NormalizeNewsPayloadJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $rawProviderPayloadId,
    ) {
    }

    public function handle(NewsPayloadNormalizer $normalizer): void
    {
        $payload = RawProviderPayload::query()->findOrFail($this->rawProviderPayloadId);

        $normalizer->normalize($payload);
    }
}
