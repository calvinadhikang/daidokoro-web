<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Transaction;
use App\Services\CustomerTransactionService;
use App\Support\TransactionItemGrouper;
use Inertia\Inertia;
use Inertia\Response;

class CustomerOrderController extends Controller
{
    public function __construct(private CustomerTransactionService $transactions) {}

    public function index(): Response
    {
        $customer = Customer::query()->findOrFail(session('customer_id'));
        $transactions = $this->transactions->listTodayByPhone($customer->phone);

        return Inertia::render('customer/order/index', [
            'serviceType' => session('service_type'),
            'transactions' => $transactions
                ->map(fn (Transaction $transaction) => $this->formatTransaction($transaction))
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array{
     *     id: int,
     *     transaction_number: string,
     *     status: string,
     *     total_bill: int,
     *     service_type: string,
     *     created_at: string|null,
     *     item_groups: list<array{ordered_at: string, items: list<array<string, mixed>>}>
     * }
     */
    private function formatTransaction(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'transaction_number' => $transaction->transaction_number,
            'status' => $transaction->status,
            'total_bill' => $transaction->total_bill,
            'service_type' => $transaction->service_type,
            'created_at' => $transaction->created_at?->toIso8601String(),
            'item_groups' => TransactionItemGrouper::groupByOrderedAt($transaction->items),
        ];
    }
}
