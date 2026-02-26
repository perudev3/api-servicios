<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AdminCategoryController;
use App\Http\Controllers\Api\ProfessionalController;

// ── Rutas públicas ─────────────────────────────────────────
Route::post('register', [AuthController::class, 'register']);
Route::post('login',    [AuthController::class, 'login']);
Route::get('/categories', function () {
    return \App\Models\Category::where('is_active', true)->get();
});

// ── Rutas autenticadas ─────────────────────────────────────
Route::middleware(['auth:api', 'active'])->group(function () {

    Route::get('profile',  [AuthController::class, 'profile']);
    Route::post('logout',  [AuthController::class, 'logout']);

    // ── Rutas solo admin ───────────────────────────────────
    Route::middleware('admin')->prefix('admin')->group(function () {

        // Usuarios
        Route::get('users',                            [AdminUserController::class, 'index']);
        Route::get('users/{user}',                     [AdminUserController::class, 'show']);
        Route::put('users/{user}',                     [AdminUserController::class, 'update']);
        Route::patch('users/{user}/toggle-status',     [AdminUserController::class, 'toggleStatus']);
        Route::post('users/bulk',                      [AdminUserController::class, 'bulk']);

        // Stats del dashboard
        Route::get('stats',                            [AdminUserController::class, 'stats']);


        // Categorias del Dashboard
        Route::apiResource('admin/categories', AdminCategoryController::class);
    });

    Route::middleware('professional')->prefix('professional')->group(function () {

        Route::get('/professional/dashboard', [ProfessionalController::class, 'dashboard']);

        Route::post('/professional/profile', [ProfessionalController::class, 'storeOrUpdate']);
    });
});