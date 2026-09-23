<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerCartItemRequest;
use App\Models\MenuModel;
use App\Models\SalesChannel;
use App\Services\CustomerCartService;
use App\Services\MenuOrderLineBuilder;
use App\Services\TransactionOrderService;
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
        $channel = SalesChannel::store();
        $menu = MenuModel::query()
            ->where('is_available', true)
            ->with([
                'addonGroups.options',
                'categories:id,name',
                'salesChannels' => fn ($query) => $query->where('sales_channels.id', $channel->id),
            ])
            ->find($menuModel->id);

        if ($menu === null || ! $menu->effectiveIsAvailable($channel)) {
            throw new NotFoundHttpException;
        }

        return Inertia::render('customer/menu/show', [
            'menu' => [
                ...$menu->toArray(),
                'price' => $menu->effectivePrice($channel),
                'pricing_type' => $menu->pricing_type,
            ],
            'serviceType' => session('service_type'),
        ]);
    }

    public function store(
        StoreCustomerCartItemRequest $request,
        MenuModel $menuModel,
    ): RedirectResponse {
        $channel = SalesChannel::store();
        $menu = MenuModel::query()
            ->where('is_available', true)
            ->with([
                'addonGroups.options',
                'salesChannels' => fn ($query) => $query->where('sales_channels.id', $channel->id),
            ])
            ->find($menuModel->id);

        if ($menu === null || ! $menu->effectiveIsAvailable($channel)) {
            throw new NotFoundHttpException;
        }

        $validated = $request->validated();
        $lineItem = $this->lineBuilder->build(
            $menu,
            (int) ($validated['quantity'] ?? 1),
            $validated['addon_option_ids'] ?? [],
            $channel,
            isset($validated['weight_grams']) ? (int) $validated['weight_grams'] : null,
        );
        $lineItem['note'] = TransactionOrderService::normalizeNote($validated['note'] ?? null);

        $this->cart->addItem($lineItem);

        $addedLabel = $menu->isWeightBased()
            ? "{$lineItem['weight_grams']}g {$lineItem['menu_name']}"
            : "{$lineItem['quantity']}× {$lineItem['menu_name']}";

        return redirect()
            ->route('customer.menu.index')
            ->with('success', "Added {$addedLabel} to your cart.");
    }
}
