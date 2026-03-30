<?php

namespace App\Services;

use App\Models\Airport;
use App\Models\City;
use App\Models\NewsEvent;
use App\Models\WatchTarget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Aggregates news event data into a travel-impact report for monitored cities.
 *
 * Used by the city impact dashboard to answer:
 *  - How many disruption-related news events hit this city in the last N days?
 *  - What is the average / peak severity?
 *  - Which categories dominate (weather vs operations vs airline)?
 *  - Day-by-day event volume (for sparklines / bar charts).
 *  - Which watch targets are active for this destination?
 */
class CityNewsImpactService
{
    // Impact level thresholds (based on average severity score 0-10)
    private const LEVEL_HIGH     = 6.5;
    private const LEVEL_MEDIUM   = 4.0;
    private const LEVEL_LOW      = 2.0;

    // ─── Public API ───────────────────────────────────────────────────────────

    /**
     * Build a full impact report for a single destination airport/city.
     *
     * @return array{
     *   airport: Airport,
     *   city: City|null,
     *   days: int,
     *   total_events: int,
     *   avg_severity: float,
     *   peak_severity: float,
     *   impact_level: string,
     *   impact_color: string,
     *   by_category: array<string, int>,
     *   by_day: array<string, int>,
     *   top_events: Collection,
     *   news_events: Collection,
     *   watch_targets: Collection,
     *   active_watch_targets: int,
     *   last_event_at: Carbon|null,
     * }
     */
    public function reportForAirport(Airport $airport, int $days = 7): array
    {
        $airport->loadMissing('city');
        $cutoff = Carbon::now()->subDays($days)->startOfDay();

        // News events for this city — fetched DB-ordered for efficiency,
        // then ranked in PHP by composite relevance score.
        $events = NewsEvent::query()
            ->where('city_id', $airport->city_id)
            ->where('published_at', '>=', $cutoff)
            ->with('sourceProvider')
            ->orderByDesc('published_at')
            ->get();

        // Watch targets pointing TO this airport
        $watchTargets = WatchTarget::query()
            ->where('destination_airport_id', $airport->id)
            ->with(['originCity', 'originAirport'])
            ->orderByDesc('monitoring_priority')
            ->get();

        $avgSeverity  = round((float) $events->avg('severity_score'), 2);
        $peakSeverity = round((float) $events->max('severity_score'), 2);

        // Full news feed: ranked by composite score (relevance 60% + severity 40%),
        // then by recency as tiebreaker.
        $rankedEvents = $events
            ->sortByDesc(fn (NewsEvent $e): float =>
                ($e->relevance_score * 0.6) + ($e->severity_score * 0.4)
            )
            ->values();

        return [
            'airport'              => $airport,
            'city'                 => $airport->city,
            'days'                 => $days,
            'total_events'         => $events->count(),
            'avg_severity'         => $avgSeverity,
            'peak_severity'        => $peakSeverity,
            'impact_level'         => $this->impactLevel($avgSeverity, $events->count()),
            'impact_color'         => $this->impactColor($avgSeverity, $events->count()),
            'by_category'          => $this->groupByCategory($events),
            'by_day'               => $this->groupByDay($events, $days),
            'top_events'           => $rankedEvents->take(3),
            'news_events'          => $rankedEvents,
            'watch_targets'        => $watchTargets,
            'active_watch_targets' => $watchTargets->where('enabled', true)->count(),
            'last_event_at'        => $events->first()?->published_at,
        ];
    }

    /**
     * Build summary cards for all configured destination (base) airports.
     *
     * @param  list<string>  $iatas  Airport IATA codes to summarise.
     * @return Collection<int, array<string, mixed>>
     */
    public function summaryForDestinations(array $iatas, int $days = 7): Collection
    {
        $airports = Airport::query()
            ->with('city')
            ->whereIn('iata', $iatas)
            ->get()
            ->keyBy('iata');

        return collect($iatas)
            ->map(function (string $iata) use ($airports, $days): ?array {
                $airport = $airports->get($iata);
                if (! $airport) {
                    return null;
                }

                return $this->reportForAirport($airport, $days);
            })
            ->filter()
            ->values();
    }

    /**
     * Build summary cards for ALL enabled destination airports found in watch targets.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function summaryForAllMonitoredDestinations(int $days = 7): Collection
    {
        $destinationIds = WatchTarget::query()
            ->whereNotNull('destination_airport_id')
            ->pluck('destination_airport_id')
            ->unique();

        $airports = Airport::query()
            ->with('city')
            ->whereIn('id', $destinationIds)
            ->orderBy('iata')
            ->get();

        return $airports->map(fn (Airport $a) => $this->reportForAirport($a, $days));
    }

    // ─── Aggregation helpers ──────────────────────────────────────────────────

    /**
     * @param  Collection<int, NewsEvent>  $events
     * @return array<string, int>
     */
    private function groupByCategory(Collection $events): array
    {
        return $events
            ->groupBy('category')
            ->map(fn (Collection $group): int => $group->count())
            ->sortDesc()
            ->all();
    }

    /**
     * Return a day-keyed count map for the last $days days (fills zeroes).
     *
     * @param  Collection<int, NewsEvent>  $events
     * @return array<string, int>  Keys are 'Y-m-d', values are event counts.
     */
    private function groupByDay(Collection $events, int $days): array
    {
        // Pre-fill all days with zero so the chart has no gaps
        $grid = collect(range(0, $days - 1))
            ->mapWithKeys(fn (int $offset): array => [
                Carbon::now()->subDays($days - 1 - $offset)->format('Y-m-d') => 0,
            ]);

        $actuals = $events
            ->groupBy(fn (NewsEvent $e): string => Carbon::parse($e->published_at)->format('Y-m-d'))
            ->map(fn (Collection $group): int => $group->count());

        return $grid->merge($actuals)->all();
    }

    // ─── Impact classification ────────────────────────────────────────────────

    private function impactLevel(float $avgSeverity, int $eventCount): string
    {
        if ($eventCount === 0) {
            return 'none';
        }

        if ($avgSeverity >= self::LEVEL_HIGH || $eventCount >= 10) {
            return 'high';
        }

        if ($avgSeverity >= self::LEVEL_MEDIUM || $eventCount >= 5) {
            return 'medium';
        }

        if ($avgSeverity >= self::LEVEL_LOW || $eventCount >= 1) {
            return 'low';
        }

        return 'none';
    }

    private function impactColor(float $avgSeverity, int $eventCount): string
    {
        return match ($this->impactLevel($avgSeverity, $eventCount)) {
            'high'   => '#ef4444',  // red
            'medium' => '#f97316',  // orange
            'low'    => '#eab308',  // yellow
            default  => '#22c55e',  // green
        };
    }
}
