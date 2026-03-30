<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\FailedJob;
use App\Models\IngestionRun;
use App\Models\Route;
use App\Services\ManualOpsService;
use App\Support\PlatformHealth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

    /**
     * Clear activity logs: all failed jobs, completed/failed ingestion runs older than 24h,
     * and the health alert cache. Resets the "Recent Activity" display on the dashboard.
     */
    public function clearLogs(Request $request): RedirectResponse
    {
        $failedJobsDeleted = FailedJob::query()->delete();

        $ingestionRunsDeleted = IngestionRun::query()
            ->whereIn('status', ['completed', 'failed'])
            ->where('finished_at', '<=', now()->subDay())
            ->delete();

        Cache::forget(PlatformHealth::FAILURE_ALERTS_CACHE_KEY);
        Cache::forget(PlatformHealth::STALE_DATA_REPORT_CACHE_KEY);

        $from = $request->input('redirect_back', 'dashboard');

        return redirect()
            ->route($from === 'ops' ? 'admin.ops.index' : 'dashboard')
            ->with('status', "Logs cleared — {$failedJobsDeleted} failed job(s) and {$ingestionRunsDeleted} old ingestion run(s) removed.");
    }

    public function queryCityScore(Request $request, ManualOpsService $manualOpsService): RedirectResponse
    {
        $validated = $request->validate([
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'time_window_hours' => ['nullable', 'integer', 'min:1', 'max:'.(int) config('operations.v1_risk_window_hours', 72)],
        ]);

        try {
            $city = City::query()->findOrFail($validated['city_id']);
            $result = $manualOpsService->queryCityScore(
                $city,
                isset($validated['time_window_hours']) ? (int) $validated['time_window_hours'] : null,
            );

            return redirect()
                ->route('admin.ops.index')
                ->with('status', "City risk query completed for {$result['city']}.")
                ->with('manual_tool_result', [
                    'tool' => 'query city risk',
                    'details' => $result,
                ]);
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.ops.index')
                ->with('error', $exception->getMessage());
        }
    }
}
