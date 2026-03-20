<?php

namespace App\Jobs;

use App\Services\Indicators\RouteIndicatorBuilder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BuildRouteIndicatorsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $windowHours = 24,
    ) {
    }

    public function handle(RouteIndicatorBuilder $builder): void
    {
        $builder->build($this->windowHours);
    }
}
