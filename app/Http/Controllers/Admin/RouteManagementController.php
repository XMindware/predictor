<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Airport;
use App\Models\Route;
use App\Models\WatchTarget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RouteManagementController extends Controller
{
    public function index(): View
    {
        return view('admin.routes.index', [
            'routes' => Route::query()
                ->with(['originAirport.city.country', 'destinationAirport.city.country'])
                ->orderByDesc('active')
                ->orderBy('origin_airport_id')
                ->orderBy('destination_airport_id')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.routes.form', [
            'route' => new Route([
                'active' => true,
            ]),
            'airports' => $this->airportOptions(),
            'pageTitle' => 'Create Route',
            'formAction' => route('admin.routes.store'),
            'formMethod' => 'POST',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->validatedPayload($request);

        DB::transaction(function () use ($payload): void {
            $route = Route::query()->create($payload);
            $this->syncWatchTarget($route);
        });

        return redirect()
            ->route('admin.routes.index')
            ->with('status', 'Route created.');
    }

    public function edit(Route $route): View
    {
        return view('admin.routes.form', [
            'route' => $route,
            'airports' => $this->airportOptions(),
            'pageTitle' => 'Edit Route',
            'formAction' => route('admin.routes.update', $route),
            'formMethod' => 'PUT',
        ]);
    }

    public function update(Request $request, Route $route): RedirectResponse
    {
        $payload = $this->validatedPayload($request, $route);
        $previousPair = [
            'origin_airport_id' => $route->origin_airport_id,
            'destination_airport_id' => $route->destination_airport_id,
        ];

        DB::transaction(function () use ($route, $payload, $previousPair): void {
            $route->update($payload);

            if (
                $previousPair['origin_airport_id'] !== $route->origin_airport_id
                || $previousPair['destination_airport_id'] !== $route->destination_airport_id
            ) {
                $this->deleteWatchTargetForAirportPair(
                    $previousPair['origin_airport_id'],
                    $previousPair['destination_airport_id'],
                );
            }

            $this->syncWatchTarget($route);
        });

        return redirect()
            ->route('admin.routes.index')
            ->with('status', 'Route updated.');
    }

    public function destroy(Route $route): RedirectResponse
    {
        DB::transaction(function () use ($route): void {
            $this->deleteWatchTargetForAirportPair($route->origin_airport_id, $route->destination_airport_id);
            $route->delete();
        });

        return redirect()
            ->route('admin.routes.index')
            ->with('status', 'Route deleted.');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Airport>
     */
    private function airportOptions()
    {
        return Airport::query()
            ->with(['city.country'])
            ->orderBy('iata')
            ->get();
    }

    /**
     * @return array{origin_airport_id: int, destination_airport_id: int, active: bool, notes: string|null}
     */
    private function validatedPayload(Request $request, ?Route $route = null): array
    {
        $validated = $request->validate([
            'origin_airport_id' => [
                'required',
                'integer',
                'exists:airports,id',
                'different:destination_airport_id',
                Rule::unique('routes', 'origin_airport_id')
                    ->ignore($route?->id)
                    ->where(fn ($query) => $query->where('destination_airport_id', $request->integer('destination_airport_id'))),
            ],
            'destination_airport_id' => ['required', 'integer', 'exists:airports,id'],
            'active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        return [
            'origin_airport_id' => (int) $validated['origin_airport_id'],
            'destination_airport_id' => (int) $validated['destination_airport_id'],
            'active' => $request->boolean('active'),
            'notes' => $validated['notes'] ?? null,
        ];
    }

    private function syncWatchTarget(Route $route): void
    {
        $route->loadMissing(['originAirport.city', 'destinationAirport.city']);

        WatchTarget::query()->updateOrCreate(
            [
                'origin_city_id' => $route->originAirport->city_id,
                'origin_airport_id' => $route->origin_airport_id,
                'destination_city_id' => $route->destinationAirport->city_id,
                'destination_airport_id' => $route->destination_airport_id,
            ],
            [
                'enabled' => $route->active,
                'monitoring_priority' => 8,
                'date_window_days' => 10,
            ],
        );
    }

    private function deleteWatchTargetForAirportPair(int $originAirportId, int $destinationAirportId): void
    {
        $originAirport = Airport::query()->find($originAirportId);
        $destinationAirport = Airport::query()->find($destinationAirportId);

        if (! $originAirport || ! $destinationAirport) {
            return;
        }

        WatchTarget::query()
            ->where('origin_city_id', $originAirport->city_id)
            ->where('origin_airport_id', $originAirportId)
            ->where('destination_city_id', $destinationAirport->city_id)
            ->where('destination_airport_id', $destinationAirportId)
            ->delete();
    }
}
