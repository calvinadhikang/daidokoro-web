<?php

use App\Http\Controllers\Api\MenuApiController;
use App\Http\Controllers\Api\TransactionApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('menu')->group(function () {
    Route::get('/', [MenuApiController::class, 'index']);
    Route::get('/categories', [MenuApiController::class, 'categories']);
    Route::get('/detail/{menuModel}', [MenuApiController::class, 'show']);
    Route::post('/create', [MenuApiController::class, 'store']);
    Route::post('/update/{menuModel}', [MenuApiController::class, 'update']);
    Route::post('/delete/{menuModel}', [MenuApiController::class, 'destroy']);
});

Route::prefix('transaction')->group(function () {
    Route::get('/today', [TransactionApiController::class, 'today']);
    Route::get('/detail/{transaction}', [TransactionApiController::class, 'detail']);
    Route::post('/mark-paid/{transaction}', [TransactionApiController::class, 'markPaid']);
    Route::post('/delete/{transaction}', [TransactionApiController::class, 'destroy']);
});

Route::get('/ping', function () {
    return response()->json([
        'ok' => true,
        'message' => 'pong',
        'time' => now()->toIso8601String(),
    ]);
});
