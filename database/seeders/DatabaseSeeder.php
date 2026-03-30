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
            // RSS news provider (supplements / replaces NewsAPI for city-targeted feeds)
            RssNewsSourceSeeder::class,
            // Auth / membership seeders (order matters: plans before demo users)
            MembershipPlanSeeder::class,
            VisitorDemoSeeder::class,
            // Monitored destinations (seeded after geography so airport lookups work)
            MonitoredDestinationSeeder::class,
        ]);
    }
}
