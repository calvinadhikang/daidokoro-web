<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerLoginRequest;
use App\Models\Customer;
use App\Services\CustomerTransactionService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CustomerLoginController extends Controller
{
    public function __construct(private CustomerTransactionService $transactions) {}

    public function create(): Response
    {
        $customerId = session('customer_id');
        $customer = $customerId !== null
            ? Customer::query()->find($customerId)
            : null;

        $activeTransaction = $customer !== null
            ? $this->transactions->findActiveByPhone($customer->phone)
            : null;

        return Inertia::render('customer/login', [
            'serviceType' => request()->query('service_type'),
            'customer' => $customer === null ? null : [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'phone_display' => $customer->phone_display,
                'phone_local' => $customer->phone_local,
            ],
            'hasActiveOrder' => $activeTransaction !== null,
        ]);
    }

    public function store(StoreCustomerLoginRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $customer = Customer::query()->updateOrCreate(
            ['phone' => $validated['phone']],
            ['name' => $validated['name']],
        );

        session([
            'customer_id' => $customer->id,
            'service_type' => $validated['service_type'] ?? session('service_type'),
        ]);

        $activeTransaction = $this->transactions->syncSessionTransaction(
            $customer,
            $validated['service_type'] ?? null,
        );

        $message = $activeTransaction !== null
            ? 'Welcome back, '.$customer->name.'! Your order is ready to continue.'
            : 'Welcome, '.$customer->name.'!';

        return redirect()
            ->route('customer.menu.index')
            ->with('success', $message);
    }
}
