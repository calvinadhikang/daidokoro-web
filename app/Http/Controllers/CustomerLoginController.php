<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerLoginRequest;
use App\Models\Customer;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CustomerLoginController extends Controller
{
    public function create(): Response
    {
        $customerId = session('customer_id');
        $customer = $customerId !== null
            ? Customer::query()->find($customerId)
            : null;

        return Inertia::render('customer/login', [
            'phonePrefix' => PhoneNumber::DISPLAY_PREFIX,
            'serviceType' => request()->query('service_type'),
            'customer' => $customer === null ? null : [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'phone_display' => $customer->phone_display,
                'phone_local' => $customer->phone_local,
            ],
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

        return redirect()
            ->route('customer.menu.index')
            ->with('success', 'Welcome back, '.$customer->name.'!');
    }
}
