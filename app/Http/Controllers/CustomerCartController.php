<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\CustomerCartService;
use App\Services\CustomerTransactionService;
use App\Services\TransactionPushNotificationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CustomerCartController extends Controller
{
    public function __construct(
        private CustomerCartService $cart,
        private CustomerTransactionService $transactions,
        private TransactionPushNotificationService $pushNotifications,
    ) {}

    public function index(): Response
    {
        return Inertia::render('customer/cart/index', [
            'serviceType' => session('service_type'),
            'cart' => $this->cart->items(),
            'cartTotal' => $this->cart->total(),
        ]);
    }

    public function checkout(): RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart is empty.',
            ]);
        }

        $customer = Customer::query()->findOrFail(session('customer_id'));

        try {
            $transaction = $this->transactions->checkoutCart(
                $customer,
                session('service_type'),
                $this->cart->items(),
            );
        } catch (ModelNotFoundException) {
            throw ValidationException::withMessages([
                'cart' => 'One or more items in your cart are no longer available.',
            ]);
        }

        $this->cart->clear();

        session(['transaction_id' => $transaction->id]);

        $this->pushNotifications->notifyCustomerOrderCheckedOut($transaction);

        return redirect()
            ->route('customer.order.index')
            ->with('success', 'Order sent! Your items have been added to your bill.');
    }
}
