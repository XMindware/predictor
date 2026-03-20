<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\City;
use App\Models\Country;
use App\Models\FlightEvent;
use App\Models\IngestionRun;
use App\Models\NewsEvent;
use App\Models\Provider;
use App\Models\RawProviderPayload;
use App\Models\Route;
use App\Models\WeatherEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_weather_flight_and_news_events_persist_with_source_and_payload_relationships(): void
    {
        $country = Country::create([
            'name' => 'Mexico',
        ]);

        $city = City::create([
            'country_id' => $country->id,
            'name' => 'Cancun',
        ]);

        $destinationCity = City::create([
            'country_id' => $country->id,
            'name' => 'Merida',
        ]);

        $originAirport = Airport::create([
            'country_id' => $country->id,
            'city_id' => $city->id,
            'name' => 'Cancun International Airport',
            'iata' => 'CUN',
            'icao' => 'MMUN',
            'timezone' => 'America/Cancun',
        ]);

        $destinationAirport = Airport::create([
            'country_id' => $country->id,
            'city_id' => $destinationCity->id,
            'name' => 'Merida International Airport',
            'iata' => 'MID',
            'icao' => 'MMMD',
            'timezone' => 'America/Merida',
        ]);

        $route = Route::create([
            'origin_airport_id' => $originAirport->id,
            'destination_airport_id' => $destinationAirport->id,
            'active' => true,
        ]);

        $provider = Provider::create([
            'name' => 'OpenWeather',
            'slug' => 'openweather',
            'service' => 'weather',
            'driver' => 'rest',
            'active' => true,
        ]);

        $run = IngestionRun::create([
            'provider_id' => $provider->id,
            'source_type' => 'weather',
            'status' => 'completed',
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
        ]);

        $rawPayload = RawProviderPayload::create([
            'provider_id' => $provider->id,
            'source_type' => 'weather',
            'external_reference' => 'payload-1',
            'payload' => ['sample' => true],
            'fetched_at' => now(),
            'ingestion_run_id' => $run->id,
        ]);

        $weatherEvent = WeatherEvent::create([
            'city_id' => $city->id,
            'airport_id' => $originAirport->id,
            'event_time' => now(),
            'forecast_for' => now()->addHour(),
            'severity_score' => 7.5,
            'condition_code' => 'RAIN',
            'summary' => 'Heavy rain expected.',
            'temperature' => 28.2,
            'precipitation_mm' => 12.5,
            'wind_speed' => 26.4,
            'source_provider_id' => $provider->id,
            'raw_payload_id' => $rawPayload->id,
        ]);

        $flightEvent = FlightEvent::create([
            'route_id' => $route->id,
            'origin_airport_id' => $originAirport->id,
            'destination_airport_id' => $destinationAirport->id,
            'airline_code' => 'AM',
            'event_time' => now(),
            'travel_date' => now()->toDateString(),
            'cancellation_rate' => 2.5,
            'delay_average_minutes' => 18.0,
            'disruption_score' => 4.7,
            'summary' => 'Moderate disruption detected.',
            'source_provider_id' => $provider->id,
            'raw_payload_id' => $rawPayload->id,
        ]);

        $newsEvent = NewsEvent::create([
            'city_id' => $city->id,
            'airport_id' => $originAirport->id,
            'airline_code' => 'AM',
            'published_at' => now(),
            'title' => 'Storm alert issued',
            'summary' => 'Airport operations may be affected.',
            'url' => 'https://example.com/news/storm-alert',
            'category' => 'weather',
            'severity_score' => 8.1,
            'relevance_score' => 9.0,
            'source_provider_id' => $provider->id,
            'raw_payload_id' => $rawPayload->id,
        ]);

        $this->assertTrue($weatherEvent->city->is($city));
        $this->assertTrue($weatherEvent->airport->is($originAirport));
        $this->assertTrue($weatherEvent->sourceProvider->is($provider));
        $this->assertTrue($weatherEvent->rawPayload->is($rawPayload));
        $this->assertTrue($provider->weatherEvents->contains($weatherEvent));
        $this->assertTrue($rawPayload->weatherEvents->contains($weatherEvent));

        $this->assertTrue($flightEvent->route->is($route));
        $this->assertTrue($flightEvent->originAirport->is($originAirport));
        $this->assertTrue($flightEvent->destinationAirport->is($destinationAirport));
        $this->assertTrue($flightEvent->sourceProvider->is($provider));
        $this->assertTrue($flightEvent->rawPayload->is($rawPayload));
        $this->assertTrue($provider->flightEvents->contains($flightEvent));
        $this->assertTrue($rawPayload->flightEvents->contains($flightEvent));

        $this->assertTrue($newsEvent->city->is($city));
        $this->assertTrue($newsEvent->airport->is($originAirport));
        $this->assertTrue($newsEvent->sourceProvider->is($provider));
        $this->assertTrue($newsEvent->rawPayload->is($rawPayload));
        $this->assertTrue($provider->newsEvents->contains($newsEvent));
        $this->assertTrue($rawPayload->newsEvents->contains($newsEvent));
        $this->assertSame('weather', $newsEvent->category);
    }
}
