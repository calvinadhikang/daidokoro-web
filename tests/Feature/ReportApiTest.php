<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Services\StoreHoursService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
