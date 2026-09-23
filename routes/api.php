<?php

use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\CustomerApiController;
use App\Http\Controllers\Api\MenuApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\OperatingHoursApiController;
use App\Http\Controllers\Api\ReportApiController;
use App\Http\Controllers\Api\SalesChannelApiController;
use App\Http\Controllers\Api\TransactionApiController;
use App\Http\Controllers\OmakaseController;
use Illuminate\Support\Facades\Route;

Route::prefix('menu')->group(function () {
    Route::get('/', [MenuApiController::class, 'index']);
    Route::get('/categories', [CategoryApiController::class, 'index']);
    Route::post('/categories/create', [CategoryApiController::class, 'store']);
    Route::post('/categories/update/{category}', [CategoryApiController::class, 'update']);
    Route::post('/categories/delete/{category}', [CategoryApiController::class, 'destroy']);
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
    Route::post('/update/{transaction}', [TransactionApiController::class, 'update']);
    Route::post('/items/create/{transaction}', [TransactionApiController::class, 'storeItem']);
    Route::post('/items/update/{item}', [TransactionApiController::class, 'updateItem']);
    Route::post('/items/delete/{item}', [TransactionApiController::class, 'destroyItem']);
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

Route::prefix('report')->group(function () {
    Route::get('/sales', [ReportApiController::class, 'sales']);
    Route::get('/event-sales', [ReportApiController::class, 'eventSales']);
    Route::get('/menus', [ReportApiController::class, 'menus']);
});

Route::prefix('customer')->group(function () {
    Route::get('/', [CustomerApiController::class, 'index']);
    Route::get('/detail/{customer}', [CustomerApiController::class, 'show']);
    Route::post('/create', [CustomerApiController::class, 'store']);
    Route::post('/update/{customer}', [CustomerApiController::class, 'update']);
});

Route::prefix('channels')->group(function () {
    Route::get('/', [SalesChannelApiController::class, 'index']);
    Route::get('/current', [SalesChannelApiController::class, 'current']);
    Route::get('/events/report-list', [SalesChannelApiController::class, 'eventsForReport']);
    Route::get('/events/{salesChannel}', [SalesChannelApiController::class, 'showEvent']);
    Route::post('/events/create', [SalesChannelApiController::class, 'storeEvent']);
    Route::post('/events/update/{salesChannel}', [SalesChannelApiController::class, 'updateEvent']);
    Route::post('/events/archive/{salesChannel}', [SalesChannelApiController::class, 'archiveEvent']);
    Route::post('/events/unarchive/{salesChannel}', [SalesChannelApiController::class, 'unarchiveEvent']);
    Route::get('/{salesChannel}/menus', [SalesChannelApiController::class, 'menus']);
    Route::post('/{salesChannel}/menus/assign', [SalesChannelApiController::class, 'assignMenus']);
    Route::post('/{salesChannel}/menus/update/{menuModel}', [SalesChannelApiController::class, 'updateMenu']);
    Route::post('/{salesChannel}/menus/unassign/{menuModel}', [SalesChannelApiController::class, 'unassignMenu']);
});

Route::get('/ping', function () {
    return response()->json([
        'ok' => true,
        'message' => 'pong',
        'time' => now()->toIso8601String(),
    ]);
});
