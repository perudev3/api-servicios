<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\ProfessionalController;
use App\Http\Controllers\Api\ServiceRequestController;
use App\Http\Controllers\Api\CityController;

// ── Rutas públicas ─────────────────────────────────────────
Route::post('register', [AuthController::class, 'register']);
Route::post('login',    [AuthController::class, 'login']);
Route::get('/categories', function () {
    return \App\Models\Category::where('is_active', true)->get();
});

Route::get('/services', function () {
    return \App\Models\Service::where('is_active', true)->get();
});

Route::get('/professionals', function () {
    return \App\Models\Professional::where('status', 'approved')->get();
});

// Ciudades del dashboard
Route::get('/cities', [CityController::class, 'index']);
Route::get('/cities/{id}', [CityController::class, 'show']); // 👈 agregar

// ── Rutas autenticadas ─────────────────────────────────────
Route::middleware(['auth:api', 'active'])->group(function () {

    Route::get('profile',  [AuthController::class, 'profile']);
    Route::post('logout',  [AuthController::class, 'logout']);

    // ── Rutas solo admin ───────────────────────────────────
    Route::middleware('admin')
            ->prefix('admin')
            ->group(function () {

                // Usuarios
                Route::get('users',                            [AdminUserController::class, 'index']);
                Route::get('users/{user}',                     [AdminUserController::class, 'show']);
                Route::put('users/{user}',                     [AdminUserController::class, 'update']);
                Route::patch('users/{user}/toggle-status',     [AdminUserController::class, 'toggleStatus']);
                Route::post('users/bulk',                      [AdminUserController::class, 'bulk']);

                // Stats del dashboard
                Route::get('stats',                            [AdminUserController::class, 'stats']);

                // Categoria del dashboard
                Route::get('/categories',[CategoryController::class,'index']);

                Route::post('/categories',[CategoryController::class,'store']);

                Route::put('/categories/{category}',[CategoryController::class,'update']);

                Route::delete('/categories/{category}',[CategoryController::class,'destroy']);

                // Servicios del dashboard
                Route::post('/services',[ServiceController::class,'store']);

                Route::put('/services/{service}',[ServiceController::class,'update']);

                Route::delete('/services/{service}',[ServiceController::class,'destroy']);

                
            });

    Route::middleware('professional')
        ->prefix('professional')
        ->group(function () {

            Route::get('/dashboard', [ProfessionalController::class, 'dashboard']);
            Route::post('/profile', [ProfessionalController::class, 'storeOrUpdate']);

            Route::get('/requests/available', [ServiceRequestController::class, 'available']);
            Route::post('/requests/{id}/accept', [ServiceRequestController::class, 'accept']);

        });

    Route::middleware('client')
        ->prefix('client')
        ->group(function() {
            Route::post('/service-request', [ServiceRequestController::class, 'store']);
            Route::get('/service-request/{id}/status', [ServiceRequestController::class, 'checkStatus']);
            Route::get('/professionals-available', [ProfessionalController::class, 'availableForClient']); // 👈
        });
});