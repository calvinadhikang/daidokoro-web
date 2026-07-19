<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Meja;
use App\Services\CustomerTransactionService;
use Illuminate\Http\RedirectResponse;

class TableEntryController extends Controller
{
    public function __construct(private CustomerTransactionService $transactions) {}

    public function __invoke(string $code): RedirectResponse
    {
        $meja = Meja::query()->where('code', $code)->firstOrFail();

        session([
            'table_code' => $meja->code,
            'service_type' => 'dine_in',
        ]);

        $customerId = session('customer_id');

        if ($customerId !== null) {
            $customer = Customer::query()->find($customerId);

            if ($customer !== null) {
                $this->transactions->syncSessionTransaction($customer, 'dine_in');

                return redirect()
                    ->route('customer.menu.index')
                    ->with('success', 'Table '.$meja->code.' selected.');
            }
        }

        return redirect()->route('customer.login', [
            'service_type' => 'dine_in',
            'table' => $meja->code,
        ]);
    }
}
