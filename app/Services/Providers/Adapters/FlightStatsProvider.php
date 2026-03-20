<?php

namespace App\Services\Providers\Adapters;

use App\Contracts\Providers\FlightProviderInterface;
use App\Data\Providers\FlightData;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FlightStatsProvider extends ConfiguredHttpProvider implements FlightProviderInterface
{
    public function searchFlights(array $criteria = []): array
    {
        $originCode = strtoupper((string) ($criteria['origin_code'] ?? ''));
        $destinationCode = strtoupper((string) ($criteria['destination_code'] ?? ''));

        if ($originCode === '' || $destinationCode === '' || $destinationCode === 'ANY') {
            return [];
        }

        $daysAhead = min(
            $this->integerConfig('max_days_ahead', 3),
            max(1, (int) ($criteria['date_window_days'] ?? 1)),
        );

        return collect(range(1, $daysAhead))
            ->flatMap(function (int $offset) use ($criteria, $originCode, $destinationCode): Collection {
                $departureDate = Carbon::now()->addDays($offset);
                $response = $this->client()
                    ->get($this->routeStatusPath($originCode, $destinationCode, $departureDate), [
                        'appId' => $this->requiredCredential('app_id'),
                        'appKey' => $this->requiredCredential('app_key'),
                        'utc' => 'false',
                        'maxFlights' => $this->integerConfig('max_flights', 10),
                    ]);

                if ($response->status() === 404) {
                    return collect();
                }

                $payload = $response->throw()->json();

                return collect($payload['flightStatuses'] ?? [])
                    ->map(fn (array $status): FlightData => $this->mapStatus($status, $criteria, $originCode, $destinationCode));
            })
            ->values()
            ->all();
    }

    private function routeStatusPath(string $originCode, string $destinationCode, Carbon $departureDate): string
    {
        return sprintf(
            '/flex/flightstatus/rest/v2/json/route/status/%s/%s/dep/%d/%d/%d',
            $originCode,
            $destinationCode,
            $departureDate->year,
            $departureDate->month,
            $departureDate->day,
        );
    }

    /**
     * @param  array<string, mixed>  $status
     */
    private function mapStatus(array $status, array $criteria, string $originCode, string $destinationCode): FlightData
    {
        $departureAt = $status['operationalTimes']['scheduledGateDeparture']['dateLocal']
            ?? $status['operationalTimes']['scheduledGateDeparture']['dateUtc']
            ?? null;
        $arrivalAt = $status['operationalTimes']['scheduledGateArrival']['dateLocal']
            ?? $status['operationalTimes']['scheduledGateArrival']['dateUtc']
            ?? null;
        $delayMinutes = $status['delays']['departureGateDelayMinutes']
            ?? $status['delays']['arrivalGateDelayMinutes']
            ?? 0.0;
        $isCancelled = in_array(($status['status'] ?? null), ['C', 'CNX', 'CANCELED', 'CANCELLED'], true);
        $flightNumber = isset($status['flightNumber']) ? (string) $status['flightNumber'] : null;

        return new FlightData(
            providerSlug: $this->provider->slug,
            originCode: $originCode,
            destinationCode: $destinationCode,
            externalReference: sprintf(
                'flight:%s:%s',
                $this->provider->slug,
                (string) ($status['flightId'] ?? implode('-', array_filter([$originCode, $destinationCode, $flightNumber, $departureAt])))
            ),
            departureAt: $departureAt,
            arrivalAt: $arrivalAt,
            carrierCode: $status['carrierFsCode'] ?? null,
            flightNumber: $flightNumber,
            priceAmount: null,
            priceCurrency: null,
            stops: 0,
            meta: [
                'watch_target_id' => $criteria['watch_target_id'] ?? null,
                'status' => $status['status'] ?? null,
                'cancellation_rate' => $isCancelled ? 1.0 : 0.0,
                'delay_average_minutes' => (float) $delayMinutes,
            ],
        );
    }
}
