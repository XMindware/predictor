<?php

namespace Database\Seeders;

use App\Models\Airport;
use App\Models\MonitoredDestination;
use Illuminate\Database\Seeder;

class MonitoredDestinationSeeder extends Seeder
{
    /**
     * Seed the monitored_destinations table from the BASE_AIRPORTS env / config value.
     *
     * If a row already exists for an IATA code it is left untouched (updateOrCreate
     * only fills in missing values on first insert).
     */
    public function run(): void
    {
        /** @var list<string> $iatas */
        $iatas = config('operations.base_airports', ['CUN']);

        foreach ($iatas as $index => $iata) {
            $airport = Airport::query()
                ->with('city')
                ->where('iata', $iata)
                ->first();

            MonitoredDestination::query()->updateOrCreate(
                ['iata' => $iata],
                [
                    'label'     => $airport?->city?->name ?? null,
                    'is_active' => true,
                    'priority'  => 10 - $index,   // first listed = highest priority
                    'notes'     => 'Seeded from BASE_AIRPORTS configuration.',
                ],
            );

            $this->command?->info("[MonitoredDestinationSeeder] {$iata} → seeded.");
        }
    }
}
