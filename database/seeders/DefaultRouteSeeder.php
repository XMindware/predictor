<?php

namespace Database\Seeders;

use App\Models\Airport;
use App\Models\Route;
use App\Models\WatchTarget;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DefaultRouteSeeder extends Seeder
{
    /**
     * Seed active inbound routes into every configured base airport.
     *
     * Uses the `base_airports` config key (e.g. ['CUN', 'SJD']) so that
     * multiple destination markets are seeded in one pass.
     *
     * Priority logic
     * ──────────────
     *  - First-listed base airport is "primary" → monitoring_priority 10
     *  - Additional base airports → priority 8
     *  - Routes between two base airports → priority 9 (cross-destination)
     */
    public function run(): void
    {
        /** @var list<string> $baseIatas */
        $baseIatas = collect(config('operations.base_airports', ['CUN']));

        if ($baseIatas->isEmpty()) {
            $this->command?->warn('[DefaultRouteSeeder] No base airports configured — skipping.');

            return;
        }

        /** @var Collection<string, Airport> $baseAirports */
        $baseAirports = Airport::query()
            ->with('city')
            ->whereIn('iata', $baseIatas->all())
            ->get()
            ->keyBy('iata');

        if ($baseAirports->isEmpty()) {
            $this->command?->warn('[DefaultRouteSeeder] None of the configured base airports exist in the DB yet.');

            return;
        }

        $allAirports   = Airport::query()->with('city')->get();
        $seededRoutes  = 0;
        $seededTargets = 0;

        foreach ($baseAirports as $baseIata => $baseAirport) {
            $isPrimary = $baseIatas->first() === $baseIata;
            $priority  = $isPrimary ? 10 : 8;

            foreach ($allAirports as $originAirport) {
                // Skip self-routes
                if ($originAirport->id === $baseAirport->id) {
                    continue;
                }

                // Cross-destination routes get a slightly higher priority
                $routePriority = $baseAirports->has($originAirport->iata)
                    ? 9
                    : $priority;

                $route = Route::query()->updateOrCreate(
                    [
                        'origin_airport_id'      => $originAirport->id,
                        'destination_airport_id' => $baseAirport->id,
                    ],
                    [
                        'active' => true,
                        'notes'  => sprintf(
                            'Seeded inbound route from %s to %s.',
                            $originAirport->iata,
                            $baseAirport->iata,
                        ),
                    ],
                );

                $seededRoutes++;

                WatchTarget::query()->updateOrCreate(
                    [
                        'origin_city_id'        => $originAirport->city_id,
                        'origin_airport_id'     => $originAirport->id,
                        'destination_city_id'   => $baseAirport->city_id,
                        'destination_airport_id' => $baseAirport->id,
                    ],
                    [
                        'enabled'             => $route->active,
                        'monitoring_priority' => $routePriority,
                        'date_window_days'    => 10,
                    ],
                );

                $seededTargets++;
            }

            $this->command?->info(sprintf(
                '[DefaultRouteSeeder] %s (%s) → %d inbound routes seeded (priority %d).',
                $baseAirport->iata,
                $baseAirport->city->name ?? 'unknown',
                $allAirports->count() - 1,
                $priority,
            ));
        }

        $this->command?->info("[DefaultRouteSeeder] Total: {$seededRoutes} routes, {$seededTargets} watch targets.");
    }
}
