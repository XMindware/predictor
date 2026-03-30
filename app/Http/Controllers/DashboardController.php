<?php

namespace App\Http\Controllers;

use App\Models\MonitoredDestination;
use App\Services\OperationsMonitoringService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, OperationsMonitoringService $operationsMonitoringService): View
    {
        $user = $request->user()->load('activeMembership.plan');

        // Active monitored destinations (falls back to env config if none in DB)
        $monitoredDestinations = MonitoredDestination::query()
            ->with('airport.city')
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->orderBy('iata')
            ->get();

        if ($monitoredDestinations->isEmpty()) {
            $fallbackIatas = config('operations.base_airports', ['CUN']);
            $fallbackIatas = is_array($fallbackIatas) ? $fallbackIatas : [$fallbackIatas];
        } else {
            $fallbackIatas = [];
        }

        return view('dashboard', [
            ...$operationsMonitoringService->dashboardData(),
            'plainTextToken'        => session('plain_text_token'),
            'tokens'                => $request->user()->tokens()->latest()->get(),
            'user'                  => $user,
            'monitoredDestinations' => $monitoredDestinations,
            'fallbackIatas'         => $fallbackIatas,
        ]);
    }
}
