<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Airport extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'country_id',
        'city_id',
        'name',
        'iata',
        'icao',
        'timezone',
        'latitude',
        'longitude',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return HasMany<Route, $this>
     */
    public function originRoutes(): HasMany
    {
        return $this->hasMany(Route::class, 'origin_airport_id');
    }

    /**
     * @return HasMany<Route, $this>
     */
    public function destinationRoutes(): HasMany
    {
        return $this->hasMany(Route::class, 'destination_airport_id');
    }

    /**
     * @return HasMany<WatchTarget, $this>
     */
    public function originWatchTargets(): HasMany
    {
        return $this->hasMany(WatchTarget::class, 'origin_airport_id');
    }

    /**
     * @return HasMany<WatchTarget, $this>
     */
    public function destinationWatchTargets(): HasMany
    {
        return $this->hasMany(WatchTarget::class, 'destination_airport_id');
    }

    /**
     * @return HasMany<AirportIndicator, $this>
     */
    public function indicators(): HasMany
    {
        return $this->hasMany(AirportIndicator::class);
    }
}
