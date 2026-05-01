<?php

use App\Http\Controllers\Api\TimeEntryController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', fn () => response()->json([
        'success' => true,
        'data' => ['status' => 'ok'],
    ]));

    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/login', [AuthenticatedSessionController::class, 'apiLogin']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', fn () => response()->json([
            'success' => true,
            'data' => ['user' => request()->user()],
        ]));
        Route::post('/logout', [AuthenticatedSessionController::class, 'apiLogout']);
        Route::post('/change-password', [UserController::class, 'changePassword']);

        Route::post('/time-entries/clock-in', [TimeEntryController::class, 'clockIn']);
        Route::post('/time-entries/clock-out', [TimeEntryController::class, 'clockOut']);
        Route::get('/time-entries', [TimeEntryController::class, 'index']);
        Route::post('/time-entries', [TimeEntryController::class, 'store']);
        Route::put('/time-entries/{timeEntry}', [TimeEntryController::class, 'update']);
        Route::delete('/time-entries/{timeEntry}', [TimeEntryController::class, 'destroy']);
    });
});
