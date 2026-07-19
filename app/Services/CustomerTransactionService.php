<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MenuModel;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerTransactionService
{
    public function __construct(
        private TransactionOrderService $orderService,
        private MenuOrderLineBuilder $lineBuilder,
    ) {}

    public function findActiveByPhone(string $phone): ?Transaction
    {
        return Transaction::query()
            ->where('customer_phone', $phone)
            ->where('status', 'in_progress')
            ->latest()
            ->first();
    }

    public function getOrCreateForCustomer(
        Customer $customer,
        ?string $serviceType,
    ): Transaction {
        return DB::transaction(function () use ($customer, $serviceType) {
            $transaction = $this->findActiveByPhone($customer->phone);
            $tableCode = session('table_code');
            $tableCode = is_string($tableCode) && $tableCode !== '' ? $tableCode : null;

            if ($transaction !== null) {
                $this->syncCustomerDetails($transaction, $customer, $serviceType, $tableCode);

                return $transaction;
            }

            return Transaction::query()->create([
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'service_type' => $serviceType ?? 'dine_in',
                'table_code' => $tableCode,
                'status' => 'in_progress',
                'total_bill' => 0,
            ]);
        });
    }

    /**
     * @param  list<array{
     *     menu_id: int,
     *     menu_name: string,
     *     quantity: int,
     *     unit_price: int,
     *     line_total: int,
     *     addon_option_ids?: array<int, int>,
     *     addons: array<int, array<string, mixed>>
     * }>  $cartItems
     */
    public function checkoutCart(
        Customer $customer,
        ?string $serviceType,
        array $cartItems,
    ): Transaction {
        $this->assertCartItemsAvailable($cartItems);

        return DB::transaction(function () use ($customer, $serviceType, $cartItems) {
            $transaction = $this->getOrCreateForCustomer($customer, $serviceType);

            foreach ($cartItems as $lineItem) {
                $this->orderService->addMenuItem(
                    $transaction,
                    $lineItem['menu_id'],
                    $lineItem['quantity'],
                    $lineItem['addon_option_ids'] ?? [],
                );
            }

            return $transaction->fresh();
        });
    }

    /**
     * @param  list<array{
     *     menu_id: int,
     *     menu_name: string,
     *     quantity: int,
     *     unit_price: int,
     *     line_total: int,
     *     addon_option_ids?: array<int, int>,
     *     addons: array<int, array<string, mixed>>
     * }>  $cartItems
     */
    private function assertCartItemsAvailable(array $cartItems): void
    {
        $unavailable = [];

        foreach ($cartItems as $lineItem) {
            $menu = MenuModel::query()
                ->where('is_available', true)
                ->with(['addonGroups.options'])
                ->find($lineItem['menu_id']);

            if ($menu === null) {
                $unavailable[] = $lineItem['menu_name'];

                continue;
            }

            try {
                $this->lineBuilder->build(
                    $menu,
                    $lineItem['quantity'],
                    $lineItem['addon_option_ids'] ?? [],
                );
            } catch (ValidationException) {
                $unavailable[] = $lineItem['menu_name'];
            }
        }

        if ($unavailable !== []) {
            throw ValidationException::withMessages([
                'cart' => 'These items are no longer available: '.implode(', ', $unavailable).'.',
            ]);
        }
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
     * }  $lineItem
     */
    public function addItem(
        Customer $customer,
        ?string $serviceType,
        array $lineItem,
    ): TransactionItem {
        return DB::transaction(function () use ($customer, $serviceType, $lineItem) {
            $transaction = $this->getOrCreateForCustomer($customer, $serviceType);

            return $this->orderService->addLineItem($transaction, $lineItem);
        });
    }

    public function syncSessionTransaction(
        Customer $customer,
        ?string $serviceType = null,
    ): ?Transaction {
        $transaction = $this->findActiveByPhone($customer->phone);

        if ($transaction === null) {
            session()->forget('transaction_id');

            return null;
        }

        $tableCode = session('table_code');
        $tableCode = is_string($tableCode) && $tableCode !== '' ? $tableCode : null;

        $this->syncCustomerDetails(
            $transaction,
            $customer,
            $serviceType ?? session('service_type'),
            $tableCode,
        );

        session(['transaction_id' => $transaction->id]);

        return $transaction;
    }

    private function syncCustomerDetails(
        Transaction $transaction,
        Customer $customer,
        ?string $serviceType,
        ?string $tableCode = null,
    ): void {
        $updates = [];

        if ($transaction->customer_name !== $customer->name) {
            $updates['customer_name'] = $customer->name;
        }

        if ($serviceType !== null && $transaction->service_type !== $serviceType) {
            $updates['service_type'] = $serviceType;
        }

        if ($tableCode !== null && $transaction->table_code !== $tableCode) {
            $updates['table_code'] = $tableCode;
        }

        if ($updates !== []) {
            $transaction->update($updates);
        }
    }
}
