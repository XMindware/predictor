<?php

namespace App\Jobs;

use App\Services\Indicators\AirportIndicatorBuilder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BuildAirportIndicatorsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $windowHours = 24,
    ) {
    }

    public function handle(AirportIndicatorBuilder $builder): void
    {
        $builder->build($this->windowHours);
    }
}
