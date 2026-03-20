<?php

namespace App\Services\Providers;

use App\Contracts\Providers\FlightProviderInterface;
use App\Contracts\Providers\NewsProviderInterface;
use App\Contracts\Providers\WeatherProviderInterface;
use App\Models\Provider;
use App\Services\Providers\Adapters\FlightStatsProvider;
use App\Services\Providers\Adapters\NewsApiProvider;
use App\Services\Providers\Adapters\OpenWeatherProvider;
use InvalidArgumentException;
use RuntimeException;

class ProviderAdapterRegistry
{
    public function weather(Provider $provider): WeatherProviderInterface
    {
        $this->ensureService($provider, 'weather');

        return match ($provider->slug) {
            'openweather' => app()->make(OpenWeatherProvider::class, ['provider' => $provider]),
            default => throw new RuntimeException(sprintf('No live weather adapter is registered for provider [%s].', $provider->slug)),
        };
    }

    public function flights(Provider $provider): FlightProviderInterface
    {
        $this->ensureService($provider, 'flights');

        return match ($provider->slug) {
            'flightstats' => app()->make(FlightStatsProvider::class, ['provider' => $provider]),
            default => throw new RuntimeException(sprintf('No live flight adapter is registered for provider [%s].', $provider->slug)),
        };
    }

    public function news(Provider $provider): NewsProviderInterface
    {
        $this->ensureService($provider, 'news');

        return match ($provider->slug) {
            'newsapi' => app()->make(NewsApiProvider::class, ['provider' => $provider]),
            default => throw new RuntimeException(sprintf('No live news adapter is registered for provider [%s].', $provider->slug)),
        };
    }

    private function ensureService(Provider $provider, string $expectedService): void
    {
        if ($provider->service !== $expectedService) {
            throw new InvalidArgumentException(sprintf(
                'Provider [%s] does not match expected service [%s].',
                $provider->slug,
                $expectedService,
            ));
        }
    }
}
