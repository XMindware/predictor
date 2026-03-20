<?php

namespace App\Services\Providers\Adapters;

use App\Contracts\Providers\FlightProviderInterface;
use App\Data\Providers\FlightData;

class StubFlightProvider implements FlightProviderInterface
{
    public function searchFlights(array $criteria = []): array
    {
        $originCode = (string) ($criteria['origin_code'] ?? 'UNKNOWN');
        $destinationCode = (string) ($criteria['destination_code'] ?? 'UNKNOWN');
        $providerSlug = (string) ($criteria['provider_slug'] ?? 'stub-flight');

        return [
            new FlightData(
                providerSlug: $providerSlug,
                originCode: $originCode,
                destinationCode: $destinationCode,
                externalReference: sprintf('flight:%s:%s-%s', $providerSlug, $originCode, $destinationCode),
                departureAt: now()->addDay()->setHour(8)->toIso8601String(),
                arrivalAt: now()->addDay()->setHour(11)->toIso8601String(),
                carrierCode: 'XX',
                flightNumber: '100',
                priceAmount: 199.99,
                priceCurrency: 'USD',
                stops: 0,
                meta: [
                    'date_window_days' => $criteria['date_window_days'] ?? null,
                    'watch_target_id' => $criteria['watch_target_id'] ?? null,
                ],
            ),
        ];
    }
}
