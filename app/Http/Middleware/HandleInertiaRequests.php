<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use App\Services\CustomerCartService;
use App\Services\CustomerTransactionService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'customer' => fn () => $this->resolveCustomer($request),
            'customerNav' => fn () => $this->resolveCustomerNav($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
        ];
    }

    /**
     * @return array{id: int, name: string, phone: string, phone_display: string, phone_local: string}|null
     */
    private function resolveCustomer(Request $request): ?array
    {
        $customerId = $request->session()->get('customer_id');

        if ($customerId === null) {
            return null;
        }

        $customer = Customer::query()->find($customerId);

        if ($customer === null) {
            return null;
        }

        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'phone_display' => $customer->phone_display,
            'phone_local' => $customer->phone_local,
        ];
    }

    /**
     * @return array{cartCount: int, hasOrder: bool}|null
     */
    private function resolveCustomerNav(Request $request): ?array
    {
        if ($request->session()->get('customer_id') === null) {
            return null;
        }

        $cart = app(CustomerCartService::class);
        $customer = Customer::query()->find($request->session()->get('customer_id'));

        if ($customer === null) {
            return null;
        }

        $transaction = app(CustomerTransactionService::class)
            ->findActiveByPhone($customer->phone);

        return [
            'cartCount' => $cart->count(),
            'hasOrder' => $transaction !== null && $transaction->items()->exists(),
        ];
    }
}
