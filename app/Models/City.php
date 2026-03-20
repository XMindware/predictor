<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'country_id',
        'name',
    ];

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return HasMany<Airport, $this>
     */
    public function airports(): HasMany
    {
        return $this->hasMany(Airport::class);
    }

    /**
     * @return HasMany<WatchTarget, $this>
     */
    public function originWatchTargets(): HasMany
    {
        return $this->hasMany(WatchTarget::class, 'origin_city_id');
    }

    /**
     * @return HasMany<WatchTarget, $this>
     */
    public function destinationWatchTargets(): HasMany
    {
        return $this->hasMany(WatchTarget::class, 'destination_city_id');
    }

    /**
     * @return HasMany<CityIndicator, $this>
     */
    public function indicators(): HasMany
    {
        return $this->hasMany(CityIndicator::class);
    }
}
