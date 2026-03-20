<?php

namespace Tests\Feature;

use Database\Seeders\BasicGeographySeeder;
use Database\Seeders\FlightSourceSeeder;
use Database\Seeders\NewsSourceSeeder;
use Database\Seeders\ProviderRegistrySeeder;
use Database\Seeders\WeatherSourceSeeder;
use App\Models\Airport;
use App\Models\FlightEvent;
use App\Models\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SourceDataSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_source_seeders_create_demo_weather_flight_and_news_data_without_duplication(): void
    {
        Carbon::setTestNow('2026-03-19 12:00:00');

        $this->seed([
            BasicGeographySeeder::class,
            ProviderRegistrySeeder::class,
            WeatherSourceSeeder::class,
            FlightSourceSeeder::class,
            NewsSourceSeeder::class,
        ]);

        $baseAirport = Airport::query()->where('iata', 'CUN')->firstOrFail();
        $expectedRouteCount = Airport::query()->count() - 1;
        $expectedFlightEventCount = $expectedRouteCount * 10;

        $this->assertDatabaseCount('weather_events', 5);
        $this->assertDatabaseCount('news_events', 5);
        $this->assertDatabaseCount('flight_events', $expectedFlightEventCount);
        $this->assertDatabaseHas('flight_events', [
            'travel_date' => '2026-03-29 00:00:00',
        ]);
        $this->assertDatabaseHas('raw_provider_payloads', [
            'source_type' => 'weather',
            'external_reference' => 'seed:weather:CUN:slot-1',
        ]);
        $this->assertDatabaseHas('raw_provider_payloads', [
            'source_type' => 'news',
        ]);
        $this->assertDatabaseHas('watch_targets', [
            'origin_airport_id' => Airport::query()->where('iata', 'JFK')->value('id'),
            'destination_airport_id' => $baseAirport->id,
            'enabled' => true,
        ]);

        $this->seed([
            WeatherSourceSeeder::class,
            FlightSourceSeeder::class,
            NewsSourceSeeder::class,
        ]);

        $this->assertDatabaseCount('weather_events', 5);
        $this->assertDatabaseCount('news_events', 5);
        $this->assertDatabaseCount('flight_events', $expectedFlightEventCount);
        $this->assertDatabaseCount('raw_provider_payloads', $expectedFlightEventCount + 10);

        Carbon::setTestNow();
    }

    public function test_flight_source_seeder_uses_the_configured_base_airport_as_destination(): void
    {
        Carbon::setTestNow('2026-03-19 12:00:00');
        config()->set('operations.base_airport_iata', 'MIA');

        $this->seed([
            BasicGeographySeeder::class,
            ProviderRegistrySeeder::class,
            FlightSourceSeeder::class,
        ]);

        $baseAirport = Airport::query()->where('iata', 'MIA')->firstOrFail();
        $expectedRouteCount = Airport::query()->count() - 1;

        $this->assertSame($expectedRouteCount, Route::query()->count());
        $this->assertSame(0, Route::query()->where('origin_airport_id', $baseAirport->id)->count());
        $this->assertSame($expectedRouteCount, Route::query()->where('destination_airport_id', $baseAirport->id)->count());
        $this->assertSame($expectedRouteCount * 10, FlightEvent::query()->where('destination_airport_id', $baseAirport->id)->count());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
