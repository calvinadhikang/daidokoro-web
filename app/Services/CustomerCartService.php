<?php

namespace App\Services;

use App\Models\MenuModel;

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
     *     addons: array<int, array<string, mixed>>
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

    /**
     * @return list<array{
     *     menu_id: int,
     *     menu_name: string,
     *     quantity: int,
     *     unit_price: int,
     *     line_total: int,
     *     addon_option_ids: array<int, int>,
     *     addons: array<int, array<string, mixed>>
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
     *     addons: array<int, array<string, mixed>>
     * }  $item
     * @return array{
     *     menu_id: int,
     *     menu_name: string,
     *     quantity: int,
     *     unit_price: int,
     *     line_total: int,
     *     addon_option_ids: array<int, int>,
     *     addons: array<int, array<string, mixed>>
     * }
     */
    private function repriceItem(MenuModel $menu, array $item): array
    {
        $addonOptionIds = array_values(array_unique($item['addon_option_ids'] ?? []));
        $addons = [];
        $addonTotal = 0;

        foreach ($menu->addonGroups as $group) {
            foreach ($group->options as $option) {
                if (! in_array($option->id, $addonOptionIds, true)) {
                    continue;
                }

                $addons[] = [
                    'menu_addon_option_id' => $option->id,
                    'group_name' => $group->name,
                    'name' => $option->name,
                    'price' => $option->price,
                ];
                $addonTotal += $option->price;
            }
        }

        $quantity = (int) $item['quantity'];
        $unitPrice = $menu->price + $addonTotal;

        return [
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $unitPrice * $quantity,
            'addon_option_ids' => $addonOptionIds,
            'addons' => $addons,
        ];
    }
}
