<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RawProviderPayload extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'provider_id',
        'source_type',
        'external_reference',
        'payload',
        'fetched_at',
        'normalized_at',
        'ingestion_run_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'fetched_at' => 'datetime',
            'normalized_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Provider, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * @return BelongsTo<IngestionRun, $this>
     */
    public function ingestionRun(): BelongsTo
    {
        return $this->belongsTo(IngestionRun::class);
    }

    /**
     * @return HasMany<WeatherEvent, $this>
     */
    public function weatherEvents(): HasMany
    {
        return $this->hasMany(WeatherEvent::class, 'raw_payload_id');
    }

    /**
     * @return HasMany<FlightEvent, $this>
     */
    public function flightEvents(): HasMany
    {
        return $this->hasMany(FlightEvent::class, 'raw_payload_id');
    }

    /**
     * @return HasMany<NewsEvent, $this>
     */
    public function newsEvents(): HasMany
    {
        return $this->hasMany(NewsEvent::class, 'raw_payload_id');
    }
}
