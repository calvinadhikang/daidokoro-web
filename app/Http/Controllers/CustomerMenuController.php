<?php

namespace App\Http\Controllers;

use App\Services\MenuCatalogService;
use Inertia\Inertia;
use Inertia\Response;

class CustomerMenuController extends Controller
{
    public function __construct(private MenuCatalogService $menuCatalog) {}

    public function index(): Response
    {
        return Inertia::render('customer/menu/index', [
            'menus' => $this->menuCatalog->availableForOrdering(),
            'categories' => $this->menuCatalog->categoriesForOrdering(),
            'serviceType' => session('service_type'),
        ]);
    }
}
