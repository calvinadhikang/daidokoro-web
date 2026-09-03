<?php

namespace App\Services;

use App\Models\MenuModel;
use App\Models\Transaction;
use App\Models\TransactionItem;

class TransactionOrderService
{
    public function __construct(private MenuOrderLineBuilder $lineBuilder) {}

    /**
     * @param  array<int, int>  $addonOptionIds
     */
    public function addMenuItem(
        Transaction $transaction,
        int $menuId,
        int $quantity,
        array $addonOptionIds,
        ?string $note = null,
    ): TransactionItem {
        $menu = MenuModel::query()
            ->where('is_available', true)
            ->with(['addonGroups.options'])
            ->findOrFail($menuId);

        $lineItem = $this->lineBuilder->build($menu, $quantity, $addonOptionIds);
        $lineItem['note'] = self::normalizeNote($note);

        return $this->addLineItem($transaction, $lineItem);
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
     * }  $lineItem
     */
    public function addLineItem(Transaction $transaction, array $lineItem): TransactionItem
    {
        $item = TransactionItem::query()->create([
            'transaction_id' => $transaction->id,
            'menu_id' => $lineItem['menu_id'],
            'menu_name' => $lineItem['menu_name'],
            'quantity' => $lineItem['quantity'],
            'unit_price' => $lineItem['unit_price'],
            'line_total' => $lineItem['line_total'],
            'addons' => $lineItem['addons'],
            'note' => self::normalizeNote($lineItem['note'] ?? null),
        ]);

        $transaction->recalculateTotal();

        return $item;
    }

    public static function normalizeNote(?string $note): ?string
    {
        if ($note === null) {
            return null;
        }

        $trimmed = trim($note);

        return $trimmed === '' ? null : $trimmed;
    }
}
