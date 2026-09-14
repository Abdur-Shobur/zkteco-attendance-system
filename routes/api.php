<?php

use App\Http\Controllers\SetupController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
| Public setup routes (no login). Require SETUP_KEY via ?key= or X-Setup-Key header.
| Examples:
|   GET /api/setup/migrate?key=YOUR_SECRET
|   GET /api/setup/seed?key=YOUR_SECRET
|   GET /api/setup/seed?key=YOUR_SECRET&class=UserSeeder
|   GET /api/setup/migrate-seed?key=YOUR_SECRET
|   GET /api/setup/status?key=YOUR_SECRET
*/
Route::prefix('setup')->group(function () {
    Route::match(['get', 'post'], '/migrate', [SetupController::class, 'migrate']);
    Route::match(['get', 'post'], '/seed', [SetupController::class, 'seed']);
    Route::match(['get', 'post'], '/migrate-seed', [SetupController::class, 'migrateAndSeed']);
    Route::match(['get', 'post'], '/status', [SetupController::class, 'status']);
});
