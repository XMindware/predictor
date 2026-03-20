<?php

namespace Tests\Feature;

use Database\Seeders\BasicGeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BasicGeographySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_basic_geography_seeder_creates_baseline_countries_cities_and_airports(): void
    {
        $this->seed(BasicGeographySeeder::class);

        $this->assertDatabaseHas('countries', [
            'name' => 'Mexico',
        ]);
        $this->assertDatabaseHas('countries', [
            'name' => 'Canada',
        ]);
        $this->assertDatabaseHas('cities', [
            'name' => 'Cancun',
        ]);
        $this->assertDatabaseHas('cities', [
            'name' => 'Toronto',
        ]);
        $this->assertDatabaseHas('airports', [
            'iata' => 'CUN',
            'timezone' => 'America/Cancun',
        ]);
        $this->assertDatabaseHas('airports', [
            'iata' => 'JFK',
            'timezone' => 'America/New_York',
        ]);
        $this->assertDatabaseHas('airports', [
            'iata' => 'YYZ',
            'timezone' => 'America/Toronto',
        ]);
        $this->assertDatabaseHas('airports', [
            'iata' => 'SFO',
            'timezone' => 'America/Los_Angeles',
        ]);
    }
}
