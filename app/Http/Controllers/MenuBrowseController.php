<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\MenuModel;
use App\Models\SalesChannel;
use App\Services\MenuCatalogService;
use App\Services\StoreHoursService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MenuBrowseController extends Controller
{
    public function __construct(
        private MenuCatalogService $menuCatalog,
        private StoreHoursService $storeHours,
    ) {}

    public function index(): Response
    {
        $store = SalesChannel::store();
        $storeStatus = $this->storeHours->status();

        return Inertia::render('menu/index', [
            'menus' => $this->menuCatalog->allForBrowse($store),
            'categories' => $this->menuCatalog->categoriesForBrowse($store),
            'storeStatus' => $storeStatus,
            'nextOpening' => $storeStatus['is_open'] ? null : $this->storeHours->nextOpening(),
            'orderUrl' => $this->publicOrderUrl(),
        ]);
    }

    public function show(MenuModel $menuModel): Response
    {
        $store = SalesChannel::store();
        $menu = $this->menuCatalog->channelQuery($store)
            ->with(['addonGroups.options', 'categories:id,name'])
            ->find($menuModel->id);

        if ($menu === null) {
            throw new NotFoundHttpException;
        }

        $storeStatus = $this->storeHours->status();
        $menuData = $menu->toArray();
        unset($menuData['sales_channels']);

        return Inertia::render('menu/show', [
            'menu' => [
                ...$menuData,
                'price' => $menu->effectivePrice($store),
                'is_available' => $menu->effectiveIsAvailable($store),
                'pricing_type' => $menu->pricing_type,
            ],
            'storeStatus' => $storeStatus,
            'nextOpening' => $storeStatus['is_open'] ? null : $this->storeHours->nextOpening(),
            'orderUrl' => $this->publicOrderUrl($menu),
        ]);
    }

    private function publicOrderUrl(?MenuModel $menu = null): string
    {
        $target = $menu === null
            ? '/customer/menu'
            : '/customer/menu/'.$menu->id;

        if ($this->customerIsLoggedIn()) {
            return $target;
        }

        if ($menu === null) {
            return '/customer/login';
        }

        return '/customer/login?redirect='.rawurlencode($target);
    }

    private function customerIsLoggedIn(): bool
    {
        $customerId = session('customer_id');

        return is_numeric($customerId)
            && Customer::query()->whereKey($customerId)->exists();
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
