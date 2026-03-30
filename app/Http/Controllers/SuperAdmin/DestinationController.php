<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Airport;
use App\Models\MonitoredDestination;
use App\Models\Route;
use App\Models\WatchTarget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DestinationController extends Controller
{
    /**
     * List all monitored destinations.
     */
    public function index(): View
    {
        $destinations = MonitoredDestination::query()
            ->with('airport.city')
            ->orderByDesc('priority')
            ->orderBy('iata')
            ->get();

        // Airports not yet added as destinations (for the "add" form)
        $addedIatas    = $destinations->pluck('iata')->all();
        $availableAirports = Airport::query()
            ->with('city')
            ->whereNotIn('iata', $addedIatas)
            ->orderBy('iata')
            ->get();

        return view('super-admin.destinations.index', compact('destinations', 'availableAirports'));
    }

    /**
     * Add a new monitored destination and provision its inbound routes + watch targets.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'iata'     => ['required', 'string', 'size:3', 'unique:monitored_destinations,iata'],
            'label'    => ['nullable', 'string', 'max:100'],
            'priority' => ['required', 'integer', 'min:1', 'max:10'],
            'notes'    => ['nullable', 'string', 'max:500'],
        ]);

        $validated['iata']      = strtoupper($validated['iata']);
        $validated['is_active'] = true;

        // Verify the airport exists in our DB
        $airport = Airport::query()
            ->with('city')
            ->where('iata', $validated['iata'])
            ->first();

        if (! $airport) {
            return back()
                ->withInput()
                ->withErrors(['iata' => "Airport with IATA code {$validated['iata']} was not found in the database."]);
        }

        $destination = MonitoredDestination::create($validated);

        // Auto-fill label from city name if not provided
        if (! $destination->label && $airport->city) {
            $destination->update(['label' => $airport->city->name]);
        }

        // Provision inbound routes + watch targets for the new destination
        [$routes, $targets] = $this->provisionRoutesForDestination($airport);

        return redirect()
            ->route('super-admin.destinations.index')
            ->with('status', "Destination {$destination->iata} added. Provisioned {$routes} routes and {$targets} watch targets.");
    }

    /**
     * Update a monitored destination's meta (label, priority, notes, is_active).
     */
    public function update(Request $request, MonitoredDestination $destination): RedirectResponse
    {
        $validated = $request->validate([
            'label'     => ['nullable', 'string', 'max:100'],
            'priority'  => ['required', 'integer', 'min:1', 'max:10'],
            'notes'     => ['nullable', 'string', 'max:500'],
            'is_active' => ['required', 'boolean'],
        ]);

        $destination->update($validated);

        return back()->with('status', "Destination {$destination->iata} updated.");
    }

    /**
     * Toggle active state inline.
     */
    public function toggle(MonitoredDestination $destination): RedirectResponse
    {
        $destination->update(['is_active' => ! $destination->is_active]);

        $state = $destination->is_active ? 'activated' : 'deactivated';

        return back()->with('status', "Destination {$destination->iata} {$state}.");
    }

    /**
     * Remove a monitored destination.
     * Does NOT delete the underlying routes/watch targets — those stay for historical data.
     */
    public function destroy(MonitoredDestination $destination): RedirectResponse
    {
        $iata = $destination->iata;
        $destination->delete();

        return redirect()
            ->route('super-admin.destinations.index')
            ->with('status', "Destination {$iata} removed from monitoring. Routes and watch targets are preserved.");
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Create inbound Route + WatchTarget rows for every known origin airport → given base airport.
     *
     * @return array{0: int, 1: int} [routesCreated, watchTargetsCreated]
     */
    private function provisionRoutesForDestination(Airport $baseAirport): array
    {
        $allAirports  = Airport::query()->with('city')->get();
        $routeCount   = 0;
        $targetCount  = 0;

        foreach ($allAirports as $origin) {
            if ($origin->id === $baseAirport->id) {
                continue;
            }

            $route = Route::query()->updateOrCreate(
                [
                    'origin_airport_id'      => $origin->id,
                    'destination_airport_id' => $baseAirport->id,
                ],
                [
                    'active' => true,
                    'notes'  => "Auto-provisioned inbound route from {$origin->iata} to {$baseAirport->iata}.",
                ],
            );

            $routeCount++;

            WatchTarget::query()->updateOrCreate(
                [
                    'origin_city_id'         => $origin->city_id,
                    'origin_airport_id'      => $origin->id,
                    'destination_city_id'    => $baseAirport->city_id,
                    'destination_airport_id' => $baseAirport->id,
                ],
                [
                    'enabled'             => $route->active,
                    'monitoring_priority' => 8,
                    'date_window_days'    => 10,
                ],
            );

            $targetCount++;
        }

        return [$routeCount, $targetCount];
    }
}
