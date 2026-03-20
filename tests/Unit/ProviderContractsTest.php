<?php

namespace Tests\Unit;

use App\Contracts\Providers\FlightProviderInterface;
use App\Contracts\Providers\NewsProviderInterface;
use App\Contracts\Providers\WeatherProviderInterface;
use App\Data\Providers\FlightData;
use App\Data\Providers\NewsData;
use App\Data\Providers\WeatherData;
use PHPUnit\Framework\TestCase;

class ProviderContractsTest extends TestCase
{
    public function test_weather_provider_contract_returns_normalized_weather_dto_objects(): void
    {
        $provider = new class implements WeatherProviderInterface
        {
            public function fetchWeather(array $criteria = []): array
            {
                return [
                    new WeatherData(
                        providerSlug: 'openweather',
                        locationCode: 'CUN',
                        externalReference: 'weather:forecast:CUN',
                        timezone: 'America/Cancun',
                        observedAt: '2026-03-19T12:00:00Z',
                        temperatureCelsius: 29.5,
                        precipitationProbability: 0.35,
                        condition: 'rain',
                        meta: ['units' => 'metric'],
                    ),
                ];
            }
        };

        $results = $provider->fetchWeather(['location_code' => 'CUN']);

        $this->assertInstanceOf(WeatherData::class, $results[0]);
        $this->assertSame('CUN', $results[0]->locationCode);
        $this->assertSame('rain', $results[0]->toArray()['condition']);
    }

    public function test_flight_provider_contract_returns_normalized_flight_dto_objects(): void
    {
        $provider = new class implements FlightProviderInterface
        {
            public function searchFlights(array $criteria = []): array
            {
                return [
                    new FlightData(
                        providerSlug: 'flightstats',
                        originCode: 'CUN',
                        destinationCode: 'MIA',
                        externalReference: 'flight:offer:123',
                        departureAt: '2026-03-20T08:00:00Z',
                        arrivalAt: '2026-03-20T10:30:00Z',
                        carrierCode: 'AA',
                        flightNumber: '1001',
                        priceAmount: 249.99,
                        priceCurrency: 'USD',
                        stops: 0,
                        meta: ['cabin' => 'economy'],
                    ),
                ];
            }
        };

        $results = $provider->searchFlights(['origin' => 'CUN', 'destination' => 'MIA']);

        $this->assertInstanceOf(FlightData::class, $results[0]);
        $this->assertSame('MIA', $results[0]->destinationCode);
        $this->assertSame(0, $results[0]->toArray()['stops']);
    }

    public function test_news_provider_contract_returns_normalized_news_dto_objects(): void
    {
        $provider = new class implements NewsProviderInterface
        {
            public function fetchNews(array $criteria = []): array
            {
                return [
                    new NewsData(
                        providerSlug: 'newsapi',
                        title: 'Storm warning issued for Cancun routes',
                        externalReference: 'article:storm-warning',
                        summary: 'Heavy rain may affect several monitored routes.',
                        url: 'https://example.com/article',
                        publishedAt: '2026-03-19T09:00:00Z',
                        topics: ['weather', 'disruption'],
                        meta: ['language' => 'en'],
                    ),
                ];
            }
        };

        $results = $provider->fetchNews(['topic' => 'weather']);

        $this->assertInstanceOf(NewsData::class, $results[0]);
        $this->assertSame('Storm warning issued for Cancun routes', $results[0]->title);
        $this->assertSame(['weather', 'disruption'], $results[0]->toArray()['topics']);
    }
}
