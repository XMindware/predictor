<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteIndicator extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'route_id',
        'as_of',
        'travel_date',
        'window_hours',
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
            'travel_date' => 'date',
            'flight_score' => 'float',
            'news_score' => 'float',
            'combined_score' => 'float',
            'supporting_factors' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Route, $this>
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }
}
