<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'service',
        'driver',
        'active',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ProviderCredential, $this>
     */
    public function credentials(): HasMany
    {
        return $this->hasMany(ProviderCredential::class);
    }

    /**
     * @return HasMany<ProviderConfig, $this>
     */
    public function configs(): HasMany
    {
        return $this->hasMany(ProviderConfig::class);
    }

    /**
     * @return HasMany<IngestionRun, $this>
     */
    public function ingestionRuns(): HasMany
    {
        return $this->hasMany(IngestionRun::class);
    }

    /**
     * @return HasMany<RawProviderPayload, $this>
     */
    public function rawPayloads(): HasMany
    {
        return $this->hasMany(RawProviderPayload::class);
    }

    /**
     * @return HasMany<WeatherEvent, $this>
     */
    public function weatherEvents(): HasMany
    {
        return $this->hasMany(WeatherEvent::class, 'source_provider_id');
    }

    /**
     * @return HasMany<FlightEvent, $this>
     */
    public function flightEvents(): HasMany
    {
        return $this->hasMany(FlightEvent::class, 'source_provider_id');
    }

    /**
     * @return HasMany<NewsEvent, $this>
     */
    public function newsEvents(): HasMany
    {
        return $this->hasMany(NewsEvent::class, 'source_provider_id');
    }
}
