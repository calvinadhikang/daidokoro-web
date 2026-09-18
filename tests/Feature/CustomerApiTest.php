<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\MenuModel;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_customers(): void
    {
        Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);

        $response = $this->getJson('/api/customer');

        $response->assertOk();
        $response->assertJsonFragment([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
            'phone_display' => '081234567890',
            'phone_country' => 'ID',
            'transactions_count' => 0,
        ]);
    }

    public function test_index_includes_customers_created_from_transactions(): void
    {
        Transaction::query()->create([
            'customer_name' => 'Kasir Guest',
            'customer_phone' => '081255500011',
            'status' => 'paid',
            'total_bill' => 20000,
        ]);

        $response = $this->getJson('/api/customer');

        $response->assertOk();
        $response->assertJsonFragment([
            'name' => 'Kasir Guest',
            'phone' => '6281255500011',
            'transactions_count' => 1,
        ]);
    }

    public function test_detail_returns_customer_and_transactions(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);

        $transaction = Transaction::query()->create([
            'customer_name' => 'Alex Tan',
            'customer_phone' => '6281234567890',
            'status' => 'paid',
            'total_bill' => 35000,
            'service_type' => 'takeaway',
        ]);

        $response = $this->getJson("/api/customer/detail/{$customer->id}");

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $customer->id,
            'name' => 'Alex Tan',
            'phone_display' => '081234567890',
            'transactions_count' => 1,
        ]);
        $response->assertJsonFragment([
            'id' => $transaction->id,
            'name' => 'Alex Tan',
            'status' => 'paid',
            'total_amount' => '35000',
            'service_type' => 'takeaway',
        ]);
    }

    public function test_update_changes_name_and_phone(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Old Name',
            'phone' => '6281234567890',
        ]);

        $transaction = Transaction::query()->create([
            'customer_name' => 'Old Name',
            'customer_phone' => '081234567890',
            'status' => 'in_progress',
            'total_bill' => 12000,
        ]);

        $response = $this->postJson("/api/customer/update/{$customer->id}", [
            'name' => 'Alex Tan',
            'phone' => '081298765432',
        ]);

        $response->assertOk();
        $response->assertJsonFragment([
            'success' => true,
            'name' => 'Alex Tan',
            'phone' => '6281298765432',
            'phone_display' => '081298765432',
        ]);

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

    public function test_update_normalizes_singapore_phone(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Old Name',
            'phone' => '6281234567890',
        ]);

        $transaction = Transaction::query()->create([
            'customer_name' => 'Old Name',
            'customer_phone' => '6281234567890',
            'status' => 'in_progress',
            'total_bill' => 12000,
        ]);

        $response = $this->postJson("/api/customer/update/{$customer->id}", [
            'name' => 'Wei',
            'phone' => '81234567',
            'phone_country' => 'SG',
        ]);

        $response->assertOk();
        $response->assertJsonFragment([
            'success' => true,
            'name' => 'Wei',
            'phone' => '6581234567',
            'phone_country' => 'SG',
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'phone' => '6581234567',
        ]);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'customer_name' => 'Wei',
            'customer_phone' => '6581234567',
        ]);
    }

    public function test_update_rejects_duplicate_phone(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);
        Customer::query()->create([
            'name' => 'Maria Santos',
            'phone' => '6289876543210',
        ]);

        $response = $this->postJson("/api/customer/update/{$customer->id}", [
            'name' => 'Alex Tan',
            'phone' => '089876543210',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['phone']);
    }

    public function test_creating_admin_transaction_upserts_customer(): void
    {
        $menu = MenuModel::query()->create([
            'name' => 'Salmon Roll',
            'price' => 45000,
            'is_available' => true,
        ]);

        $response = $this->postJson('/api/transaction/create', [
            'customer_name' => 'Alex Tan',
            'customer_phone' => '081234567890',
            'service_type' => 'dine_in',
            'items' => [
                [
                    'menu_id' => $menu->id,
                    'quantity' => 1,
                    'addon_option_ids' => [],
                ],
            ],
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('customers', [
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);
    }
}
