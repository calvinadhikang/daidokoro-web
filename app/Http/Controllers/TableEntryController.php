<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Meja;
use App\Services\CustomerTransactionService;
use App\Services\StoreHoursService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TableEntryController extends Controller
{
    public function __construct(
        private CustomerTransactionService $transactions,
        private StoreHoursService $storeHours,
    ) {}

    public function show(string $code): Response
    {
        $meja = Meja::query()->where('code', $code)->firstOrFail();

        session(['table_code' => $meja->code]);
        session()->forget('service_type');

        return Inertia::render('table-entry', [
            'tableCode' => $meja->code,
            'storeStatus' => $this->storeHours->status(),
        ]);
    }

    public function select(string $code, string $serviceType): RedirectResponse
    {
        abort_unless(in_array($serviceType, ['dine_in', 'takeaway'], true), 404);

        $meja = Meja::query()->where('code', $code)->firstOrFail();

        session([
            'table_code' => $meja->code,
            'service_type' => $serviceType,
        ]);

        $customerId = session('customer_id');

        if ($customerId !== null) {
            $customer = Customer::query()->find($customerId);

            if ($customer !== null) {
                $this->transactions->syncSessionTransaction($customer, $serviceType);

                return redirect()
                    ->route('customer.menu.index')
                    ->with('success', 'Table '.$meja->code.' selected.');
            }
        }

        return redirect()->route('customer.login', [
            'service_type' => $serviceType,
            'table' => $meja->code,
        ]);
    }
}
