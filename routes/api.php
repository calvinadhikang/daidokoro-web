<?php

use App\Http\Controllers\Api\MenuApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\OperatingHoursApiController;
use App\Http\Controllers\Api\TransactionApiController;
use App\Http\Controllers\OmakaseController;
use Illuminate\Support\Facades\Route;

Route::prefix('menu')->group(function () {
    Route::get('/', [MenuApiController::class, 'index']);
    Route::get('/categories', [MenuApiController::class, 'categories']);
    Route::get('/detail/{menuModel}', [MenuApiController::class, 'show']);
    Route::post('/create', [MenuApiController::class, 'store']);
    Route::post('/update/{menuModel}', [MenuApiController::class, 'update']);
    Route::post('/toggle-availability/{menuModel}', [MenuApiController::class, 'toggleAvailability']);
    Route::post('/delete/{menuModel}', [MenuApiController::class, 'destroy']);
});

Route::prefix('transaction')->group(function () {
    Route::get('/today', [TransactionApiController::class, 'today']);
    Route::get('/next-number', [TransactionApiController::class, 'nextNumber']);
    Route::get('/detail/{transaction}', [TransactionApiController::class, 'detail']);
    Route::post('/create', [TransactionApiController::class, 'store']);
    Route::post('/mark-paid/{transaction}', [TransactionApiController::class, 'markPaid']);
    Route::post('/delete/{transaction}', [TransactionApiController::class, 'destroy']);
});

Route::prefix('notification')->group(function () {
    Route::post('/register', [NotificationApiController::class, 'register']);
    Route::get('/test', [NotificationApiController::class, 'testBroadcast']);
    Route::post('/test', [NotificationApiController::class, 'test']);
});

Route::prefix('omakase')->group(function () {
    Route::get('/session', [OmakaseController::class, 'getOmakaseSessions']);
    Route::get('/session/{id}', [OmakaseController::class, 'getOmakaseSessionById']);
    Route::post('/session/create', [OmakaseController::class, 'createOmakaseSession']);
    Route::post('/session/update/{id}', [OmakaseController::class, 'updateOmakaseSession']);
    Route::post('/session/delete/{id}', [OmakaseController::class, 'deleteOmakaseSession']);

    Route::post('/menu/create', [OmakaseController::class, 'createOmakaseMenu']);
    Route::post('/menu/delete/{id}', [OmakaseController::class, 'deleteOmakaseMenu']);
    Route::post('/menu/update/{id}', [OmakaseController::class, 'updateOmakaseMenu']);
});

Route::prefix('hours')->group(function () {
    Route::get('/', [OperatingHoursApiController::class, 'index']);
    Route::post('/update', [OperatingHoursApiController::class, 'update']);
    Route::post('/closures/create', [OperatingHoursApiController::class, 'storeClosure']);
    Route::post('/closures/delete/{closure}', [OperatingHoursApiController::class, 'destroyClosure']);
});

Route::get('/ping', function () {
    return response()->json([
        'ok' => true,
        'message' => 'pong',
        'time' => now()->toIso8601String(),
    ]);
});
