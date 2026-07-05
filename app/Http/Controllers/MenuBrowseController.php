<?php

namespace App\Http\Controllers;

use App\Services\MenuCatalogService;
use Inertia\Inertia;
use Inertia\Response;

class MenuBrowseController extends Controller
{
    public function __construct(private MenuCatalogService $menuCatalog) {}

    public function index(): Response
    {
        return Inertia::render('menu/index', [
            'menus' => $this->menuCatalog->allForBrowse(),
        ]);
    }
}
