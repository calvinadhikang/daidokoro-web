<?php

namespace Tests\Feature;

use App\Models\OperatingClosure;
use App\Models\OperatingHour;
use App\Services\StoreHoursService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperatingHoursApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_week_schedule_closures_and_status(): void
    {
        OperatingHour::ensureWeekExists();

        $today = app(StoreHoursService::class)->today();

        OperatingClosure::query()->create([
            'starts_at' => $today,
            'ends_at' => $today,
            'label' => 'Libur Nasional',
        ]);

        $response = $this->getJson('/api/hours');

        $response->assertOk();
        $response->assertJsonCount(7, 'days');
        $response->assertJsonPath('closures.0.label', 'Libur Nasional');
        $response->assertJsonStructure([
            'days' => [
                '*' => [
                    'day_of_week',
                    'day_name',
                    'is_closed',
                    'session_1_starts_at',
                    'session_1_ends_at',
                    'session_2_starts_at',
                    'session_2_ends_at',
                ],
            ],
            'closures' => [
                '*' => ['id', 'starts_at', 'ends_at', 'label'],
            ],
            'storeStatus' => [
                'is_open',
                'reason',
                'message',
                'checked_at',
                'checked_at_formatted',
                'timezone',
                'timezone_label',
            ],
            'today',
        ]);
        $response->assertJsonPath('storeStatus.timezone', 'Asia/Jakarta');
    }

    public function test_update_saves_weekly_schedule(): void
    {
        OperatingHour::ensureWeekExists();

        $days = [];
        for ($day = 0; $day <= 6; $day++) {
            $days[] = [
                'day_of_week' => $day,
                'is_closed' => $day === 0,
                'session_1_starts_at' => $day === 0 ? null : '10:00',
                'session_1_ends_at' => $day === 0 ? null : '13:00',
                'session_2_starts_at' => $day === 0 ? null : '16:00',
                'session_2_ends_at' => $day === 0 ? null : '19:00',
            ];
        }

        $response = $this->postJson('/api/hours/update', ['days' => $days]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $monday = OperatingHour::query()->where('day_of_week', 1)->first();
        $this->assertNotNull($monday);
        $this->assertFalse($monday->is_closed);
        $this->assertSame('10:00:00', $monday->session_1_starts_at);
        $this->assertSame('13:00:00', $monday->session_1_ends_at);
        $this->assertSame('16:00:00', $monday->session_2_starts_at);
        $this->assertSame('19:00:00', $monday->session_2_ends_at);

        $sunday = OperatingHour::query()->where('day_of_week', 0)->first();
        $this->assertNotNull($sunday);
        $this->assertTrue($sunday->is_closed);
        $this->assertNull($sunday->session_1_starts_at);
    }

    public function test_update_rejects_invalid_session_order(): void
    {
        OperatingHour::ensureWeekExists();

        $days = [];
        for ($day = 0; $day <= 6; $day++) {
            $days[] = [
                'day_of_week' => $day,
                'is_closed' => false,
                'session_1_starts_at' => '14:00',
                'session_1_ends_at' => '12:00',
                'session_2_starts_at' => null,
                'session_2_ends_at' => null,
            ];
        }

        $response = $this->postJson('/api/hours/update', ['days' => $days]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['days.0.session_1_ends_at']);
    }

    public function test_store_closure_creates_closed_period(): void
    {
        $today = app(StoreHoursService::class)->today();

        $response = $this->postJson('/api/hours/closures/create', [
            'starts_at' => $today,
            'ends_at' => $today,
            'label' => 'Maintenance',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('closures.0.label', 'Maintenance');

        $this->assertDatabaseHas('operating_closures', [
            'starts_at' => $today,
            'ends_at' => $today,
            'label' => 'Maintenance',
        ]);
    }

    public function test_destroy_closure_removes_closed_period(): void
    {
        $today = app(StoreHoursService::class)->today();

        $closure = OperatingClosure::query()->create([
            'starts_at' => $today,
            'ends_at' => $today,
            'label' => 'Temporary',
        ]);

        $response = $this->postJson("/api/hours/closures/delete/{$closure->id}");

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertDatabaseMissing('operating_closures', ['id' => $closure->id]);
    }
}
