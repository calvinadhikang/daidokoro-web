<?php

use App\Http\Controllers\Api\ManjoQrisNotificationController;
use App\Http\Middleware\EnsureCustomerSession;
use App\Http\Middleware\EnsureStoreIsOpen;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::post('/v1.0/access-token/b2b', [ManjoQrisNotificationController::class, 'token']);
            Route::post('/v1.0/qr/qr-mpm-notify', [ManjoQrisNotificationController::class, 'store']);
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/admin/login');

        $middleware->alias([
            'customer' => EnsureCustomerSession::class,
            'store.open' => EnsureStoreIsOpen::class,
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
