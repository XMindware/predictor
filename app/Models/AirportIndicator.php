<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AirportIndicator extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'airport_id',
        'as_of',
        'window_hours',
        'weather_score',
        'flight_score',
        'news_score',
        'combined_score',
        'supporting_factors',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'as_of' => 'datetime',
            'weather_score' => 'float',
            'flight_score' => 'float',
            'news_score' => 'float',
            'combined_score' => 'float',
            'supporting_factors' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Airport, $this>
     */
    public function airport(): BelongsTo
    {
        return $this->belongsTo(Airport::class);
    }
}
