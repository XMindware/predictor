<?php

use App\Http\Controllers\Admin\OpsController;
use App\Http\Controllers\Admin\ProviderController;
use App\Http\Controllers\Admin\ManualOpsController;
use App\Http\Controllers\Admin\DataInspectionController;
use App\Http\Controllers\Admin\RouteManagementController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PersonalAccessTokenController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::post('/tokens', [PersonalAccessTokenController::class, 'store'])->name('tokens.store');
    Route::delete('/tokens/{token}', [PersonalAccessTokenController::class, 'destroy'])->name('tokens.destroy');
    Route::get('/admin/ops', [OpsController::class, 'index'])->name('admin.ops.index');
    Route::get('/admin/data-inspection', [DataInspectionController::class, 'index'])->name('admin.data-inspection.index');
    Route::post('/admin/ops/triggers/weather', [ManualOpsController::class, 'refetchWeather'])->name('admin.ops.triggers.weather');
    Route::post('/admin/ops/triggers/news', [ManualOpsController::class, 'refetchNews'])->name('admin.ops.triggers.news');
    Route::post('/admin/ops/triggers/flights', [ManualOpsController::class, 'refetchFlights'])->name('admin.ops.triggers.flights');
    Route::post('/admin/ops/triggers/indicators', [ManualOpsController::class, 'rebuildIndicators'])->name('admin.ops.triggers.indicators');
    Route::post('/admin/ops/triggers/risk', [ManualOpsController::class, 'recomputeRisk'])->name('admin.ops.triggers.risk');
    Route::post('/admin/ops/triggers/city-score', [ManualOpsController::class, 'queryCityScore'])->name('admin.ops.triggers.city-score');
    Route::post('/admin/providers/{provider}/test', [ProviderController::class, 'test'])->name('admin.providers.test');
    Route::resource('/admin/providers', ProviderController::class)
        ->except(['show'])
        ->names('admin.providers');
    Route::resource('/admin/routes', RouteManagementController::class)
        ->except(['show'])
        ->names('admin.routes');
});
