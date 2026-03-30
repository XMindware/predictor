<?php

namespace Database\Seeders;

use App\Models\Airport;
use App\Models\FlightEvent;
use Database\Seeders\Concerns\SeedsDemoSourceData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class FlightSourceSeeder extends Seeder
{
    use SeedsDemoSourceData;

    public function run(): void
    {
        $provider = $this->provider('flightstats');
        $run = $this->seedRun($provider, 'flights');
        $destinationAirport = $this->baseAirport();
        $originProfiles = [
            'CUN' => ['airline' => 'Y4', 'base_disruption' => 2.8],
            'JFK' => ['airline' => 'DL', 'base_disruption' => 6.2],
            'MEX' => ['airline' => 'AM', 'base_disruption' => 3.9],
            'MIA' => ['airline' => 'AA', 'base_disruption' => 4.8],
            'MID' => ['airline' => 'Y4', 'base_disruption' => 3.4],
            'GDL' => ['airline' => 'AM', 'base_disruption' => 4.1],
            'MTY' => ['airline' => 'VB', 'base_disruption' => 4.4],
            'PVR' => ['airline' => 'Y4', 'base_disruption' => 3.6],
            'SJD' => ['airline' => 'Y4', 'base_disruption' => 4.0],
            'TIJ' => ['airline' => 'VB', 'base_disruption' => 4.7],
            'ATL' => ['airline' => 'DL', 'base_disruption' => 5.8],
            'ORD' => ['airline' => 'UA', 'base_disruption' => 5.2],
            'DFW' => ['airline' => 'AA', 'base_disruption' => 5.0],
            'IAH' => ['airline' => 'UA', 'base_disruption' => 5.1],
            'LAX' => ['airline' => 'AA', 'base_disruption' => 5.4],
            'SFO' => ['airline' => 'UA', 'base_disruption' => 5.3],
            'YYC' => ['airline' => 'WS', 'base_disruption' => 4.9],
            'YUL' => ['airline' => 'AC', 'base_disruption' => 4.8],
            'YYZ' => ['airline' => 'AC', 'base_disruption' => 5.0],
            'YVR' => ['airline' => 'AC', 'base_disruption' => 5.1],
        ];

        foreach ($this->nonBaseAirports() as $originAirport) {
            $profile = $originProfiles[$originAirport->iata] ?? $this->defaultOriginProfile($originAirport);
            $route = $this->route(
                $originAirport->iata,
                $destinationAirport->iata,
                sprintf('Seeded demo route %s-%s into configured base airport.', $originAirport->iata, $destinationAirport->iata),
            );
            $watchTarget = $this->routeWatchTarget($route, 9, 10);

            for ($dayOffset = 1; $dayOffset <= 10; $dayOffset++) {
                $travelDate = Carbon::now()->addDays($dayOffset)->toDateString();
                $eventTime = Carbon::now()->subHours($dayOffset % 6 + 1);
                $delayAverage = 12 + ($dayOffset * 3);
                $cancellationRate = round(0.6 + ($dayOffset * 0.25), 2);
                $disruptionScore = round(min(9.7, $profile['base_disruption'] + ($dayOffset * 0.22)), 2);
                $externalReference = sprintf(
                    'seed:flight:%s:%s:day-%d',
                    $originAirport->iata,
                    $destinationAirport->iata,
                    $dayOffset,
                );

                $payload = $this->seedPayload(
                    $provider,
                    $run,
                    'flights',
                    $externalReference,
                    [
                        'watch_target_id' => $watchTarget->id,
                        'items' => [[
                            'provider_slug' => $provider->slug,
                            'origin_code' => $originAirport->iata,
                            'destination_code' => $destinationAirport->iata,
                            'departure_at' => Carbon::parse($travelDate)->setHour(8)->toIso8601String(),
                            'arrival_at' => Carbon::parse($travelDate)->setHour(10)->addMinutes(45)->toIso8601String(),
                            'carrier_code' => $profile['airline'],
                            'flight_number' => (string) (100 + $dayOffset),
                            'price_amount' => 149 + ($dayOffset * 11),
                            'price_currency' => 'USD',
                            'stops' => $dayOffset % 3 === 0 ? 1 : 0,
                            'meta' => [
                                'delay_average_minutes' => $delayAverage,
                                'cancellation_rate' => $cancellationRate,
                            ],
                        ]],
                    ],
                    $eventTime->toDateTimeString(),
                );

                FlightEvent::query()->updateOrCreate(
                    [
                        'raw_payload_id' => $payload->id,
                    ],
                    [
                        'route_id' => $route->id,
                        'origin_airport_id' => $route->origin_airport_id,
                        'destination_airport_id' => $route->destination_airport_id,
                        'airline_code' => $profile['airline'],
                        'event_time' => $eventTime,
                        'travel_date' => $travelDate,
                        'cancellation_rate' => $cancellationRate,
                        'delay_average_minutes' => $delayAverage,
                        'disruption_score' => $disruptionScore,
                        'summary' => sprintf(
                            'Seeded disruption outlook for %s to %s on %s.',
                            $originAirport->iata,
                            $destinationAirport->iata,
                            $travelDate
                        ),
                        'source_provider_id' => $provider->id,
                    ],
                );
            }
        }
    }

    /**
     * @return array{airline: string, base_disruption: float}
     */
    private function defaultOriginProfile(Airport $originAirport): array
    {
        return match ($originAirport->country?->name) {
            'Mexico' => ['airline' => 'AM', 'base_disruption' => 4.2],
            'United States' => ['airline' => 'AA', 'base_disruption' => 5.0],
            'Canada' => ['airline' => 'AC', 'base_disruption' => 4.9],
            default => ['airline' => 'AM', 'base_disruption' => 4.5],
        };
    }
}
