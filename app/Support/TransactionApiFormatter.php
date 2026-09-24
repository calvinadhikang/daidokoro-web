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
        $transaction->loadMissing('salesChannel');

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
            'sales_channel_id' => $transaction->sales_channel_id,
            'sales_channel_name' => $transaction->salesChannel?->name,
            'sales_channel_type' => $transaction->salesChannel?->type,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function formatDetail(Transaction $transaction): array
    {
        $transaction->load(['items.menu', 'salesChannel']);

        $itemGroups = TransactionItemGrouper::groupByOrderedAt($transaction->items);

        return [
            'id' => $transaction->id,
            'transaction_number' => self::formatTransactionNumber($transaction),
            'name' => $transaction->customer_name,
            'customer_phone' => $transaction->customer_phone,
            'customer_phone_display' => WalkInCustomer::isWalkInStored($transaction->customer_phone)
                ? null
                : PhoneNumber::formatForDisplay($transaction->customer_phone),
            'customer_phone_local' => WalkInCustomer::isWalkInStored($transaction->customer_phone)
                ? ''
                : PhoneNumber::toLocalInput($transaction->customer_phone),
            'customer_phone_country' => WalkInCustomer::isWalkInStored($transaction->customer_phone)
                ? PhoneNumber::DEFAULT_REGION
                : PhoneNumber::region($transaction->customer_phone),
            'is_walk_in' => WalkInCustomer::isWalkInStored($transaction->customer_phone),
            'service_type' => $transaction->service_type,
            'table_code' => $transaction->table_code,
            'status' => $transaction->status,
            'total_amount' => (string) $transaction->total_bill,
            'is_admin_created' => (bool) $transaction->is_admin_created,
            'sales_channel_id' => $transaction->sales_channel_id,
            'sales_channel_name' => $transaction->salesChannel?->name,
            'sales_channel_type' => $transaction->salesChannel?->type,
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
        $pricingType = $item->pricing_type
            ?: ($item->menu?->pricing_type ?? 'standard');
        $menuPrice = $item->menu?->price ?? $item->unit_price;

        return [
            'id' => $item->id,
            'menu_id' => $item->menu_id,
            'menu' => [
                'id' => $item->menu_id,
                'name' => $item->menu_name,
                'type' => $pricingType,
                'price' => (string) $menuPrice,
                'price_label' => PriceLabel::format((int) $menuPrice, $pricingType),
            ],
            'quantity' => $item->quantity,
            'weight_grams' => $item->weight_grams,
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
