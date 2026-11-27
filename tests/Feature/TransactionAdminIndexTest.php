<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Services\StoreHoursService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionAdminIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();
    }

    public function test_index_defaults_to_todays_transactions(): void
    {
        $today = app(StoreHoursService::class)->today();
        $yesterday = now()->timezone(StoreHoursService::TIMEZONE)->subDay()->toDateString();

        Transaction::query()->create([
            'customer_name' => 'Today Guest',
            'customer_phone' => '6281111111111',
            'service_type' => 'dine_in',
            'status' => 'in_progress',
            'total_bill' => 25000,
            'business_date' => $today,
            'daily_number' => 1,
        ]);

        Transaction::query()->create([
            'customer_name' => 'Yesterday Guest',
            'customer_phone' => '6282222222222',
            'service_type' => 'takeaway',
            'status' => 'paid',
            'total_bill' => 40000,
            'business_date' => $yesterday,
            'daily_number' => 1,
        ]);

        $response = $this->get(route('admin.transaction.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/transaction/index')
            ->where('filters.date', $today)
            ->where('filters.today', $today)
            ->has('transactions', 1)
            ->where('transactions.0.customer_name', 'Today Guest')
        );
    }

    public function test_index_filters_by_business_date(): void
    {
        $today = app(StoreHoursService::class)->today();
        $yesterday = now()->timezone(StoreHoursService::TIMEZONE)->subDay()->toDateString();

        Transaction::query()->create([
            'customer_name' => 'Today Guest',
            'customer_phone' => '6281111111111',
            'service_type' => 'dine_in',
            'status' => 'in_progress',
            'total_bill' => 25000,
            'business_date' => $today,
            'daily_number' => 1,
        ]);

        Transaction::query()->create([
            'customer_name' => 'Yesterday Guest',
            'customer_phone' => '6282222222222',
            'service_type' => 'takeaway',
            'status' => 'paid',
            'total_bill' => 40000,
            'business_date' => $yesterday,
            'daily_number' => 1,
        ]);

        $response = $this->get(route('admin.transaction.index', ['date' => $yesterday]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/transaction/index')
            ->where('filters.date', $yesterday)
            ->where('filters.today', $today)
            ->has('transactions', 1)
            ->where('transactions.0.customer_name', 'Yesterday Guest')
        );
    }

    public function test_index_ignores_an_invalid_date_and_shows_today(): void
    {
        $today = app(StoreHoursService::class)->today();

        Transaction::query()->create([
            'customer_name' => 'Today Guest',
            'customer_phone' => '6281111111111',
            'service_type' => 'dine_in',
            'status' => 'paid',
            'total_bill' => 10000,
            'business_date' => $today,
            'daily_number' => 1,
        ]);

        $response = $this->get(route('admin.transaction.index', ['date' => '2026-02-31']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('filters.date', $today)
            ->has('transactions', 1)
            ->where('transactions.0.customer_name', 'Today Guest')
        );
    }
}
