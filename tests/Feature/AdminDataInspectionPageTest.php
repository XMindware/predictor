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
use App\Models\User;
use App\Models\WeatherEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDataInspectionPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_users_are_redirected_away_from_the_data_inspection_page(): void
    {
        $this->get('/admin/data-inspection')
            ->assertRedirect('/login');
    }

    public function test_authenticated_users_can_inspect_weather_news_and_flight_data_for_a_city_and_date(): void
    {
        $user = User::factory()->create();

        $weatherProvider = Provider::create([
            'name' => 'OpenWeather',
            'slug' => 'openweather',
            'service' => 'weather',
            'driver' => 'rest',
            'active' => true,
        ]);
        $newsProvider = Provider::create([
            'name' => 'NewsAPI',
            'slug' => 'newsapi',
            'service' => 'news',
            'driver' => 'rest',
            'active' => true,
        ]);
        $flightProvider = Provider::create([
            'name' => 'FlightStats',
            'slug' => 'flightstats',
            'service' => 'flights',
            'driver' => 'rest',
            'active' => true,
        ]);

        $country = Country::create([
            'name' => 'United States',
        ]);
        $city = City::create([
            'country_id' => $country->id,
            'name' => 'New York',
        ]);
        $destinationCity = City::create([
            'country_id' => $country->id,
            'name' => 'Miami',
        ]);
        $originAirport = Airport::create([
            'country_id' => $country->id,
            'city_id' => $city->id,
            'name' => 'John F. Kennedy International Airport',
            'iata' => 'JFK',
            'icao' => 'KJFK',
            'timezone' => 'America/New_York',
        ]);
        $destinationAirport = Airport::create([
            'country_id' => $country->id,
            'city_id' => $destinationCity->id,
            'name' => 'Miami International Airport',
            'iata' => 'MIA',
            'icao' => 'KMIA',
            'timezone' => 'America/New_York',
        ]);
        $route = Route::create([
            'origin_airport_id' => $originAirport->id,
            'destination_airport_id' => $destinationAirport->id,
            'active' => true,
        ]);

        $weatherRun = IngestionRun::create([
            'provider_id' => $weatherProvider->id,
            'source_type' => 'weather',
            'status' => 'completed',
            'started_at' => '2026-03-24 09:50:00',
            'finished_at' => '2026-03-24 10:00:00',
        ]);
        $newsRun = IngestionRun::create([
            'provider_id' => $newsProvider->id,
            'source_type' => 'news',
            'status' => 'completed',
            'started_at' => '2026-03-25 08:50:00',
            'finished_at' => '2026-03-25 09:00:00',
        ]);
        $flightRun = IngestionRun::create([
            'provider_id' => $flightProvider->id,
            'source_type' => 'flights',
            'status' => 'completed',
            'started_at' => '2026-03-24 07:50:00',
            'finished_at' => '2026-03-24 08:00:00',
        ]);

        $weatherPayload = RawProviderPayload::create([
            'provider_id' => $weatherProvider->id,
            'source_type' => 'weather',
            'external_reference' => 'weather:jfk:2026-03-25',
            'payload' => ['items' => []],
            'fetched_at' => '2026-03-24 10:00:00',
            'ingestion_run_id' => $weatherRun->id,
        ]);
        $newsPayload = RawProviderPayload::create([
            'provider_id' => $newsProvider->id,
            'source_type' => 'news',
            'external_reference' => 'news:jfk:2026-03-25',
            'payload' => ['items' => []],
            'fetched_at' => '2026-03-25 09:00:00',
            'ingestion_run_id' => $newsRun->id,
        ]);
        $flightPayload = RawProviderPayload::create([
            'provider_id' => $flightProvider->id,
            'source_type' => 'flights',
            'external_reference' => 'flight:jfk-mia:2026-03-25',
            'payload' => ['items' => []],
            'fetched_at' => '2026-03-24 08:00:00',
            'ingestion_run_id' => $flightRun->id,
        ]);

        WeatherEvent::create([
            'city_id' => $city->id,
            'airport_id' => $originAirport->id,
            'event_time' => '2026-03-24 10:00:00',
            'forecast_for' => '2026-03-25 12:00:00',
            'severity_score' => 7.2,
            'condition_code' => 'SNOW',
            'summary' => 'Snow disruption expected.',
            'temperature' => 1.5,
            'precipitation_mm' => 6.0,
            'wind_speed' => 18.0,
            'source_provider_id' => $weatherProvider->id,
            'raw_payload_id' => $weatherPayload->id,
        ]);

        NewsEvent::create([
            'city_id' => $city->id,
            'airport_id' => $originAirport->id,
            'airline_code' => null,
            'published_at' => '2026-03-25 09:30:00',
            'title' => 'Storm warnings issued for New York departures',
            'summary' => 'Operational teams are monitoring the situation.',
            'url' => 'https://example.com/story',
            'category' => 'weather',
            'severity_score' => 8.1,
            'relevance_score' => 8.6,
            'source_provider_id' => $newsProvider->id,
            'raw_payload_id' => $newsPayload->id,
        ]);

        FlightEvent::create([
            'route_id' => $route->id,
            'origin_airport_id' => $originAirport->id,
            'destination_airport_id' => $destinationAirport->id,
            'airline_code' => 'DL',
            'event_time' => '2026-03-24 08:00:00',
            'travel_date' => '2026-03-25',
            'cancellation_rate' => 1.3,
            'delay_average_minutes' => 34.0,
            'disruption_score' => 6.8,
            'summary' => 'Higher delay risk on the morning bank.',
            'source_provider_id' => $flightProvider->id,
            'raw_payload_id' => $flightPayload->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin.data-inspection.index', [
                'city_id' => $city->id,
                'date' => '2026-03-25',
            ]))
            ->assertOk()
            ->assertSee('Fetched Data Inspection')
            ->assertSee('Weather Data')
            ->assertSee('News Data')
            ->assertSee('Flight Data')
            ->assertSee('Snow disruption expected.')
            ->assertSee('Storm warnings issued for New York departures')
            ->assertSee('JFK → MIA')
            ->assertSee('weather:jfk:2026-03-25')
            ->assertSee('news:jfk:2026-03-25')
            ->assertSee('flight:jfk-mia:2026-03-25');
    }
}
