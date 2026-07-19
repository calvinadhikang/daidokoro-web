<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Meja;
use App\Models\MenuModel;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_entry_sets_session_and_redirects_to_login(): void
    {
        Meja::query()->create(['code' => 'A1']);

        $response = $this->get(route('table.entry', ['code' => 'A1']));

        $response->assertRedirect(route('customer.login', [
            'service_type' => 'dine_in',
            'table' => 'A1',
        ]));
        $this->assertSame('A1', session('table_code'));
        $this->assertSame('dine_in', session('service_type'));
    }

    public function test_table_entry_returns_404_for_unknown_code(): void
    {
        $response = $this->get(route('table.entry', ['code' => 'MISSING']));

        $response->assertNotFound();
    }

    public function test_table_entry_skips_login_when_customer_session_exists(): void
    {
        Meja::query()->create(['code' => 'A1']);
        $customer = Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);

        $response = $this->withSession(['customer_id' => $customer->id])
            ->get(route('table.entry', ['code' => 'A1']));

        $response->assertRedirect(route('customer.menu.index'));
        $this->assertSame('A1', session('table_code'));
    }

    public function test_checkout_records_table_code_from_session(): void
    {
        Meja::query()->create(['code' => 'A1']);
        $customer = Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);
        $menu = MenuModel::query()->create([
            'name' => 'Salmon Roll',
            'price' => 45000,
            'is_available' => true,
        ]);

        $cart = [[
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            'quantity' => 1,
            'unit_price' => 45000,
            'line_total' => 45000,
            'addon_option_ids' => [],
            'addons' => [],
        ]];

        $response = $this->withSession([
            'customer_id' => $customer->id,
            'service_type' => 'dine_in',
            'table_code' => 'A1',
            'customer_cart' => $cart,
        ])->post(route('customer.cart.checkout'));

        $response->assertRedirect();

        $this->assertDatabaseHas('transactions', [
            'customer_phone' => $customer->phone,
            'table_code' => 'A1',
            'service_type' => 'dine_in',
            'status' => 'in_progress',
        ]);
    }

    public function test_login_page_receives_table_code_from_query(): void
    {
        $response = $this->get(route('customer.login', [
            'service_type' => 'dine_in',
            'table' => 'A1',
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('customer/login')
            ->where('tableCode', 'A1')
            ->where('serviceType', 'dine_in')
        );
        $this->assertSame('A1', session('table_code'));
    }

    public function test_sync_updates_open_transaction_table_code(): void
    {
        Meja::query()->create(['code' => 'B2']);
        $customer = Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);
        Transaction::query()->create([
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'service_type' => 'dine_in',
            'status' => 'in_progress',
            'total_bill' => 0,
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('table.entry', ['code' => 'B2']));

        $this->assertDatabaseHas('transactions', [
            'customer_phone' => $customer->phone,
            'table_code' => 'B2',
            'status' => 'in_progress',
        ]);
    }
}
