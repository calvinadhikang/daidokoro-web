<?php

namespace Tests\Feature;

use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_index_renders(): void
    {
        $response = $this->get(route('admin.reports.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/reports/index')
        );
    }

    public function test_sales_report_defaults_to_today(): void
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        Transaction::query()->create([
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

        $response = $this->get(route('admin.reports.sales'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/reports/sales')
            ->where('filters.preset', 'today')
            ->where('filters.from', $today)
            ->where('filters.to', $today)
            ->where('summary.revenue', 50000)
            ->where('summary.total_count', 2)
            ->where('summary.paid_count', 1)
            ->has('groups', 1)
            ->where('groups.0.date', $today)
            ->has('groups.0.transactions', 2)
        );
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

        $response = $this->get(route('admin.reports.sales', [
            'preset' => 'range',
            'from' => $dayOne,
            'to' => $dayTwo,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/reports/sales')
            ->where('filters.preset', 'range')
            ->where('filters.from', $dayOne)
            ->where('filters.to', $dayTwo)
            ->where('summary.revenue', 70000)
            ->where('summary.total_count', 3)
            ->where('summary.paid_count', 2)
            ->has('groups', 2)
            ->where('groups.0.date', $dayTwo)
            ->has('groups.0.transactions', 2)
            ->where('groups.1.date', $dayOne)
            ->has('groups.1.transactions', 1)
        );
    }
}
