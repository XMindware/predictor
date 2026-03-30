<?php

use App\Http\Controllers\Admin\CityImpactController;
use App\Http\Controllers\Admin\DataInspectionController;
use App\Http\Controllers\Admin\ManualOpsController;
use App\Http\Controllers\Admin\OpsController;
use App\Http\Controllers\Admin\ProviderController;
use App\Http\Controllers\Admin\RouteManagementController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PersonalAccessTokenController;
use App\Http\Controllers\SuperAdmin\DestinationController;
use App\Http\Controllers\SuperAdmin\MembershipPlanController;
use App\Http\Controllers\SuperAdmin\RssSourceController;
use App\Http\Controllers\SuperAdmin\UserManagementController;
use Illuminate\Support\Facades\Route;

// ─── Public (guest-only) ──────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
});

// ─── Authenticated ────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // Home → redirect to dashboard (dashboard IS the home for logged-in users)
    Route::get('/', fn () => redirect()->route('dashboard'));

    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // API tokens (members and above)
    Route::middleware('min_role:member')->group(function () {
        Route::post('/tokens', [PersonalAccessTokenController::class, 'store'])->name('tokens.store');
        Route::delete('/tokens/{token}', [PersonalAccessTokenController::class, 'destroy'])->name('tokens.destroy');
    });

    // ─── Admin panel (admin + super_admin) ────────────────────────────────────
    Route::middleware('min_role:admin')->prefix('admin')->group(function () {
        Route::get('/ops', [OpsController::class, 'index'])->name('admin.ops.index');
        Route::get('/data-inspection', [DataInspectionController::class, 'index'])->name('admin.data-inspection.index');

        Route::post('/ops/triggers/weather', [ManualOpsController::class, 'refetchWeather'])->name('admin.ops.triggers.weather');
        Route::post('/ops/triggers/news', [ManualOpsController::class, 'refetchNews'])->name('admin.ops.triggers.news');
        Route::post('/ops/triggers/flights', [ManualOpsController::class, 'refetchFlights'])->name('admin.ops.triggers.flights');
        Route::post('/ops/triggers/indicators', [ManualOpsController::class, 'rebuildIndicators'])->name('admin.ops.triggers.indicators');
        Route::post('/ops/triggers/risk', [ManualOpsController::class, 'recomputeRisk'])->name('admin.ops.triggers.risk');
        Route::post('/ops/triggers/city-score', [ManualOpsController::class, 'queryCityScore'])->name('admin.ops.triggers.city-score');
        Route::post('/ops/logs/clear', [ManualOpsController::class, 'clearLogs'])->name('admin.ops.logs.clear');

        Route::post('/providers/{provider}/test', [ProviderController::class, 'test'])->name('admin.providers.test');
        Route::resource('/providers', ProviderController::class)->except(['show'])->names('admin.providers');
        Route::resource('/routes', RouteManagementController::class)->except(['show'])->names('admin.routes');

        // ── City travel impact ────────────────────────────────────────────────
        Route::get('/cities', [CityImpactController::class, 'index'])->name('admin.cities.index');
        Route::get('/cities/{iata}', [CityImpactController::class, 'show'])->name('admin.cities.show');
        Route::post('/cities/{iata}/fetch', [CityImpactController::class, 'triggerFetch'])->name('admin.cities.trigger-fetch');
        Route::patch('/cities/watch-targets/{watchTarget}/toggle', [CityImpactController::class, 'toggleWatchTarget'])->name('admin.cities.watch-targets.toggle');
        Route::patch('/cities/watch-targets/{watchTarget}', [CityImpactController::class, 'updateWatchTarget'])->name('admin.cities.watch-targets.update');
    });

    // ─── Super Admin panel ────────────────────────────────────────────────────
    Route::middleware('role:super_admin')->prefix('super-admin')->name('super-admin.')->group(function () {

        // Monitored destinations management
        Route::get('/destinations', [DestinationController::class, 'index'])->name('destinations.index');
        Route::post('/destinations', [DestinationController::class, 'store'])->name('destinations.store');
        Route::patch('/destinations/{destination}/toggle', [DestinationController::class, 'toggle'])->name('destinations.toggle');
        Route::patch('/destinations/{destination}', [DestinationController::class, 'update'])->name('destinations.update');
        Route::delete('/destinations/{destination}', [DestinationController::class, 'destroy'])->name('destinations.destroy');

        // RSS news sources management
        Route::get('/rss-sources', [RssSourceController::class, 'index'])->name('rss-sources.index');
        Route::post('/rss-sources', [RssSourceController::class, 'store'])->name('rss-sources.store');
        Route::patch('/rss-sources/{rssSource}/toggle', [RssSourceController::class, 'toggle'])->name('rss-sources.toggle');
        Route::patch('/rss-sources/{rssSource}', [RssSourceController::class, 'update'])->name('rss-sources.update');
        Route::delete('/rss-sources/{rssSource}', [RssSourceController::class, 'destroy'])->name('rss-sources.destroy');

        // Membership plans CRUD
        Route::resource('memberships', MembershipPlanController::class)
            ->except(['show'])
            ->names('memberships');

        // User management
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [UserManagementController::class, 'show'])->name('users.show');
        Route::patch('/users/{user}/role', [UserManagementController::class, 'updateRole'])->name('users.update-role');
        Route::post('/users/{user}/memberships', [UserManagementController::class, 'assignMembership'])->name('users.memberships.store');
        Route::delete('/users/{user}/memberships/{membership}', [UserManagementController::class, 'cancelMembership'])->name('users.memberships.cancel');
    });
});
