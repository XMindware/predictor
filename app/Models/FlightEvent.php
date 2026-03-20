<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlightEvent extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'route_id',
        'origin_airport_id',
        'destination_airport_id',
        'airline_code',
        'event_time',
        'travel_date',
        'cancellation_rate',
        'delay_average_minutes',
        'disruption_score',
        'summary',
        'source_provider_id',
        'raw_payload_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_time' => 'datetime',
            'travel_date' => 'date',
            'cancellation_rate' => 'float',
            'delay_average_minutes' => 'float',
            'disruption_score' => 'float',
        ];
    }

    /**
     * @return BelongsTo<Route, $this>
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    /**
     * @return BelongsTo<Airport, $this>
     */
    public function originAirport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'origin_airport_id');
    }

    /**
     * @return BelongsTo<Airport, $this>
     */
    public function destinationAirport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'destination_airport_id');
    }

    /**
     * @return BelongsTo<Provider, $this>
     */
    public function sourceProvider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'source_provider_id');
    }

    /**
     * @return BelongsTo<RawProviderPayload, $this>
     */
    public function rawPayload(): BelongsTo
    {
        return $this->belongsTo(RawProviderPayload::class, 'raw_payload_id');
    }
}
