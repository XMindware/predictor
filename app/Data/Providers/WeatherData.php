<?php

namespace App\Data\Providers;

final readonly class WeatherData
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $providerSlug,
        public string $locationCode,
        public ?string $externalReference = null,
        public ?string $timezone = null,
        public ?string $observedAt = null,
        public ?float $temperatureCelsius = null,
        public ?float $precipitationProbability = null,
        public ?string $condition = null,
        public array $meta = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'provider_slug' => $this->providerSlug,
            'location_code' => $this->locationCode,
            'external_reference' => $this->externalReference,
            'timezone' => $this->timezone,
            'observed_at' => $this->observedAt,
            'temperature_celsius' => $this->temperatureCelsius,
            'precipitation_probability' => $this->precipitationProbability,
            'condition' => $this->condition,
            'meta' => $this->meta,
        ];
    }
}
