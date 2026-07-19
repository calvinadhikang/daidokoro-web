<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Services\StoreHoursService;
use App\Services\TransactionNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TransactionNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_transactions_get_sequential_daily_numbers(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-19 10:00:00', StoreHoursService::TIMEZONE));

        $first = Transaction::query()->create([
            'customer_name' => 'Alex',
            'customer_phone' => '6281111111111',
            'status' => 'in_progress',
            'total_bill' => 0,
        ]);

        $second = Transaction::query()->create([
            'customer_name' => 'Maria',
            'customer_phone' => '6282222222222',
            'status' => 'in_progress',
            'total_bill' => 0,
        ]);

        $this->assertSame('2026-07-19', $first->business_date->toDateString());
        $this->assertSame(1, $first->daily_number);
        $this->assertSame('001', $first->transaction_number);

        $this->assertSame(2, $second->daily_number);
        $this->assertSame('002', $second->transaction_number);
    }

    public function test_transaction_numbers_reset_on_a_new_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-19 22:00:00', StoreHoursService::TIMEZONE));

        $today = Transaction::query()->create([
            'customer_name' => 'Alex',
            'customer_phone' => '6281111111111',
            'status' => 'in_progress',
            'total_bill' => 0,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-07-20 09:00:00', StoreHoursService::TIMEZONE));

        $tomorrow = Transaction::query()->create([
            'customer_name' => 'Maria',
            'customer_phone' => '6282222222222',
            'status' => 'in_progress',
            'total_bill' => 0,
        ]);

        $this->assertSame('001', $today->transaction_number);
        $this->assertSame('001', $tomorrow->fresh()->transaction_number);
        $this->assertSame('2026-07-20', $tomorrow->business_date->toDateString());
    }

    public function test_next_number_endpoint_previews_without_creating(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-19 10:00:00', StoreHoursService::TIMEZONE));

        Transaction::query()->create([
            'customer_name' => 'Alex',
            'customer_phone' => '6281111111111',
            'status' => 'in_progress',
            'total_bill' => 0,
        ]);

        $response = $this->getJson('/api/transaction/next-number');

        $response->assertOk();
        $response->assertJson(['transaction_number' => '002']);
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_api_list_includes_is_admin_created_flag(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-19 10:00:00', StoreHoursService::TIMEZONE));

        $adminTransaction = Transaction::query()->create([
            'customer_name' => 'Admin Order',
            'customer_phone' => '6281111111111',
            'status' => 'in_progress',
            'total_bill' => 1000,
            'is_admin_created' => true,
        ]);

        $customerTransaction = Transaction::query()->create([
            'customer_name' => 'Customer Order',
            'customer_phone' => '6282222222222',
            'status' => 'in_progress',
            'total_bill' => 2000,
            'is_admin_created' => false,
        ]);

        $response = $this->getJson('/api/transaction/today');

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $adminTransaction->id,
            'is_admin_created' => true,
        ]);
        $response->assertJsonFragment([
            'id' => $customerTransaction->id,
            'is_admin_created' => false,
        ]);
    }

    public function test_api_list_uses_three_digit_daily_number(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-19 10:00:00', StoreHoursService::TIMEZONE));

        $transaction = Transaction::query()->create([
            'customer_name' => 'Alex',
            'customer_phone' => '6281111111111',
            'status' => 'in_progress',
            'total_bill' => 1000,
        ]);

        $transaction->update([
            'daily_number' => 5,
        ]);

        $this->assertSame('005', TransactionNumberService::format(5));

        $response = $this->getJson("/api/transaction/detail/{$transaction->id}");

        $response->assertOk();
        $response->assertJsonFragment([
            'transaction_number' => '005',
        ]);
    }
}
