<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdmsController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('attendance.index')
        : redirect()->route('login');
});

// ZKTeco ADMS / iClock — must stay public for the device
Route::match(['get', 'post'], '/iclock/cdata', [AdmsController::class, 'cdata'])->name('adms.cdata');
Route::get('/iclock/getrequest', [AdmsController::class, 'getRequest'])->name('adms.getrequest');
Route::post('/iclock/devicecmd', [AdmsController::class, 'deviceCmd'])->name('adms.devicecmd');

// Guest auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Protected admin panel
Route::middleware(['auth', 'admin'])->prefix('attendance')->name('attendance.')->group(function () {
    Route::get('/', [AttendanceController::class, 'index'])->name('index');
    Route::get('/logs-page', [AttendanceController::class, 'logsPage'])->name('logs.page');
    Route::get('/users', [AttendanceController::class, 'usersPage'])->name('users.page');
    Route::get('/users/create', [AttendanceController::class, 'createUserPage'])->name('users.create');
    Route::post('/users', [AttendanceController::class, 'storeUser'])->name('users.store');
    Route::get('/users/{user}/edit', [AttendanceController::class, 'editUserPage'])->name('users.edit');
    Route::put('/users/{user}', [AttendanceController::class, 'updateUser'])->name('users.update');
    Route::get('/profile', [AttendanceController::class, 'profilePage'])->name('profile.page');
    Route::put('/profile', [AttendanceController::class, 'updateProfile'])->name('profile.update');
    Route::get('/device', [AttendanceController::class, 'devicePage'])->name('device.page');
    Route::get('/settings', [AttendanceController::class, 'settingsPage'])->name('settings.page');
    Route::post('/settings', [AttendanceController::class, 'updateSettings'])->name('settings.update');
    Route::get('/report', [AttendanceController::class, 'report'])->name('report');
    Route::get('/report/export', [AttendanceController::class, 'exportReport'])->name('report.export');

    Route::get('/test-connection', [AttendanceController::class, 'testConnection'])->name('test-connection');
    Route::get('/device-info', [AttendanceController::class, 'getDeviceInfo'])->name('device-info');
    Route::get('/device-users', [AttendanceController::class, 'getDeviceUsers'])->name('device-users');
    Route::post('/sync-device-users', [AttendanceController::class, 'syncDeviceUsers'])->name('sync-device-users');

    Route::post('/sync', [AttendanceController::class, 'syncLogs'])->name('sync');
    Route::get('/logs', [AttendanceController::class, 'getLogs'])->name('logs');
    Route::post('/mark-processed', [AttendanceController::class, 'markAsProcessed'])->name('mark-processed');
    Route::delete('/clear-device-logs', [AttendanceController::class, 'clearDeviceLogs'])->name('clear-device-logs');

    Route::get('/realtime-sync', [AttendanceController::class, 'realtimeSync'])->name('realtime-sync');
    Route::get('/user/{userId}/summary', [AttendanceController::class, 'getUserSummary'])->name('user-summary');
    Route::get('/export', [AttendanceController::class, 'exportLogs'])->name('export');
});
