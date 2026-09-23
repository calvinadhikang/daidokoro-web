<?php

namespace App\Services;

use App\Models\MenuModel;
use App\Models\SalesChannel;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionOrderService
{
    public function __construct(
        private MenuOrderLineBuilder $lineBuilder,
        private CustomerDirectoryService $customers,
        private SalesChannelService $salesChannels,
    ) {}

    /**
     * @param  array{
     *     customer_name: string,
     *     customer_phone: string,
     *     service_type?: string|null,
     *     sales_channel_id?: int|null,
     *     items?: list<array{
     *         menu_id: int,
     *         quantity?: int|null,
     *         weight_grams?: int|null,
     *         addon_option_ids?: array<int, int>,
     *         note?: string|null
     *     }>
     * }  $data
     */
    public function createAdminTransaction(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $channel = $this->salesChannels->resolve($data['sales_channel_id'] ?? null);
            $serviceType = $data['service_type'] ?? ($channel->isEvent() ? 'takeaway' : 'dine_in');

            $transaction = Transaction::query()->create([
                'sales_channel_id' => $channel->id,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'service_type' => $serviceType,
                'status' => 'in_progress',
                'total_bill' => 0,
                'is_admin_created' => true,
            ]);

            foreach ($data['items'] ?? [] as $itemData) {
                $this->addMenuItem(
                    $transaction,
                    $itemData['menu_id'],
                    (int) ($itemData['quantity'] ?? 1),
                    $itemData['addon_option_ids'] ?? [],
                    $itemData['note'] ?? null,
                    isset($itemData['weight_grams']) ? (int) $itemData['weight_grams'] : null,
                );
            }

            $this->customers->upsertFromNamePhone(
                $data['customer_name'],
                $data['customer_phone'],
            );

            return $transaction->fresh() ?? $transaction;
        });
    }

    /**
     * @param  array{
     *     customer_name: string,
     *     customer_phone: string,
     *     service_type?: string|null
     * }  $data
     */
    public function updateHeader(Transaction $transaction, array $data): Transaction
    {
        $this->assertEditable($transaction);

        $transaction->update([
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'service_type' => $data['service_type'] ?? $transaction->service_type,
        ]);

        $this->customers->upsertFromNamePhone(
            $data['customer_name'],
            $data['customer_phone'],
        );

        return $transaction->fresh() ?? $transaction;
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
        ?int $weightGrams = null,
    ): TransactionItem {
        $this->assertEditable($transaction);

        $channel = $transaction->salesChannel ?? $this->salesChannels->store();
        $menu = MenuModel::query()
            ->with(['addonGroups.options'])
            ->find($menuId);

        if ($menu === null) {
            throw ValidationException::withMessages([
                'items' => "Menu #{$menuId} is no longer available.",
            ]);
        }

        $this->salesChannels->assertAssigned($menu, $channel, requireAvailable: true);

        $lineItem = $this->lineBuilder->build(
            $menu,
            $quantity,
            $addonOptionIds,
            $channel,
            $weightGrams,
        );
        $lineItem['note'] = self::normalizeNote($note);

        return $this->addLineItem($transaction, $lineItem);
    }

    /**
     * @param  array<int, int>  $addonOptionIds
     */
    public function updateMenuItem(
        TransactionItem $item,
        int $quantity,
        array $addonOptionIds,
        ?string $note = null,
        ?int $weightGrams = null,
    ): TransactionItem {
        $transaction = $item->transaction;
        $this->assertEditable($transaction);

        $menu = MenuModel::query()
            ->with(['addonGroups.options'])
            ->find($item->menu_id);

        if ($menu === null) {
            throw ValidationException::withMessages([
                'menu_id' => 'The ordered menu is no longer available.',
            ]);
        }

        $channel = $transaction->salesChannel ?? $this->salesChannels->store();
        $this->salesChannels->assertAssigned($menu, $channel, requireAvailable: false);

        $lineItem = $this->lineBuilder->build(
            $menu,
            $quantity,
            $addonOptionIds,
            $channel,
            $weightGrams ?? $item->weight_grams,
        );

        return DB::transaction(function () use ($item, $transaction, $lineItem, $note) {
            $item->update([
                'menu_name' => $lineItem['menu_name'],
                'quantity' => $lineItem['quantity'],
                'weight_grams' => $lineItem['weight_grams'],
                'pricing_type' => $lineItem['pricing_type'],
                'unit_price' => $lineItem['unit_price'],
                'line_total' => $lineItem['line_total'],
                'addons' => $lineItem['addons'],
                'note' => self::normalizeNote($note),
            ]);

            $transaction->recalculateTotal();

            return $item->fresh() ?? $item;
        });
    }

    public function deleteMenuItem(TransactionItem $item): Transaction
    {
        $transaction = $item->transaction;
        $this->assertEditable($transaction);

        return DB::transaction(function () use ($item, $transaction) {
            $item->delete();
            $transaction->recalculateTotal();

            return $transaction->fresh() ?? $transaction;
        });
    }

    /**
     * @param  array{
     *     menu_id: int,
     *     menu_name: string,
     *     quantity: int,
     *     weight_grams?: int|null,
     *     pricing_type?: string,
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
                'weight_grams' => $lineItem['weight_grams'] ?? null,
                'pricing_type' => $lineItem['pricing_type'] ?? MenuModel::PRICING_STANDARD,
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

    private function assertEditable(Transaction $transaction): void
    {
        if ($transaction->isPaid()) {
            throw ValidationException::withMessages([
                'transaction' => 'Paid transactions cannot be edited.',
            ]);
        }
    }
}
