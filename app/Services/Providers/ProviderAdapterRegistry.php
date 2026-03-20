<?php

namespace App\Services\Providers;

use App\Contracts\Providers\FlightProviderInterface;
use App\Contracts\Providers\NewsProviderInterface;
use App\Contracts\Providers\WeatherProviderInterface;
use App\Models\Provider;
use App\Services\Providers\Adapters\StubFlightProvider;
use App\Services\Providers\Adapters\StubNewsProvider;
use App\Services\Providers\Adapters\StubWeatherProvider;
use InvalidArgumentException;

class ProviderAdapterRegistry
{
    public function weather(Provider $provider): WeatherProviderInterface
    {
        $this->ensureService($provider, 'weather');

        return app(StubWeatherProvider::class);
    }

    public function flights(Provider $provider): FlightProviderInterface
    {
        $this->ensureService($provider, 'flights');

        return app(StubFlightProvider::class);
    }

    public function news(Provider $provider): NewsProviderInterface
    {
        $this->ensureService($provider, 'news');

        return app(StubNewsProvider::class);
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
