<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\CustomerDirectoryService;
use App\Support\CustomerFormatter;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function __construct(private CustomerDirectoryService $customers) {}

    public function index(): Response
    {
        return Inertia::render('admin/customers/index', [
            'customers' => $this->customers
                ->list()
                ->map(fn (Customer $customer) => CustomerFormatter::format($customer))
                ->values(),
        ]);
    }

    public function show(Customer $customer): Response
    {
        return Inertia::render('admin/customers/show', [
            'customer' => CustomerFormatter::format($customer),
            'transactions' => $this->customers->transactionsFor($customer),
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validated();

        $this->customers->update(
            $customer,
            $validated['name'],
            $validated['phone'],
        );

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('success', 'Customer updated successfully.');
    }
}
