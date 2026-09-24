<?php

namespace App\Http\Middleware;

use App\Services\StoreHoursService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStoreIsOpen
{
    public function __construct(private StoreHoursService $storeHours) {}

    public function handle(Request $request, Closure $next): Response
    {
        $status = $this->storeHours->status();

        if ($status['is_open'] || $status['reason'] !== 'closed_period') {
            return $next($request);
        }

        if ($request->inertia() || $request->expectsJson()) {
            return redirect()
                ->route('home')
                ->with('error', $status['message']);
        }

        return redirect()->route('home');
    }
}
