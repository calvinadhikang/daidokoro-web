<?php

namespace App\Support;

use App\Models\Transaction;
use App\Models\TransactionItem;

class TransactionApiFormatter
{
    /**
     * @return array<string, mixed>
     */
    public static function formatListItem(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'transaction_number' => self::formatTransactionNumber($transaction),
            'name' => $transaction->customer_name,
            'status' => $transaction->status,
            'total_amount' => (string) $transaction->total_bill,
            'is_admin_created' => (bool) $transaction->is_admin_created,
            'deleted_at' => null,
            'service_type' => $transaction->service_type,
            'table_code' => $transaction->table_code,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function formatDetail(Transaction $transaction): array
    {
        $transaction->load(['items.menu']);

        $itemGroups = TransactionItemGrouper::groupByOrderedAt($transaction->items);

        return [
            'id' => $transaction->id,
            'transaction_number' => self::formatTransactionNumber($transaction),
            'name' => $transaction->customer_name,
            'customer_phone' => $transaction->customer_phone,
            'service_type' => $transaction->service_type,
            'table_code' => $transaction->table_code,
            'status' => $transaction->status,
            'total_amount' => (string) $transaction->total_bill,
            'is_admin_created' => (bool) $transaction->is_admin_created,
            'created_at' => $transaction->created_at?->toIso8601String(),
            'deleted_at' => null,
            'order_items' => $transaction->items
                ->map(fn (TransactionItem $item) => self::formatItem($item))
                ->values()
                ->all(),
            'item_groups' => array_map(function (array $group) {
                return [
                    'ordered_at' => $group['ordered_at'],
                    'items' => collect($group['items'])
                        ->map(fn (TransactionItem $item) => self::formatItem($item))
                        ->values()
                        ->all(),
                ];
            }, $itemGroups),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function formatItem(TransactionItem $item): array
    {
        $menuPrice = $item->menu?->price ?? $item->unit_price;

        return [
            'id' => $item->id,
            'menu_id' => $item->menu_id,
            'menu' => [
                'id' => $item->menu_id,
                'name' => $item->menu_name,
                'type' => 'standard',
                'price' => (string) $menuPrice,
                'price_label' => 'Rp '.number_format($menuPrice, 0, ',', '.'),
            ],
            'quantity' => $item->quantity,
            'weight_grams' => null,
            'unit_price' => (string) $item->unit_price,
            'line_total' => (string) $item->line_total,
            'note' => $item->note,
            'addons' => $item->addons ?? [],
        ];
    }

    private static function formatTransactionNumber(Transaction $transaction): string
    {
        return $transaction->transaction_number;
    }
}
