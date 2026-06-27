<?php

namespace App\Http\Controllers;

use App\Services\StoreHoursService;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(private StoreHoursService $storeHours) {}

    public function index(): Response
    {
        return Inertia::render('home', [
            'storeStatus' => $this->storeHours->status(),
            'nextSession' => $this->storeHours->nextSessionToday(),
        ]);
    }
}
