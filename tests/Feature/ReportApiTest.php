<?php

namespace Tests\Feature;

use App\Models\MenuModel;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Services\StoreHoursService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_report_defaults_to_today(): void
    {
        $today = app(StoreHoursService::class)->today();
        $yesterday = now()->subDay()->toDateString();

        $paid = Transaction::query()->create([
            'customer_name' => 'Today Paid',
            'customer_phone' => '6281111111111',
            'service_type' => 'dine_in',
            'status' => 'paid',
            'total_bill' => 50000,
            'business_date' => $today,
            'daily_number' => 1,
        ]);

        Transaction::query()->create([
            'customer_name' => 'Today Open',
            'customer_phone' => '6282222222222',
            'service_type' => 'takeaway',
            'status' => 'in_progress',
            'total_bill' => 20000,
            'business_date' => $today,
            'daily_number' => 2,
        ]);

        Transaction::query()->create([
            'customer_name' => 'Yesterday Paid',
            'customer_phone' => '6283333333333',
            'service_type' => 'dine_in',
            'status' => 'paid',
            'total_bill' => 90000,
            'business_date' => $yesterday,
            'daily_number' => 1,
        ]);

        $response = $this->getJson('/api/report/sales');

        $response->assertOk();
        $response->assertJsonPath('filters.preset', 'today');
        $response->assertJsonPath('filters.from', $today);
        $response->assertJsonPath('filters.to', $today);
        $response->assertJsonPath('summary.revenue', 50000);
        $response->assertJsonPath('summary.total_count', 2);
        $response->assertJsonPath('summary.paid_count', 1);
        $response->assertJsonPath('summary.unpaid_count', 1);
        $response->assertJsonPath('summary.unpaid_revenue', 20000);
        $response->assertJsonCount(1, 'groups');
        $response->assertJsonPath('groups.0.date', $today);
        $response->assertJsonCount(2, 'groups.0.transactions');
        $response->assertJsonFragment([
            'id' => $paid->id,
            'transaction_number' => $paid->transaction_number,
            'name' => 'Today Paid',
            'status' => 'paid',
            'total_amount' => '50000',
            'is_admin_created' => false,
            'service_type' => 'dine_in',
        ]);
        $this->assertArrayHasKey('created_at', $response->json('groups.0.transactions.0'));
    }

    public function test_sales_report_filters_by_date_range_and_groups_by_date(): void
    {
        $dayOne = now()->subDays(2)->toDateString();
        $dayTwo = now()->subDay()->toDateString();

        Transaction::query()->create([
            'customer_name' => 'Day One',
            'customer_phone' => '6281111111111',
            'service_type' => 'dine_in',
            'status' => 'paid',
            'total_bill' => 30000,
            'business_date' => $dayOne,
            'daily_number' => 1,
        ]);

        Transaction::query()->create([
            'customer_name' => 'Day Two A',
            'customer_phone' => '6282222222222',
            'service_type' => 'takeaway',
            'status' => 'paid',
            'total_bill' => 40000,
            'business_date' => $dayTwo,
            'daily_number' => 1,
        ]);

        Transaction::query()->create([
            'customer_name' => 'Day Two B',
            'customer_phone' => '6283333333333',
            'service_type' => 'dine_in',
            'status' => 'in_progress',
            'total_bill' => 10000,
            'business_date' => $dayTwo,
            'daily_number' => 2,
        ]);

        $response = $this->getJson('/api/report/sales?'.http_build_query([
            'preset' => 'range',
            'from' => $dayOne,
            'to' => $dayTwo,
        ]));

        $response->assertOk();
        $response->assertJsonPath('filters.preset', 'range');
        $response->assertJsonPath('filters.from', $dayOne);
        $response->assertJsonPath('filters.to', $dayTwo);
        $response->assertJsonPath('summary.revenue', 70000);
        $response->assertJsonPath('summary.total_count', 3);
        $response->assertJsonPath('summary.paid_count', 2);
        $response->assertJsonPath('summary.unpaid_count', 1);
        $response->assertJsonPath('summary.unpaid_revenue', 10000);
        $response->assertJsonCount(2, 'groups');
        $response->assertJsonPath('groups.0.date', $dayTwo);
        $response->assertJsonCount(2, 'groups.0.transactions');
        $response->assertJsonPath('groups.1.date', $dayOne);
        $response->assertJsonCount(1, 'groups.1.transactions');
    }

    public function test_sales_report_rejects_range_when_to_is_before_from(): void
    {
        $response = $this->getJson('/api/report/sales?'.http_build_query([
            'preset' => 'range',
            'from' => '2026-09-05',
            'to' => '2026-09-01',
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['to']);
    }

    public function test_menu_report_defaults_to_current_month_and_ranks_by_quantity(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-15 12:00:00', StoreHoursService::TIMEZONE));

        $salmon = MenuModel::query()->create([
            'name' => 'Salmon Roll',
            'price' => 45000,
            'is_available' => true,
        ]);
        $tuna = MenuModel::query()->create([
            'name' => 'Tuna Roll',
            'price' => 40000,
            'is_available' => true,
        ]);

        $thisMonth = Transaction::query()->create([
            'customer_name' => 'This Month',
            'customer_phone' => '6281111111111',
            'service_type' => 'dine_in',
            'status' => 'paid',
            'total_bill' => 130000,
            'business_date' => '2026-09-10',
            'daily_number' => 1,
        ]);
        TransactionItem::query()->create([
            'transaction_id' => $thisMonth->id,
            'menu_id' => $salmon->id,
            'menu_name' => $salmon->name,
            'quantity' => 2,
            'unit_price' => 45000,
            'line_total' => 90000,
        ]);
        TransactionItem::query()->create([
            'transaction_id' => $thisMonth->id,
            'menu_id' => $tuna->id,
            'menu_name' => $tuna->name,
            'quantity' => 1,
            'unit_price' => 40000,
            'line_total' => 40000,
        ]);

        $alsoThisMonth = Transaction::query()->create([
            'customer_name' => 'Also This Month',
            'customer_phone' => '6282222222222',
            'service_type' => 'takeaway',
            'status' => 'in_progress',
            'total_bill' => 45000,
            'business_date' => '2026-09-01',
            'daily_number' => 1,
        ]);
        TransactionItem::query()->create([
            'transaction_id' => $alsoThisMonth->id,
            'menu_id' => $salmon->id,
            'menu_name' => $salmon->name,
            'quantity' => 1,
            'unit_price' => 45000,
            'line_total' => 45000,
        ]);

        $lastMonth = Transaction::query()->create([
            'customer_name' => 'Last Month',
            'customer_phone' => '6283333333333',
            'service_type' => 'dine_in',
            'status' => 'paid',
            'total_bill' => 400000,
            'business_date' => '2026-08-31',
            'daily_number' => 1,
        ]);
        TransactionItem::query()->create([
            'transaction_id' => $lastMonth->id,
            'menu_id' => $tuna->id,
            'menu_name' => $tuna->name,
            'quantity' => 10,
            'unit_price' => 40000,
            'line_total' => 400000,
        ]);

        $response = $this->getJson('/api/report/menus');

        $response->assertOk();
        $response->assertJsonPath('filters.preset', 'month');
        $response->assertJsonPath('filters.from', '2026-09-01');
        $response->assertJsonPath('filters.to', '2026-09-30');
        $response->assertJsonPath('summary.menu_count', 2);
        $response->assertJsonPath('summary.quantity_sold', 4);
        $response->assertJsonPath('summary.revenue', 175000);
        $response->assertJsonCount(2, 'items');
        $response->assertJsonPath('items.0.rank', 1);
        $response->assertJsonPath('items.0.menu_id', $salmon->id);
        $response->assertJsonPath('items.0.menu_name', 'Salmon Roll');
        $response->assertJsonPath('items.0.quantity_sold', 3);
        $response->assertJsonPath('items.0.revenue', 135000);
        $response->assertJsonPath('items.1.rank', 2);
        $response->assertJsonPath('items.1.menu_id', $tuna->id);
        $response->assertJsonPath('items.1.quantity_sold', 1);
        $response->assertJsonPath('items.1.revenue', 40000);
    }

    public function test_menu_report_filters_by_date_range(): void
    {
        $salmon = MenuModel::query()->create([
            'name' => 'Salmon Roll',
            'price' => 45000,
            'is_available' => true,
        ]);

        $inRange = Transaction::query()->create([
            'customer_name' => 'In Range',
            'customer_phone' => '6281111111111',
            'service_type' => 'dine_in',
            'status' => 'paid',
            'total_bill' => 90000,
            'business_date' => '2026-08-20',
            'daily_number' => 1,
        ]);
        TransactionItem::query()->create([
            'transaction_id' => $inRange->id,
            'menu_id' => $salmon->id,
            'menu_name' => $salmon->name,
            'quantity' => 2,
            'unit_price' => 45000,
            'line_total' => 90000,
        ]);

        $outOfRange = Transaction::query()->create([
            'customer_name' => 'Out Of Range',
            'customer_phone' => '6282222222222',
            'service_type' => 'takeaway',
            'status' => 'paid',
            'total_bill' => 45000,
            'business_date' => '2026-08-19',
            'daily_number' => 1,
        ]);
        TransactionItem::query()->create([
            'transaction_id' => $outOfRange->id,
            'menu_id' => $salmon->id,
            'menu_name' => $salmon->name,
            'quantity' => 1,
            'unit_price' => 45000,
            'line_total' => 45000,
        ]);

        $response = $this->getJson('/api/report/menus?'.http_build_query([
            'preset' => 'range',
            'from' => '2026-08-20',
            'to' => '2026-08-25',
        ]));

        $response->assertOk();
        $response->assertJsonPath('filters.preset', 'range');
        $response->assertJsonPath('filters.from', '2026-08-20');
        $response->assertJsonPath('filters.to', '2026-08-25');
        $response->assertJsonPath('summary.quantity_sold', 2);
        $response->assertJsonPath('summary.revenue', 90000);
        $response->assertJsonCount(1, 'items');
        $response->assertJsonPath('items.0.quantity_sold', 2);
    }

    public function test_menu_report_rejects_range_when_to_is_before_from(): void
    {
        $response = $this->getJson('/api/report/menus?'.http_build_query([
            'preset' => 'range',
            'from' => '2026-09-05',
            'to' => '2026-09-01',
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['to']);
    }
}
