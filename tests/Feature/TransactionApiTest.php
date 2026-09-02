<?php

namespace Tests\Feature;

use App\Models\MenuModel;
use App\Models\OperatingHour;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Services\StoreHoursService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        OperatingHour::ensureWeekExists();
    }

    public function test_today_returns_transactions_for_current_operating_session(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-06 10:00:00', StoreHoursService::TIMEZONE));

        $menu = MenuModel::query()->create([
            'name' => 'Salmon Roll',
            'price' => 45000,
            'is_available' => true,
        ]);

        $inSession = Transaction::query()->create([
            'customer_name' => 'Alex Tan',
            'customer_phone' => '081234567890',
            'status' => 'in_progress',
            'total_bill' => 45000,
            'service_type' => 'dine_in',
            'table_code' => 'A1',
        ]);
        $inSession->created_at = Carbon::parse('2026-07-06 09:30:00', StoreHoursService::TIMEZONE);
        $inSession->saveQuietly();

        TransactionItem::query()->create([
            'transaction_id' => $inSession->id,
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            'quantity' => 1,
            'unit_price' => 45000,
            'line_total' => 45000,
        ]);

        $outsideSession = Transaction::query()->create([
            'customer_name' => 'Maria Santos',
            'customer_phone' => '089876543210',
            'status' => 'paid',
            'total_bill' => 30000,
        ]);
        $outsideSession->created_at = Carbon::parse('2026-07-06 13:00:00', StoreHoursService::TIMEZONE);
        $outsideSession->saveQuietly();

        TransactionItem::query()->create([
            'transaction_id' => $outsideSession->id,
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            'quantity' => 1,
            'unit_price' => 30000,
            'line_total' => 30000,
        ]);

        $response = $this->getJson('/api/transaction/today');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'id' => $inSession->id,
            'name' => 'Alex Tan',
            'status' => 'in_progress',
            'total_amount' => '45000',
            'table_code' => 'A1',
        ]);
        $response->assertJsonMissing(['id' => $outsideSession->id]);
    }

    public function test_today_falls_back_to_calendar_day_when_store_is_closed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-06 20:00:00', StoreHoursService::TIMEZONE));

        $transaction = Transaction::query()->create([
            'customer_name' => 'Alex Tan',
            'customer_phone' => '081234567890',
            'status' => 'in_progress',
            'total_bill' => 25000,
        ]);
        $transaction->created_at = Carbon::parse('2026-07-06 19:30:00', StoreHoursService::TIMEZONE);
        $transaction->saveQuietly();

        $response = $this->getJson('/api/transaction/today');

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $transaction->id,
            'name' => 'Alex Tan',
        ]);
    }

    public function test_detail_returns_transaction_with_order_groups(): void
    {
        $menu = MenuModel::query()->create([
            'name' => 'Chicken Rice',
            'price' => 35000,
            'is_available' => true,
        ]);

        $transaction = Transaction::query()->create([
            'customer_name' => 'Alex Tan',
            'customer_phone' => '081234567890',
            'service_type' => 'dine_in',
            'table_code' => 'B2',
            'status' => 'in_progress',
            'total_bill' => 70000,
        ]);

        TransactionItem::query()->create([
            'transaction_id' => $transaction->id,
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            'quantity' => 2,
            'unit_price' => 35000,
            'line_total' => 70000,
        ]);

        $response = $this->getJson("/api/transaction/detail/{$transaction->id}");

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $transaction->id,
            'name' => 'Alex Tan',
            'customer_phone' => '081234567890',
            'service_type' => 'dine_in',
            'table_code' => 'B2',
            'status' => 'in_progress',
            'total_amount' => '70000',
        ]);
        $response->assertJsonStructure([
            'order_items' => [
                ['id', 'menu_id', 'menu', 'quantity', 'line_total'],
            ],
            'item_groups' => [
                ['ordered_at', 'items'],
            ],
        ]);
    }

    public function test_mark_paid_updates_transaction_status(): void
    {
        $transaction = Transaction::query()->create([
            'customer_name' => 'Alex Tan',
            'customer_phone' => '081234567890',
            'status' => 'in_progress',
            'total_bill' => 50000,
        ]);

        $response = $this->postJson("/api/transaction/mark-paid/{$transaction->id}");

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'transaction' => [
                'id' => $transaction->id,
                'status' => 'paid',
            ],
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'paid',
        ]);
    }

    public function test_mark_paid_is_idempotent_for_paid_transactions(): void
    {
        $transaction = Transaction::query()->create([
            'customer_name' => 'Alex Tan',
            'customer_phone' => '081234567890',
            'status' => 'paid',
            'total_bill' => 50000,
        ]);

        $response = $this->postJson("/api/transaction/mark-paid/{$transaction->id}");

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'transaction' => [
                'status' => 'paid',
            ],
        ]);
    }

    public function test_delete_rejects_paid_transactions(): void
    {
        $transaction = Transaction::query()->create([
            'customer_name' => 'Alex Tan',
            'customer_phone' => '081234567890',
            'status' => 'paid',
            'total_bill' => 50000,
        ]);

        $response = $this->postJson("/api/transaction/delete/{$transaction->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);
    }

    public function test_delete_removes_in_progress_transaction(): void
    {
        $transaction = Transaction::query()->create([
            'customer_name' => 'Alex Tan',
            'customer_phone' => '081234567890',
            'status' => 'in_progress',
            'total_bill' => 50000,
        ]);

        $response = $this->postJson("/api/transaction/delete/{$transaction->id}");

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    }
}
