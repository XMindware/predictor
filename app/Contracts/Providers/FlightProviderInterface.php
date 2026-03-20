<?php

namespace App\Contracts\Providers;

use App\Data\Providers\FlightData;

interface FlightProviderInterface
{
    /**
     * Search normalized flight data for the supplied criteria.
     *
     * @param  array<string, mixed>  $criteria
     * @return list<FlightData>
     */
    public function searchFlights(array $criteria = []): array;
}
