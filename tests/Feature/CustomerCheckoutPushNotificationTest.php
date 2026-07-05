<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\MenuModel;
use App\Models\PushDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CustomerCheckoutPushNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_sends_push_notification_to_registered_devices(): void
    {
        Http::fake([
            'exp.host/*' => Http::response([
                'data' => [
                    ['status' => 'ok', 'id' => 'push-1'],
                ],
            ]),
        ]);

        PushDevice::query()->create([
            'device_key' => 'cashier-1',
            'expo_push_token' => 'ExponentPushToken[cashier-token]',
            'platform' => 'android',
            'device_name' => 'Cashier tablet',
            'app_version' => '1.0.0',
            'last_registered_at' => now(),
        ]);

        $customer = Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);

        $menu = MenuModel::query()->create([
            'name' => 'Chicken Rice',
            'price' => 35000,
            'is_available' => true,
        ]);

        $this
            ->withSession(['customer_id' => $customer->id, 'service_type' => 'takeaway'])
            ->post(route('customer.menu.store', $menu), ['quantity' => 2]);

        $response = $this
            ->withSession(['customer_id' => $customer->id, 'service_type' => 'takeaway'])
            ->post(route('customer.cart.checkout'));

        $response->assertRedirect(route('customer.order.index'));

        Http::assertSent(function ($request) {
            return $request->url() === 'https://exp.host/--/api/v2/push/send'
                && $request['to'] === ['ExponentPushToken[cashier-token]']
                && $request['title'] === 'New customer order'
                && str_contains($request['body'], 'Alex Tan')
                && str_contains($request['body'], 'Takeaway')
                && $request['data']['screen'] === 'transaction_detail'
                && $request['data']['source'] === 'customer_checkout';
        });
    }

    public function test_checkout_succeeds_when_push_notification_fails(): void
    {
        Http::fake([
            'exp.host/*' => Http::response([], 500),
        ]);

        PushDevice::query()->create([
            'device_key' => 'cashier-1',
            'expo_push_token' => 'ExponentPushToken[cashier-token]',
            'platform' => 'android',
            'device_name' => 'Cashier tablet',
            'app_version' => '1.0.0',
            'last_registered_at' => now(),
        ]);

        $customer = Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);

        $menu = MenuModel::query()->create([
            'name' => 'Chicken Rice',
            'price' => 35000,
            'is_available' => true,
        ]);

        $this
            ->withSession(['customer_id' => $customer->id, 'service_type' => 'dine_in'])
            ->post(route('customer.menu.store', $menu), ['quantity' => 1]);

        $response = $this
            ->withSession(['customer_id' => $customer->id, 'service_type' => 'dine_in'])
            ->post(route('customer.cart.checkout'));

        $response->assertRedirect(route('customer.order.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseCount('transaction_items', 1);
    }
}
