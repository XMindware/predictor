<?php

namespace App\Http\Controllers;

use App\Services\OperationsMonitoringService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, OperationsMonitoringService $operationsMonitoringService): View
    {
        return view('dashboard', [
            ...$operationsMonitoringService->dashboardData(),
            'plainTextToken' => session('plain_text_token'),
            'tokens' => $request->user()->tokens()->latest()->get(),
            'user' => $request->user(),
        ]);
    }
}
