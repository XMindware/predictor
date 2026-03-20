<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsEvent extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'city_id',
        'airport_id',
        'airline_code',
        'published_at',
        'title',
        'summary',
        'url',
        'category',
        'severity_score',
        'relevance_score',
        'source_provider_id',
        'raw_payload_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'severity_score' => 'float',
            'relevance_score' => 'float',
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
     * @return BelongsTo<Airport, $this>
     */
    public function airport(): BelongsTo
    {
        return $this->belongsTo(Airport::class);
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
