<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Route;
use App\Services\ManualOpsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class ManualOpsController extends Controller
{
    public function refetchWeather(Request $request, ManualOpsService $manualOpsService): RedirectResponse
    {
        $validated = $request->validate([
            'city_id' => ['required', 'integer', 'exists:cities,id'],
        ]);

        try {
            $city = City::query()->findOrFail($validated['city_id']);
            $result = $manualOpsService->refetchWeatherForCity($city);

            return redirect()
                ->route('admin.ops.index')
                ->with('status', "Weather re-fetch completed for {$result['city']}.")
                ->with('manual_tool_result', [
                    'tool' => 're-fetch weather',
                    'details' => $result,
                ]);
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.ops.index')
                ->with('error', $exception->getMessage());
        }
    }

    public function refetchFlights(Request $request, ManualOpsService $manualOpsService): RedirectResponse
    {
        $validated = $request->validate([
            'route_id' => ['required', 'integer', 'exists:routes,id'],
        ]);

        try {
            $route = Route::query()->findOrFail($validated['route_id']);
            $result = $manualOpsService->refetchFlightsForRoute($route);

            return redirect()
                ->route('admin.ops.index')
                ->with('status', "Flight re-fetch completed for {$result['route']}.")
                ->with('manual_tool_result', [
                    'tool' => 're-fetch flights',
                    'details' => $result,
                ]);
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.ops.index')
                ->with('error', $exception->getMessage());
        }
    }

    public function refetchNews(Request $request, ManualOpsService $manualOpsService): RedirectResponse
    {
        $validated = $request->validate([
            'city_id' => ['required', 'integer', 'exists:cities,id'],
        ]);

        try {
            $city = City::query()->findOrFail($validated['city_id']);
            $result = $manualOpsService->refetchNewsForCity($city);

            return redirect()
                ->route('admin.ops.index')
                ->with('status', "News re-fetch completed for {$result['city']}.")
                ->with('manual_tool_result', [
                    'tool' => 're-fetch news',
                    'details' => $result,
                ]);
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.ops.index')
                ->with('error', $exception->getMessage());
        }
    }

    public function rebuildIndicators(ManualOpsService $manualOpsService): RedirectResponse
    {
        try {
            $result = $manualOpsService->rebuildIndicators();

            return redirect()
                ->route('admin.ops.index')
                ->with('status', 'Indicator rebuild completed.')
                ->with('manual_tool_result', [
                    'tool' => 'rebuild indicators',
                    'details' => $result,
                ]);
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.ops.index')
                ->with('error', $exception->getMessage());
        }
    }

    public function recomputeRisk(Request $request, ManualOpsService $manualOpsService): RedirectResponse
    {
        $validated = $request->validate([
            'route_id' => ['required', 'integer', 'exists:routes,id'],
            'travel_date' => ['required', 'date'],
        ]);

        try {
            $route = Route::query()->with(['originAirport', 'destinationAirport'])->findOrFail($validated['route_id']);
            $result = $manualOpsService->recomputeRisk($route, $validated['travel_date']);

            return redirect()
                ->route('admin.ops.index')
                ->with('status', 'Risk recompute completed.')
                ->with('manual_tool_result', [
                    'tool' => 'recompute risk',
                    'details' => $result,
                ]);
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.ops.index')
                ->with('error', $exception->getMessage());
        }
    }

    public function queryCityScore(Request $request, ManualOpsService $manualOpsService): RedirectResponse
    {
        $validated = $request->validate([
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'query_date' => ['nullable', 'date'],
        ]);

        try {
            $city = City::query()->findOrFail($validated['city_id']);
            $result = $manualOpsService->queryCityScore($city, $validated['query_date'] ?? null);

            return redirect()
                ->route('admin.ops.index')
                ->with('status', "City score query completed for {$result['city']}.")
                ->with('manual_tool_result', [
                    'tool' => 'query city score',
                    'details' => $result,
                ]);
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.ops.index')
                ->with('error', $exception->getMessage());
        }
    }
}
