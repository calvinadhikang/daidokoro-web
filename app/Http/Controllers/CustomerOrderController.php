<?php

namespace App\Http\Controllers;

use App\Models\Customer;
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
        $transaction = $this->transactions->findActiveByPhone($customer->phone);

        if ($transaction !== null) {
            $transaction->load('items');
        }

        return Inertia::render('customer/order/index', [
            'serviceType' => session('service_type'),
            'transaction' => $transaction,
            'itemGroups' => $transaction !== null
                ? TransactionItemGrouper::groupByOrderedAt($transaction->items)
                : [],
        ]);
    }
}
