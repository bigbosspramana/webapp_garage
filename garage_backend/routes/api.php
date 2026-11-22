<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RentalController;
use App\Http\Controllers\Api\VehicleController;
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

    // Vehicle Endpoints (Penyewa - Layar 3 & 4)
    Route::get('/vehicles', [VehicleController::class, 'index']);
    Route::post('/vehicles', [VehicleController::class, 'store']);
    Route::delete('/vehicles/{id}', [VehicleController::class, 'destroy']);

    // Sewa Garasi Endpoints (Penyewa - Layar 5, 6, 7)
    Route::get('/garages/available', [RentalController::class, 'getAvailableGarages']);
    Route::get('/rentals', [RentalController::class, 'index']);
    Route::post('/rentals', [RentalController::class, 'store']);

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

        // Perpanjang Sewa Garasi (Layar 11)
        Route::get('/active-rentals', [AdminController::class, 'getActiveRentals']);
        Route::post('/rentals/{id}/extend', [AdminController::class, 'extendRental']);

        // Laporan Sewa (Layar 12, 13, 14)
        Route::get('/reports', [AdminController::class, 'getReports']);
    });
});
