<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RssNewsSource extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'provider_id',
        'iata',
        'name',
        'url',
        'language',
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

    // ── Relationships ─────────────────────────────────────────────────────────

    /**
     * @return BelongsTo<Provider, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /**
     * @param  Builder<RssNewsSource>  $query
     * @return Builder<RssNewsSource>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Global default feeds — fetched for every watch target.
     *
     * @param  Builder<RssNewsSource>  $query
     * @return Builder<RssNewsSource>
     */
    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('iata');
    }

    /**
     * Feeds for a specific airport IATA code.
     *
     * @param  Builder<RssNewsSource>  $query
     * @return Builder<RssNewsSource>
     */
    public function scopeForIata(Builder $query, string $iata): Builder
    {
        return $query->where('iata', strtoupper($iata));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Return active feed URLs for the given IATAs, merging global defaults.
     * De-duplicated, ordered by priority descending.
     *
     * @param  list<string>  $iatas  e.g. ['CUN', 'MIA']
     * @return list<string>
     */
    public static function urlsFor(array $iatas): array
    {
        $iatas = array_map('strtoupper', array_filter($iatas));

        return static::active()
            ->where(function (Builder $q) use ($iatas): void {
                $q->whereNull('iata');                         // global defaults
                if ($iatas !== []) {
                    $q->orWhereIn('iata', $iatas);             // city-specific
                }
            })
            ->orderByDesc('priority')
            ->orderBy('id')
            ->pluck('url')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Whether this is a global (non-city-specific) feed.
     */
    public function isGlobal(): bool
    {
        return $this->iata === null;
    }
}
