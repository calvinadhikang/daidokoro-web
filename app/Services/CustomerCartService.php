<?php

namespace App\Services;

use App\Models\MenuModel;
use App\Models\SalesChannel;

class CustomerCartService
{
    private const SESSION_KEY = 'customer_cart';

    /**
     * @param  array{
     *     menu_id: int,
     *     menu_name: string,
     *     quantity: int,
     *     unit_price: int,
     *     line_total: int,
     *     addon_option_ids: array<int, int>,
     *     addons: array<int, array<string, mixed>>,
     *     note?: string|null
     * }  $item
     */
    public function addItem(array $item): void
    {
        $cart = $this->items();
        $cart[] = $item;

        session([self::SESSION_KEY => $cart]);
    }

    public function removeItem(int $index): bool
    {
        $cart = $this->items();

        if (! array_key_exists($index, $cart)) {
            return false;
        }

        unset($cart[$index]);
        session([self::SESSION_KEY => array_values($cart)]);

        return true;
    }

    public function updateQuantity(int $index, int $quantity): bool
    {
        $cart = $this->items();

        if (! array_key_exists($index, $cart)) {
            return false;
        }

        $cart[$index]['quantity'] = $quantity;
        $cart[$index]['line_total'] = ((int) $cart[$index]['unit_price']) * $quantity;
        session([self::SESSION_KEY => $cart]);

        return true;
    }

    /**
     * @return list<array{
     *     menu_id: int,
     *     menu_name: string,
     *     quantity: int,
     *     unit_price: int,
     *     line_total: int,
     *     addon_option_ids: array<int, int>,
     *     addons: array<int, array<string, mixed>>,
     *     note?: string|null
     * }>
     */
    public function items(): array
    {
        /** @var list<array<string, mixed>>|null $cart */
        $cart = session(self::SESSION_KEY);

        return is_array($cart) ? $cart : [];
    }

    /**
     * Rebuild cart line prices from the current menu / addon prices.
     */
    public function syncPrices(): void
    {
        $items = $this->items();

        if ($items === []) {
            return;
        }

        $menuIds = array_values(array_unique(array_column($items, 'menu_id')));
        $menus = MenuModel::query()
            ->with(['addonGroups.options'])
            ->whereIn('id', $menuIds)
            ->get()
            ->keyBy('id');

        $synced = [];

        foreach ($items as $item) {
            /** @var MenuModel|null $menu */
            $menu = $menus->get($item['menu_id']);

            $synced[] = $menu === null
                ? $item
                : $this->repriceItem($menu, $item);
        }

        session([self::SESSION_KEY => $synced]);
    }

    public function count(): int
    {
        return count($this->items());
    }

    public function quantity(): int
    {
        return (int) array_sum(array_column($this->items(), 'quantity'));
    }

    public function total(): int
    {
        return array_sum(array_column($this->items(), 'line_total'));
    }

    public function isEmpty(): bool
    {
        return $this->items() === [];
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * @param  array{
     *     menu_id: int,
     *     menu_name: string,
     *     quantity: int,
     *     unit_price: int,
     *     line_total: int,
     *     addon_option_ids?: array<int, int>,
     *     addons: array<int, array<string, mixed>>,
     *     note?: string|null
     * }  $item
     * @return array{
     *     menu_id: int,
     *     menu_name: string,
     *     quantity: int,
     *     unit_price: int,
     *     line_total: int,
     *     addon_option_ids: array<int, int>,
     *     addons: array<int, array<string, mixed>>,
     *     note: string|null
     * }
     */
    private function repriceItem(MenuModel $menu, array $item): array
    {
        $addonOptionIds = array_values(array_unique($item['addon_option_ids'] ?? []));
        $quantity = (int) $item['quantity'];
        $weightGrams = isset($item['weight_grams']) ? (int) $item['weight_grams'] : null;
        $channel = SalesChannel::store();
        $line = app(MenuOrderLineBuilder::class)->build(
            $menu,
            $quantity,
            $addonOptionIds,
            $channel,
            $weightGrams,
        );

        return [
            'menu_id' => $line['menu_id'],
            'menu_name' => $line['menu_name'],
            'quantity' => $line['quantity'],
            'weight_grams' => $line['weight_grams'],
            'pricing_type' => $line['pricing_type'],
            'unit_price' => $line['unit_price'],
            'line_total' => $line['line_total'],
            'addon_option_ids' => $addonOptionIds,
            'addons' => $line['addons'],
            'note' => TransactionOrderService::normalizeNote(
                isset($item['note']) && is_string($item['note']) ? $item['note'] : null,
            ),
        ];
    }
}
