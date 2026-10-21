<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\QrisPayment;
use App\Models\SalesChannel;
use App\Models\Transaction;
use App\Services\CustomerTransactionService;
use App\Services\QrisPaymentService;
use App\Support\TransactionItemGrouper;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CustomerOrderController extends Controller
{
    public function __construct(private CustomerTransactionService $transactions) {}

    public function index(): Response
    {
        $customer = Customer::query()->findOrFail(session('customer_id'));
        $transactions = $this->transactions->listTodayByPhone($customer->phone);
        $transactions->load([
            'qrisPayments' => function (HasMany $query) {
                $query->where('status', QrisPayment::STATUS_PENDING)
                    ->where('expires_at', '>', now())
                    ->orderByDesc('id');
            },
        ]);

        return Inertia::render('customer/order/index', [
            'serviceType' => session('service_type'),
            'transactions' => $transactions
                ->map(fn (Transaction $transaction) => $this->formatTransaction($transaction))
                ->values()
                ->all(),
        ]);
    }

    public function storeQris(Transaction $transaction, QrisPaymentService $qris): RedirectResponse
    {
        $qris->issue($this->ownedTransaction($transaction));

        return redirect()
            ->route('customer.order.index')
            ->with('success', 'QRIS code is ready.');
    }

    public function checkQris(Transaction $transaction, QrisPaymentService $qris): RedirectResponse
    {
        $qris->check($this->ownedTransaction($transaction));

        return redirect()
            ->route('customer.order.index')
            ->with('success', 'Payment received.');
    }

    private function ownedTransaction(Transaction $transaction): Transaction
    {
        $customer = Customer::query()->findOrFail(session('customer_id'));

        return Transaction::query()
            ->forPhone($customer->phone)
            ->whereKey($transaction->id)
            ->where('sales_channel_id', SalesChannel::store()->id)
            ->firstOrFail();
    }

    /**
     * @return array{
     *     id: int,
     *     transaction_number: string,
     *     status: string,
     *     paid_via: string|null,
     *     total_bill: int,
     *     service_type: string,
     *     created_at: string|null,
     *     item_groups: list<array{ordered_at: string, items: list<array<string, mixed>>}>,
     *     qris: array{qr_content: string, amount: int, expires_at: string}|null
     * }
     */
    private function formatTransaction(Transaction $transaction): array
    {
        $payment = $transaction->relationLoaded('qrisPayments')
            ? $transaction->qrisPayments->first()
            : null;

        return [
            'id' => $transaction->id,
            'transaction_number' => $transaction->transaction_number,
            'status' => $transaction->status,
            'paid_via' => $transaction->paid_via,
            'total_bill' => $transaction->total_bill,
            'service_type' => $transaction->service_type,
            'created_at' => $transaction->created_at?->toIso8601String(),
            'item_groups' => TransactionItemGrouper::groupByOrderedAt($transaction->items),
            'qris' => $payment instanceof QrisPayment && $transaction->status !== 'paid'
                ? [
                    'qr_content' => $payment->qr_content,
                    'amount' => $payment->amount,
                    'expires_at' => $payment->expires_at->toIso8601String(),
                ]
                : null,
        ];
    }
}
