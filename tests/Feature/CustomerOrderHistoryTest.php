<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\MenuModel;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Services\StoreHoursService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CustomerOrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('customer.order.history'))
            ->assertRedirect(route('customer.login'));
    }

    public function test_history_groups_transactions_from_the_nearest_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-21 15:00:00', StoreHoursService::TIMEZONE));

        $customer = Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);

        Customer::query()->create([
            'name' => 'Other Guest',
            'phone' => '6281111111111',
        ]);

        $menu = MenuModel::query()->create([
            'name' => 'Chicken Rice',
            'price' => 35000,
            'is_available' => true,
        ]);

        $older = $this->makeTransaction($customer, '2026-10-18', 1, 'paid', 10000);
        $yesterday = $this->makeTransaction($customer, '2026-10-20', 1, 'paid', 20000);
        $todayEarly = $this->makeTransaction($customer, '2026-10-21', 1, 'in_progress', 35000, '2026-10-21 10:00:00');
        $todayLate = $this->makeTransaction($customer, '2026-10-21', 2, 'paid', 15000, '2026-10-21 16:00:00');
        $this->makeTransaction(
            Customer::query()->where('phone', '6281111111111')->firstOrFail(),
            '2026-10-21',
            3,
            'paid',
            9000,
        );

        TransactionItem::query()->create([
            'transaction_id' => $todayLate->id,
            'menu_id' => $menu->id,
            'menu_name' => 'Chicken Rice',
            'quantity' => 1,
            'unit_price' => 15000,
            'line_total' => 15000,
            'addons' => null,
        ]);

        $response = $this
            ->withSession(['customer_id' => $customer->id])
            ->get(route('customer.order.history'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('customer/order/history')
            ->where('sort', 'newest')
            ->has('groups', 3)
            ->where('groups.0.date', '2026-10-21')
            ->where('groups.0.label', 'Today')
            ->where('groups.0.transactions.0.id', $todayLate->id)
            ->where('groups.0.transactions.1.id', $todayEarly->id)
            ->has('groups.0.transactions.0.item_groups', 1)
            ->where('groups.1.date', '2026-10-20')
            ->where('groups.1.label', 'Yesterday')
            ->where('groups.1.transactions.0.id', $yesterday->id)
            ->where('groups.2.date', '2026-10-18')
            ->where('groups.2.label', 'Sun, 18 Oct 2026')
            ->where('groups.2.transactions.0.id', $older->id)
        );

        Carbon::setTestNow();
    }

    public function test_history_can_be_sorted_from_the_oldest_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-21 15:00:00', StoreHoursService::TIMEZONE));

        $customer = Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);

        $older = $this->makeTransaction($customer, '2026-10-18', 1, 'paid', 10000);
        $newer = $this->makeTransaction($customer, '2026-10-21', 1, 'paid', 20000);

        $response = $this
            ->withSession(['customer_id' => $customer->id])
            ->get(route('customer.order.history', ['sort' => 'oldest']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('customer/order/history')
            ->where('sort', 'oldest')
            ->where('groups.0.date', '2026-10-18')
            ->where('groups.0.transactions.0.id', $older->id)
            ->where('groups.1.date', '2026-10-21')
            ->where('groups.1.transactions.0.id', $newer->id)
        );

        Carbon::setTestNow();
    }

    private function makeTransaction(
        Customer $customer,
        string $businessDate,
        int $dailyNumber,
        string $status,
        int $total,
        ?string $createdAt = null,
    ): Transaction {
        $transaction = Transaction::query()->create([
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'service_type' => 'dine_in',
            'status' => $status,
            'total_bill' => $total,
            'business_date' => $businessDate,
            'daily_number' => $dailyNumber,
        ]);

        if ($createdAt !== null) {
            $transaction->forceFill([
                'created_at' => Carbon::parse($createdAt, StoreHoursService::TIMEZONE),
            ])->save();
        }

        return $transaction->fresh();
    }
}
