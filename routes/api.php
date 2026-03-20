<?php

use App\Http\Controllers\Api\ApiTokenController;
use App\Http\Controllers\Api\AuthenticatedUserController;
use App\Http\Controllers\Api\RiskAssessmentController;
use App\Http\Controllers\Api\RouteRiskController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);
Route::post('/tokens', [ApiTokenController::class, 'store'])->name('api.tokens.store');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', AuthenticatedUserController::class)->name('api.user');
    Route::get('/tokens', [ApiTokenController::class, 'index'])->name('api.tokens.index');
    Route::delete('/tokens/{token}', [ApiTokenController::class, 'destroy'])->name('api.tokens.destroy');
    Route::post('/risk-assessment', RiskAssessmentController::class)->name('api.risk-assessment');
    Route::get('/routes/risk', RouteRiskController::class)->name('api.routes.risk');
});
