<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerCartItemRequest;
use App\Models\MenuModel;
use App\Services\CustomerCartService;
use App\Services\MenuOrderLineBuilder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomerMenuOrderController extends Controller
{
    public function __construct(
        private MenuOrderLineBuilder $lineBuilder,
        private CustomerCartService $cart,
    ) {}

    public function show(MenuModel $menuModel): Response
    {
        $menu = MenuModel::query()
            ->where('is_available', true)
            ->with(['addonGroups.options', 'categories:id,name'])
            ->find($menuModel->id);

        if ($menu === null) {
            throw new NotFoundHttpException;
        }

        return Inertia::render('customer/menu/show', [
            'menu' => $menu,
            'serviceType' => session('service_type'),
        ]);
    }

    public function store(
        StoreCustomerCartItemRequest $request,
        MenuModel $menuModel,
    ): RedirectResponse {
        $menu = MenuModel::query()
            ->where('is_available', true)
            ->with(['addonGroups.options'])
            ->find($menuModel->id);

        if ($menu === null) {
            throw new NotFoundHttpException;
        }

        $validated = $request->validated();

        $lineItem = $this->lineBuilder->build(
            $menu,
            $validated['quantity'],
            $validated['addon_option_ids'] ?? [],
        );

        $this->cart->addItem($lineItem);

        return redirect()
            ->route('customer.menu.index')
            ->with(
                'success',
                "Added {$lineItem['quantity']}× {$lineItem['menu_name']} to your order.",
            );
    }
}
