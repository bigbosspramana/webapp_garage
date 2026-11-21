<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Sistem Sewa Garasi Kendaraan
|--------------------------------------------------------------------------
*/

// Public Authentication Routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected Routes (Memerlukan token Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Admin Management Endpoints
    Route::prefix('admin')->group(function () {
        Route::get('/pending-users', [AdminController::class, 'getPendingUsers']);
        Route::post('/users/{id}/approve', [AdminController::class, 'approveUser']);
        Route::post('/users/{id}/reject', [AdminController::class, 'rejectUser']);

        // CRUD & Batch Garasi
        Route::get('/garages', [AdminController::class, 'getGarages']);
        Route::post('/garages/batch', [AdminController::class, 'batchCreateGarages']);
        Route::put('/garages/{id}', [AdminController::class, 'updateGarage']);
        Route::delete('/garages/{id}', [AdminController::class, 'deleteGarage']);
    });
});
