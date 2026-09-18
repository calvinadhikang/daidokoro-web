<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Transaction;
use App\Services\CustomerDirectoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerPhoneNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_phone_is_stored_with_country_code(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '081234567890',
        ]);

        $this->assertSame('6281234567890', $customer->phone);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'phone' => '6281234567890',
        ]);
    }

    public function test_transaction_phone_is_stored_with_country_code(): void
    {
        $transaction = Transaction::query()->create([
            'customer_name' => 'Alex Tan',
            'customer_phone' => '0812-3456-7890',
            'status' => 'paid',
            'total_bill' => 25000,
        ]);

        $this->assertSame('6281234567890', $transaction->customer_phone);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'customer_phone' => '6281234567890',
        ]);
    }

    public function test_zero_prefix_and_country_code_are_the_same_customer(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);

        $transaction = Transaction::query()->create([
            'customer_name' => 'Alex Tan',
            'customer_phone' => '081234567890',
            'status' => 'paid',
            'total_bill' => 25000,
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'customer_phone' => '6281234567890',
        ]);

        $history = app(CustomerDirectoryService::class)->transactionsFor($customer);

        $this->assertCount(1, $history);
        $this->assertTrue($history->contains('id', $transaction->id));
    }

    public function test_legacy_duplicate_customers_are_merged_and_phones_rewritten(): void
    {
        $keeper = Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);

        DB::table('customers')->insert([
            'name' => 'Pelanggan',
            'phone' => '081234567890',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $transaction = Transaction::query()->create([
            'customer_name' => 'Alex Tan',
            'customer_phone' => '6281234567890',
            'status' => 'paid',
            'total_bill' => 15000,
        ]);

        DB::table('transactions')->where('id', $transaction->id)->update([
            'customer_phone' => '081234567890',
        ]);

        app(CustomerDirectoryService::class)->normalizeAndMergeStoredPhones();

        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseHas('customers', [
            'id' => $keeper->id,
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'customer_phone' => '6281234567890',
        ]);
        $this->assertDatabaseMissing('customers', [
            'phone' => '081234567890',
        ]);
    }

    public function test_customer_list_does_not_create_a_second_row_for_zero_prefix_orders(): void
    {
        Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);

        $transaction = Transaction::query()->create([
            'customer_name' => 'Alex Tan',
            'customer_phone' => '081234567890',
            'status' => 'paid',
            'total_bill' => 20000,
        ]);

        DB::table('transactions')->where('id', $transaction->id)->update([
            'customer_phone' => '081234567890',
        ]);

        $customers = app(CustomerDirectoryService::class)->list();

        $this->assertCount(1, $customers);
        $this->assertSame(1, (int) $customers->first()?->transactions_count);
    }
}
