<?php

namespace App\Services;

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

    public function count(): int
    {
        return count($this->items());
    }
}
