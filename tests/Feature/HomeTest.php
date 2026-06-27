<?php

namespace Tests\Feature;

use App\Models\OperatingHour;
use App\Services\StoreHoursService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        OperatingHour::ensureWeekExists();
    }

    public function test_home_page_renders_successfully(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('home')
            ->has('storeStatus')
            ->has('nextSession')
        );
    }

    public function test_next_session_returns_current_session_when_open(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-27 10:00:00', StoreHoursService::TIMEZONE));

        OperatingHour::query()
            ->where('day_of_week', 6)
            ->update([
                'is_closed' => false,
                'session_1_starts_at' => '09:00:00',
                'session_1_ends_at' => '12:00:00',
                'session_2_starts_at' => '15:00:00',
                'session_2_ends_at' => '18:00:00',
            ]);

        $nextSession = app(StoreHoursService::class)->nextSessionToday();

        $this->assertNotNull($nextSession);
        $this->assertSame('current', $nextSession['context']);
        $this->assertSame(1, $nextSession['session_number']);
        $this->assertSame('9:00 AM – 12:00 PM', $nextSession['time_range_formatted']);

        Carbon::setTestNow();
    }

    public function test_next_session_returns_upcoming_session_when_between_sessions(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-27 13:00:00', StoreHoursService::TIMEZONE));

        OperatingHour::query()
            ->where('day_of_week', 6)
            ->update([
                'is_closed' => false,
                'session_1_starts_at' => '09:00:00',
                'session_1_ends_at' => '12:00:00',
                'session_2_starts_at' => '15:00:00',
                'session_2_ends_at' => '18:00:00',
            ]);

        $nextSession = app(StoreHoursService::class)->nextSessionToday();

        $this->assertNotNull($nextSession);
        $this->assertSame('upcoming', $nextSession['context']);
        $this->assertSame(2, $nextSession['session_number']);
        $this->assertSame('3:00 PM – 6:00 PM', $nextSession['time_range_formatted']);

        Carbon::setTestNow();
    }

    public function test_next_session_is_null_on_weekly_closed_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-28 10:00:00', StoreHoursService::TIMEZONE));

        $nextSession = app(StoreHoursService::class)->nextSessionToday();

        $this->assertNull($nextSession);

        Carbon::setTestNow();
    }
}
