<?php

namespace App\Data\Providers;

final readonly class FlightData
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $providerSlug,
        public string $originCode,
        public string $destinationCode,
        public ?string $externalReference = null,
        public ?string $departureAt = null,
        public ?string $arrivalAt = null,
        public ?string $carrierCode = null,
        public ?string $flightNumber = null,
        public ?float $priceAmount = null,
        public ?string $priceCurrency = null,
        public int $stops = 0,
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
            'origin_code' => $this->originCode,
            'destination_code' => $this->destinationCode,
            'external_reference' => $this->externalReference,
            'departure_at' => $this->departureAt,
            'arrival_at' => $this->arrivalAt,
            'carrier_code' => $this->carrierCode,
            'flight_number' => $this->flightNumber,
            'price_amount' => $this->priceAmount,
            'price_currency' => $this->priceCurrency,
            'stops' => $this->stops,
            'meta' => $this->meta,
        ];
    }
}
