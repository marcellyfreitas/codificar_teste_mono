<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
    });

    Route::prefix('tickets')->middleware('auth:sanctum')->name('tickets')->group(function () {
        Route::get('/', [TicketController::class, 'index']);
        Route::post('/', [TicketController::class, 'store']);
        Route::get('/{ticket}', [TicketController::class, 'show']);
        Route::put('/{ticket}', [TicketController::class, 'update']);
        Route::delete('/{ticket}', [TicketController::class, 'destroy']);
        Route::post('/balance', [TicketController::class, 'balance']);
        Route::post('/unassign-open', [TicketController::class, 'unassignOpen']);
    });
});
