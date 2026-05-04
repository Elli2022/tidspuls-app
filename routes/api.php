<?php

use App\Http\Controllers\Api\LocationLogController;
use App\Http\Controllers\Api\OrganizationMemberController;
use App\Http\Controllers\Api\PasswordResetApiController;
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

    Route::post('/forgot-password', [PasswordResetApiController::class, 'forgot'])
        ->middleware('throttle:6,1');
    Route::post('/reset-password', [PasswordResetApiController::class, 'reset'])
        ->middleware('throttle:12,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', fn () => response()->json([
            'success' => true,
            'data' => ['user' => request()->user()->load('organization')],
        ]));
        Route::get('/organization/members', [OrganizationMemberController::class, 'index']);
        Route::post('/logout', [AuthenticatedSessionController::class, 'apiLogout']);
        Route::post('/change-password', [UserController::class, 'changePassword']);

        Route::get('/location-logs', [LocationLogController::class, 'index']);
        Route::post('/location-logs', [LocationLogController::class, 'store']);

        Route::get('/time-entries/pending-review', [TimeEntryController::class, 'pendingReview']);
        Route::post('/time-entries/{timeEntry}/submit', [TimeEntryController::class, 'submit']);
        Route::post('/time-entries/{timeEntry}/approve', [TimeEntryController::class, 'approve']);
        Route::post('/time-entries/{timeEntry}/reject', [TimeEntryController::class, 'reject']);

        Route::post('/time-entries/clock-in', [TimeEntryController::class, 'clockIn']);
        Route::post('/time-entries/clock-out', [TimeEntryController::class, 'clockOut']);
        Route::get('/time-entries', [TimeEntryController::class, 'index']);
        Route::post('/time-entries', [TimeEntryController::class, 'store']);
        Route::put('/time-entries/{timeEntry}', [TimeEntryController::class, 'update']);
        Route::delete('/time-entries/{timeEntry}', [TimeEntryController::class, 'destroy']);
    });
});
