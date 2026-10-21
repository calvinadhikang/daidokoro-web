<?php

namespace App\Services;

use App\Models\OperatingClosure;
use App\Models\OperatingHour;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class StoreHoursService
{
    public const TIMEZONE = 'Asia/Jakarta';

    public const TIMEZONE_LABEL = 'WIB';

    /**
     * @var array<int, string>
     */
    private const DAY_NAMES_ID = [
        0 => 'Minggu',
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
    ];

    /**
     * @var array<int, string>
     */
    private const MONTH_NAMES_ID = [
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Apr',
        5 => 'Mei',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Agu',
        9 => 'Sep',
        10 => 'Okt',
        11 => 'Nov',
        12 => 'Des',
    ];

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

    /**
     * @return array{
     *     session_number: int,
     *     starts_at: Carbon,
     *     ends_at: Carbon,
     * }|null
     */
    public function currentSessionWindow(?Carbon $at = null): ?array
    {
        $at = ($at ?? $this->now())->timezone(self::TIMEZONE);

        if (! $this->status($at)['is_open']) {
            return null;
        }

        $dayOfWeek = (int) $at->format('w');
        $schedule = OperatingHour::query()
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if ($schedule === null || $schedule->is_closed) {
            return null;
        }

        $currentTime = $at->format('H:i:s');
        $date = $at->toDateString();

        foreach ($this->sessionsFromSchedule($schedule) as $session) {
            if (! $this->isWithinSession($currentTime, $session['starts_at'], $session['ends_at'])) {
                continue;
            }

            return [
                'session_number' => $session['session_number'],
                'starts_at' => Carbon::parse(
                    "{$date} {$this->normalizeTime($session['starts_at'])}",
                    self::TIMEZONE,
                ),
                'ends_at' => Carbon::parse(
                    "{$date} {$this->normalizeTime($session['ends_at'])}",
                    self::TIMEZONE,
                ),
            ];
        }

        return null;
    }

    /**
     * @return array{
     *     context: 'current'|'upcoming',
     *     session_number: int,
     *     time_range_formatted: string,
     *     starts_at_formatted: string,
     *     ends_at_formatted: string,
     * }|null
     */
    public function nextSessionToday(?Carbon $at = null): ?array
    {
        $at = ($at ?? $this->now())->timezone(self::TIMEZONE);
        $status = $this->status($at);

        if (in_array($status['reason'], ['closed_period', 'weekly_closed'], true)) {
            return null;
        }

        $dayOfWeek = (int) $at->format('w');
        $schedule = OperatingHour::query()
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if ($schedule === null || $schedule->is_closed) {
            return null;
        }

        $sessions = $this->sessionsFromSchedule($schedule);

        if ($sessions === []) {
            return null;
        }

        $currentTime = $at->format('H:i:s');

        if ($status['is_open']) {
            foreach ($sessions as $session) {
                if ($this->isWithinSession($currentTime, $session['starts_at'], $session['ends_at'])) {
                    return $this->formatSessionInfo($session, 'current');
                }
            }

            return null;
        }

        foreach ($sessions as $session) {
            if ($currentTime < $this->normalizeTime($session['starts_at'])) {
                return $this->formatSessionInfo($session, 'upcoming');
            }
        }

        return null;
    }

    /**
     * The next time the store starts a session, looking ahead up to 21 days.
     *
     * @return array{
     *     opens_at: string,
     *     opens_at_formatted: string,
     *     message: string,
     * }|null
     */
    public function nextOpening(?Carbon $at = null): ?array
    {
        $at = ($at ?? $this->now())->timezone(self::TIMEZONE);
        $schedules = OperatingHour::query()->get()->keyBy('day_of_week');
        $rangeEnd = $at->copy()->addDays(20)->toDateString();
        $closures = OperatingClosure::query()
            ->whereDate('starts_at', '<=', $rangeEnd)
            ->whereDate('ends_at', '>=', $at->toDateString())
            ->get();

        for ($offset = 0; $offset < 21; $offset++) {
            $day = $at->copy()->startOfDay()->addDays($offset);
            $date = $day->toDateString();

            if ($this->dateIsClosedByClosure($closures, $date)) {
                continue;
            }

            $schedule = $schedules->get((int) $day->format('w'));

            if (! $schedule instanceof OperatingHour || $schedule->is_closed) {
                continue;
            }

            foreach ($this->sessionsFromSchedule($schedule) as $session) {
                $startsAt = Carbon::parse(
                    "{$date} {$this->normalizeTime($session['starts_at'])}",
                    self::TIMEZONE,
                );

                if ($startsAt->lessThanOrEqualTo($at)) {
                    continue;
                }

                return $this->formatNextOpening($at, $startsAt);
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, OperatingClosure>  $closures
     */
    private function dateIsClosedByClosure(Collection $closures, string $date): bool
    {
        return $closures->contains(function (OperatingClosure $closure) use ($date): bool {
            return $closure->starts_at->toDateString() <= $date
                && $closure->ends_at->toDateString() >= $date;
        });
    }

    /**
     * @return array{
     *     opens_at: string,
     *     opens_at_formatted: string,
     *     message: string,
     * }
     */
    private function formatNextOpening(Carbon $at, Carbon $startsAt): array
    {
        $time = $startsAt->format('H.i');
        $date = $startsAt->toDateString();

        if ($date === $at->toDateString()) {
            $when = "hari ini pukul {$time}";
        } elseif ($date === $at->copy()->addDay()->toDateString()) {
            $when = "besok pukul {$time}";
        } else {
            $dayName = self::DAY_NAMES_ID[(int) $startsAt->format('w')];
            $month = self::MONTH_NAMES_ID[(int) $startsAt->format('n')];
            $when = "{$dayName}, {$startsAt->format('j')} {$month} pukul {$time}";
        }

        $message = "Buka lagi {$when} ".self::TIMEZONE_LABEL;

        return [
            'opens_at' => $startsAt->toIso8601String(),
            'opens_at_formatted' => $message,
            'message' => $message,
        ];
    }

    /**
     * @return list<array{session_number: int, starts_at: string, ends_at: string}>
     */
    private function sessionsFromSchedule(OperatingHour $schedule): array
    {
        $sessions = [];

        if ($schedule->session_1_starts_at !== null && $schedule->session_1_ends_at !== null) {
            $sessions[] = [
                'session_number' => 1,
                'starts_at' => $schedule->session_1_starts_at,
                'ends_at' => $schedule->session_1_ends_at,
            ];
        }

        if ($schedule->session_2_starts_at !== null && $schedule->session_2_ends_at !== null) {
            $sessions[] = [
                'session_number' => 2,
                'starts_at' => $schedule->session_2_starts_at,
                'ends_at' => $schedule->session_2_ends_at,
            ];
        }

        return $sessions;
    }

    /**
     * @param  array{session_number: int, starts_at: string, ends_at: string}  $session
     * @return array{
     *     context: 'current'|'upcoming',
     *     session_number: int,
     *     time_range_formatted: string,
     *     starts_at_formatted: string,
     *     ends_at_formatted: string,
     * }
     */
    private function formatSessionInfo(array $session, string $context): array
    {
        $startsAtFormatted = $this->formatDisplayTime($session['starts_at']);
        $endsAtFormatted = $this->formatDisplayTime($session['ends_at']);

        return [
            'context' => $context,
            'session_number' => $session['session_number'],
            'time_range_formatted' => "{$startsAtFormatted} – {$endsAtFormatted}",
            'starts_at_formatted' => $startsAtFormatted,
            'ends_at_formatted' => $endsAtFormatted,
        ];
    }

    private function formatDisplayTime(string $time): string
    {
        return Carbon::createFromFormat(
            'H:i:s',
            $this->normalizeTime($time),
            self::TIMEZONE,
        )->format('g:i A');
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
