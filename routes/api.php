<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// NOTE: This route returns all users; keep it admin-only.
// Route::middleware(['auth:sanctum', 'role:admin'])->get('/user', function (Request $request) {
//     return User::all();
// });

// ─── Public Routes ───────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

Route::get('/categories', [CategoryController::class, 'index']);

// ─── Protected Routes (requires Sanctum token) ───────────────────────────────
Route::prefix('auth')->middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);
});

// ─── Admin Routes (Protected: auth:sanctum, role:admin) ───────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::apiResource('users', UserController::class)->only([
        'index',
        'store',
        'update',
        'destroy',
    ]);

    Route::apiResource('categories', CategoryController::class)->only([
        'store',
        'update',
        'destroy',
    ]);
});

