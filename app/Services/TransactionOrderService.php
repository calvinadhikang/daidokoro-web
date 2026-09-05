<?php

namespace App\Services;

use App\Models\MenuModel;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionOrderService
{
    public function __construct(private MenuOrderLineBuilder $lineBuilder) {}

    /**
     * @param  array{
     *     customer_name: string,
     *     customer_phone: string,
     *     service_type?: string|null,
     *     items?: list<array{
     *         menu_id: int,
     *         quantity: int,
     *         addon_option_ids?: array<int, int>,
     *         note?: string|null
     *     }>
     * }  $data
     */
    public function createAdminTransaction(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $transaction = Transaction::query()->create([
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'service_type' => $data['service_type'] ?? 'dine_in',
                'status' => 'in_progress',
                'total_bill' => 0,
                'is_admin_created' => true,
            ]);

            foreach ($data['items'] ?? [] as $itemData) {
                $this->addMenuItem(
                    $transaction,
                    $itemData['menu_id'],
                    $itemData['quantity'],
                    $itemData['addon_option_ids'] ?? [],
                    $itemData['note'] ?? null,
                );
            }

            return $transaction->fresh() ?? $transaction;
        });
    }

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
            ->find($menuId);

        if ($menu === null) {
            $named = MenuModel::query()->find($menuId);
            $label = $named?->name ?? "Menu #{$menuId}";

            throw ValidationException::withMessages([
                'items' => "{$label} is no longer available.",
            ]);
        }

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
        return DB::transaction(function () use ($transaction, $lineItem) {
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
        });
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
