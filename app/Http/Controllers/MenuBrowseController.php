<?php

namespace App\Http\Controllers;

use App\Models\MenuModel;
use App\Services\MenuCatalogService;
use App\Services\StoreHoursService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MenuBrowseController extends Controller
{
    public function __construct(
        private MenuCatalogService $menuCatalog,
        private StoreHoursService $storeHours,
    ) {}

    public function index(): Response
    {
        return Inertia::render('menu/index', [
            'menus' => $this->menuCatalog->allForBrowse(),
            'categories' => $this->menuCatalog->categoriesForBrowse(),
            'storeStatus' => $this->storeHours->status(),
        ]);
    }

    public function toggleAvailability(MenuModel $menuModel): RedirectResponse
    {
        $menuModel->update([
            'is_available' => ! $menuModel->is_available,
        ]);

        return redirect()
            ->route('menu.index')
            ->with('success', "{$menuModel->name} is now ".($menuModel->is_available ? 'available' : 'unavailable').'.');
    }
}
