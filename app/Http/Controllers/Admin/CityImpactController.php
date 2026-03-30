<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\FetchNewsDataJob;
use App\Jobs\FetchWeatherDataJob;
use App\Jobs\FetchFlightDataJob;
use App\Models\Airport;
use App\Models\MonitoredDestination;
use App\Models\WatchTarget;
use App\Services\CityNewsImpactService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CityImpactController extends Controller
{
    public function __construct(
        private readonly CityNewsImpactService $impactService,
    ) {}

    /**
     * Overview of all monitored destination cities with news impact summaries.
     */
    public function index(Request $request): View
    {
        $days    = max(1, min(30, (int) $request->integer('days', 7)));
        $showAll = $request->boolean('all', false);

        // Prefer the database-managed list; fall back to env config if empty
        $baseIatas = MonitoredDestination::activeIatas();
        if (empty($baseIatas)) {
            $baseIatas = config('operations.base_airports', ['CUN']);
        }

        if ($showAll) {
            $summaries = $this->impactService->summaryForAllMonitoredDestinations($days);
        } else {
            $summaries = $this->impactService->summaryForDestinations($baseIatas, $days);
        }

        return view('admin.cities.index', compact('summaries', 'days', 'showAll', 'baseIatas'));
    }

    /**
     * Detailed impact view for a single destination airport (by IATA).
     */
    public function show(Request $request, string $iata): View
    {
        $iata    = strtoupper($iata);
        $days    = max(1, min(30, (int) $request->integer('days', 7)));
        $airport = Airport::query()
            ->with('city')
            ->where('iata', $iata)
            ->firstOrFail();

        $report = $this->impactService->reportForAirport($airport, $days);

        return view('admin.cities.show', compact('report', 'airport', 'days'));
    }

    /**
     * Toggle a watch target's enabled state.
     */
    public function toggleWatchTarget(Request $request, WatchTarget $watchTarget): RedirectResponse
    {
        $watchTarget->update(['enabled' => ! $watchTarget->enabled]);

        $state = $watchTarget->enabled ? 'enabled' : 'disabled';

        return back()->with('status', "Watch target {$state}.");
    }

    /**
     * Update monitoring priority and date window for a watch target.
     */
    public function updateWatchTarget(Request $request, WatchTarget $watchTarget): RedirectResponse
    {
        $validated = $request->validate([
            'monitoring_priority' => ['required', 'integer', 'min:1', 'max:10'],
            'date_window_days'    => ['required', 'integer', 'min:1', 'max:30'],
            'enabled'             => ['sometimes', 'boolean'],
        ]);

        $validated['enabled'] = $request->boolean('enabled', $watchTarget->enabled);

        $watchTarget->update($validated);

        return back()->with('status', 'Watch target updated.');
    }

    /**
     * Trigger an immediate data fetch for a specific destination airport.
     * Dispatches all three ingestion jobs filtered to watch targets for this destination.
     */
    public function triggerFetch(Request $request, string $iata): RedirectResponse
    {
        $iata    = strtoupper($iata);
        $sources = $request->input('sources', ['news', 'weather', 'flights']);

        if (in_array('news', $sources, true)) {
            FetchNewsDataJob::dispatch();
        }

        if (in_array('weather', $sources, true)) {
            FetchWeatherDataJob::dispatch();
        }

        if (in_array('flights', $sources, true)) {
            FetchFlightDataJob::dispatch();
        }

        return back()->with('status', "Fetch jobs dispatched for {$iata}. Results will appear after the queue processes them.");
    }
}
