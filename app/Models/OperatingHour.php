<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $day_of_week
 * @property bool $is_closed
 * @property string|null $session_1_starts_at
 * @property string|null $session_1_ends_at
 * @property string|null $session_2_starts_at
 * @property string|null $session_2_ends_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'day_of_week',
    'is_closed',
    'session_1_starts_at',
    'session_1_ends_at',
    'session_2_starts_at',
    'session_2_ends_at',
])]
class OperatingHour extends Model
{
    /**
     * @var array<int, string>
     */
    public const DAY_NAMES = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_closed' => 'boolean',
        ];
    }

    public static function ensureWeekExists(): void
    {
        for ($day = 0; $day <= 6; $day++) {
            self::query()->firstOrCreate(
                ['day_of_week' => $day],
                [
                    'is_closed' => $day === 0,
                    'session_1_starts_at' => $day === 0 ? null : '09:00:00',
                    'session_1_ends_at' => $day === 0 ? null : '12:00:00',
                    'session_2_starts_at' => $day === 0 ? null : '15:00:00',
                    'session_2_ends_at' => $day === 0 ? null : '18:00:00',
                ],
            );
        }
    }

    public static function formatTimeForInput(?string $time): ?string
    {
        if ($time === null) {
            return null;
        }

        return substr($time, 0, 5);
    }
}
