<?php

namespace Database\Seeders;

use App\Models\Airport;
use App\Models\Route;
use App\Models\WatchTarget;
use Illuminate\Database\Seeder;

class DefaultRouteSeeder extends Seeder
{
    /**
     * Seed active inbound routes into the configured base airport.
     */
    public function run(): void
    {
        $baseAirport = Airport::query()
            ->with('city')
            ->where('iata', config('operations.base_airport_iata', 'CUN'))
            ->first();

        if (! $baseAirport) {
            return;
        }

        Airport::query()
            ->with('city')
            ->whereKeyNot($baseAirport->id)
            ->orderBy('iata')
            ->get()
            ->each(function (Airport $originAirport) use ($baseAirport): void {
                $route = Route::query()->updateOrCreate(
                    [
                        'origin_airport_id' => $originAirport->id,
                        'destination_airport_id' => $baseAirport->id,
                    ],
                    [
                        'active' => true,
                        'notes' => sprintf(
                            'Seeded inbound route from %s to %s.',
                            $originAirport->iata,
                            $baseAirport->iata,
                        ),
                    ],
                );

                WatchTarget::query()->updateOrCreate(
                    [
                        'origin_city_id' => $originAirport->city_id,
                        'origin_airport_id' => $originAirport->id,
                        'destination_city_id' => $baseAirport->city_id,
                        'destination_airport_id' => $baseAirport->id,
                    ],
                    [
                        'enabled' => $route->active,
                        'monitoring_priority' => 8,
                        'date_window_days' => 10,
                    ],
                );
            });
    }
}
