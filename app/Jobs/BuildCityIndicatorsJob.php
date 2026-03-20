<?php

namespace App\Jobs;

use App\Services\Indicators\CityIndicatorBuilder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BuildCityIndicatorsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $windowHours = 24,
    ) {
    }

    public function handle(CityIndicatorBuilder $builder): void
    {
        $builder->build($this->windowHours);
    }
}
