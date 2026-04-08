<?php

use App\Http\Controllers\Api\TableController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\MenuItemImageController;
use App\Http\Controllers\Order\OrderController;
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
Route::get('/menu-items', [MenuItemController::class, 'index']);
Route::get('/menu-items/{id}', [MenuItemController::class, 'show']);
Route::get('/menu-items/{menu_item}/images', [MenuItemImageController::class, 'index']);

Route::get('/tables', [TableController::class, 'index']);
Route::get('/tables/{table}', [TableController::class, 'show']);


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

    Route::post('/menu-items', [MenuItemController::class, 'store']);
    Route::patch('/menu-items/{menu_item}', [MenuItemController::class, 'update']);
    Route::delete('/menu-items/{menu_item}', [MenuItemController::class, 'destroy']);
    Route::patch('/menu-items/{menu_item}/availability', [MenuItemController::class, 'toggleAvailability']);

    Route::post('/menu-items/{menu_item}/images', [MenuItemImageController::class, 'store']);
    Route::delete('/menu-items/{menu_item}/images/{image}', [MenuItemImageController::class, 'destroy']);
});

// ─── Tables Routes ───────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('role:admin')->group(function () {
        Route::post('/tables', [TableController::class, 'store']);
        Route::put('/tables/{table}', [TableController::class, 'update']);

    });
    Route::middleware('role:admin,cashier')->patch('/tables/{table}/status', [TableController::class, 'updateStatus']);
});

// ─── Orders Routes ───────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('role:admin,cashier')->get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::middleware('role:cashier')->get('/orders/table/{table_id}', [OrderController::class, 'showByTable']);
    Route::middleware('role:cashier,admin')->post('/orders', [OrderController::class, 'store']);
    Route::middleware('role:cashier')->post('/orders/{id}/items', [OrderController::class, 'addItem']);
    Route::middleware('role:kitchen')->patch('/orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::middleware('role:cashier')->delete('/orders/{id}', [OrderController::class, 'destroy']);
});
