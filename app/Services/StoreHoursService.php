<?php

namespace App\Services;

use App\Models\OperatingClosure;
use App\Models\OperatingHour;
use Illuminate\Support\Carbon;

class StoreHoursService
{
    public const TIMEZONE = 'Asia/Jakarta';

    public const TIMEZONE_LABEL = 'WIB';

    public function now(): Carbon
    {
        return Carbon::now(self::TIMEZONE);
    }

    public function today(): string
    {
        return $this->now()->toDateString();
    }

    /**
     * @return array{
     *     is_open: bool,
     *     reason: 'open'|'closed_period'|'weekly_closed'|'outside_hours',
     *     message: string,
     *     checked_at: string,
     *     checked_at_formatted: string,
     *     timezone: string,
     *     timezone_label: string
     * }
     */
    public function status(?Carbon $at = null): array
    {
        $at = ($at ?? $this->now())->timezone(self::TIMEZONE);
        $date = $at->toDateString();
        $dayOfWeek = (int) $at->format('w');
        $currentTime = $at->format('H:i:s');

        $closure = OperatingClosure::query()
            ->whereDate('starts_at', '<=', $date)
            ->whereDate('ends_at', '>=', $date)
            ->orderBy('starts_at')
            ->first();

        if ($closure !== null) {
            return $this->buildStatus(
                isOpen: false,
                reason: 'closed_period',
                message: $closure->label !== null && $closure->label !== ''
                    ? "Closed — {$closure->label}"
                    : 'Closed — holiday / closed period',
                at: $at,
            );
        }

        $schedule = OperatingHour::query()
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if ($schedule === null || $schedule->is_closed) {
            return $this->buildStatus(
                isOpen: false,
                reason: 'weekly_closed',
                message: 'Closed today — regular day off',
                at: $at,
            );
        }

        if ($this->isWithinSession($currentTime, $schedule->session_1_starts_at, $schedule->session_1_ends_at)) {
            return $this->buildStatus(
                isOpen: true,
                reason: 'open',
                message: 'Open now',
                at: $at,
            );
        }

        if (
            $schedule->session_2_starts_at !== null
            && $schedule->session_2_ends_at !== null
            && $this->isWithinSession($currentTime, $schedule->session_2_starts_at, $schedule->session_2_ends_at)
        ) {
            return $this->buildStatus(
                isOpen: true,
                reason: 'open',
                message: 'Open now',
                at: $at,
            );
        }

        return $this->buildStatus(
            isOpen: false,
            reason: 'outside_hours',
            message: 'Closed — outside operating hours',
            at: $at,
        );
    }

    private function isWithinSession(
        string $currentTime,
        ?string $startsAt,
        ?string $endsAt,
    ): bool {
        if ($startsAt === null || $endsAt === null) {
            return false;
        }

        return $currentTime >= $this->normalizeTime($startsAt)
            && $currentTime < $this->normalizeTime($endsAt);
    }

    private function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? "{$time}:00" : $time;
    }

    /**
     * @return array{
     *     is_open: bool,
     *     reason: 'open'|'closed_period'|'weekly_closed'|'outside_hours',
     *     message: string,
     *     checked_at: string,
     *     checked_at_formatted: string,
     *     timezone: string,
     *     timezone_label: string
     * }
     */
    private function buildStatus(
        bool $isOpen,
        string $reason,
        string $message,
        Carbon $at,
    ): array {
        return [
            'is_open' => $isOpen,
            'reason' => $reason,
            'message' => $message,
            'checked_at' => $at->toIso8601String(),
            'checked_at_formatted' => $at->format('D, j M Y · g:i A').' '.self::TIMEZONE_LABEL,
            'timezone' => self::TIMEZONE,
            'timezone_label' => self::TIMEZONE_LABEL,
        ];
    }
}
