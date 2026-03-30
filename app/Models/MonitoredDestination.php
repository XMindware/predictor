<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MonitoredDestination extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'iata',
        'label',
        'is_active',
        'priority',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'priority'  => 'integer',
        ];
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    /**
     * @param  Builder<MonitoredDestination>  $query
     * @return Builder<MonitoredDestination>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    /**
     * @return HasOne<Airport, $this>
     */
    public function airport(): HasOne
    {
        return $this->hasOne(Airport::class, 'iata', 'iata')->with('city');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Display name: prefers the stored label, falls back to IATA code.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->label ?: $this->iata;
    }

    /**
     * Return all active IATA codes ordered by priority desc.
     *
     * @return list<string>
     */
    public static function activeIatas(): array
    {
        return static::active()
            ->orderByDesc('priority')
            ->orderBy('iata')
            ->pluck('iata')
            ->all();
    }
}
