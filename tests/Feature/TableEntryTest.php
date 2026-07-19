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

    public function test_table_entry_shows_service_type_choice(): void
    {
        Meja::query()->create(['code' => 'A1']);

        $response = $this->get(route('table.entry', ['code' => 'A1']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('table-entry')
            ->where('tableCode', 'A1')
            ->has('storeStatus')
        );
        $this->assertSame('A1', session('table_code'));
        $this->assertFalse(session()->has('service_type'));
    }

    public function test_table_entry_returns_404_for_unknown_code(): void
    {
        $response = $this->get(route('table.entry', ['code' => 'MISSING']));

        $response->assertNotFound();
    }

    public function test_selecting_dine_in_redirects_to_login(): void
    {
        Meja::query()->create(['code' => 'A1']);

        $response = $this->get(route('table.entry.select', [
            'code' => 'A1',
            'serviceType' => 'dine_in',
        ]));

        $response->assertRedirect(route('customer.login', [
            'service_type' => 'dine_in',
            'table' => 'A1',
        ]));
        $this->assertSame('A1', session('table_code'));
        $this->assertSame('dine_in', session('service_type'));
    }

    public function test_selecting_dine_out_redirects_to_login(): void
    {
        Meja::query()->create(['code' => 'A1']);

        $response = $this->get(route('table.entry.select', [
            'code' => 'A1',
            'serviceType' => 'takeaway',
        ]));

        $response->assertRedirect(route('customer.login', [
            'service_type' => 'takeaway',
            'table' => 'A1',
        ]));
        $this->assertSame('A1', session('table_code'));
        $this->assertSame('takeaway', session('service_type'));
    }

    public function test_select_skips_login_when_customer_session_exists(): void
    {
        Meja::query()->create(['code' => 'A1']);
        $customer = Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);

        $response = $this->withSession(['customer_id' => $customer->id])
            ->get(route('table.entry.select', [
                'code' => 'A1',
                'serviceType' => 'dine_in',
            ]));

        $response->assertRedirect(route('customer.menu.index'));
        $this->assertSame('A1', session('table_code'));
        $this->assertSame('dine_in', session('service_type'));
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

    public function test_sync_updates_open_transaction_table_code_on_select(): void
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
            ->get(route('table.entry.select', [
                'code' => 'B2',
                'serviceType' => 'takeaway',
            ]));

        $this->assertDatabaseHas('transactions', [
            'customer_phone' => $customer->phone,
            'table_code' => 'B2',
            'service_type' => 'takeaway',
            'status' => 'in_progress',
        ]);
    }
}
