<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_customers(): void
    {
        Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);
        Customer::query()->create([
            'name' => 'Maria Santos',
            'phone' => '6289876543210',
        ]);

        $response = $this->get(route('admin.customers.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/customers/index')
            ->has('customers', 2)
            ->where('customers.0.name', 'Alex Tan')
            ->where('customers.0.phone_display', '081234567890')
            ->where('customers.1.name', 'Maria Santos')
        );
    }

    public function test_index_syncs_customers_from_transactions(): void
    {
        Transaction::query()->create([
            'customer_name' => 'Walk-in Guest',
            'customer_phone' => '081211122233',
            'status' => 'paid',
            'total_bill' => 25000,
        ]);

        $response = $this->get(route('admin.customers.index'));

        $response->assertOk();
        $this->assertDatabaseHas('customers', [
            'name' => 'Walk-in Guest',
            'phone' => '6281211122233',
        ]);
        $response->assertInertia(fn ($page) => $page
            ->component('admin/customers/index')
            ->has('customers', 1)
            ->where('customers.0.name', 'Walk-in Guest')
            ->where('customers.0.transactions_count', 1)
        );
    }

    public function test_show_includes_matching_transactions(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);

        $matching = Transaction::query()->create([
            'customer_name' => 'Alex Tan',
            'customer_phone' => '081234567890',
            'status' => 'paid',
            'total_bill' => 45000,
        ]);

        Transaction::query()->create([
            'customer_name' => 'Someone Else',
            'customer_phone' => '6281111111111',
            'status' => 'in_progress',
            'total_bill' => 10000,
        ]);

        $response = $this->get(route('admin.customers.show', $customer));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/customers/show')
            ->where('customer.name', 'Alex Tan')
            ->has('transactions', 1)
            ->where('transactions.0.id', $matching->id)
        );
    }

    public function test_update_changes_name_and_phone_and_syncs_transactions(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Old Name',
            'phone' => '6281234567890',
        ]);

        $transaction = Transaction::query()->create([
            'customer_name' => 'Old Name',
            'customer_phone' => '6281234567890',
            'status' => 'in_progress',
            'total_bill' => 15000,
        ]);

        $response = $this->put(route('admin.customers.update', $customer), [
            'name' => 'Alex Tan',
            'phone' => '081298765432',
        ]);

        $response->assertRedirect(route('admin.customers.show', $customer));
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Alex Tan',
            'phone' => '6281298765432',
        ]);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'customer_name' => 'Alex Tan',
            'customer_phone' => '6281298765432',
        ]);
    }

    public function test_update_requires_unique_phone(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);
        Customer::query()->create([
            'name' => 'Maria Santos',
            'phone' => '6289876543210',
        ]);

        $response = $this->put(route('admin.customers.update', $customer), [
            'name' => 'Alex Tan',
            'phone' => '089876543210',
        ]);

        $response->assertSessionHasErrors(['phone']);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'phone' => '6281234567890',
        ]);
    }
}
