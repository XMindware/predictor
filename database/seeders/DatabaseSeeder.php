<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SuperAdminSeeder::class,
            BasicGeographySeeder::class,
            DefaultRouteSeeder::class,
            ProviderRegistrySeeder::class,
            ScoringProfileSeeder::class,
            WeatherSourceSeeder::class,
            FlightSourceSeeder::class,
            NewsSourceSeeder::class,
        ]);
    }
}
