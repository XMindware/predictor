<?php

namespace App\Contracts\Providers;

use App\Data\Providers\WeatherData;

interface WeatherProviderInterface
{
    /**
     * Fetch normalized weather data for the supplied criteria.
     *
     * @param  array<string, mixed>  $criteria
     * @return list<WeatherData>
     */
    public function fetchWeather(array $criteria = []): array;
}
