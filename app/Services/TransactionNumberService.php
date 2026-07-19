<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionNumberService
{
    /**
     * @return array{business_date: string, daily_number: int}
     */
    public function allocateNext(?Carbon $at = null): array
    {
        $date = ($at ?? Carbon::now(StoreHoursService::TIMEZONE))
            ->timezone(StoreHoursService::TIMEZONE)
            ->toDateString();

        $allocate = function () use ($date): array {
            $max = Transaction::query()
                ->whereDate('business_date', $date)
                ->lockForUpdate()
                ->max('daily_number');

            return [
                'business_date' => $date,
                'daily_number' => ((int) $max) + 1,
            ];
        };

        if (DB::transactionLevel() > 0) {
            return $allocate();
        }

        return DB::transaction($allocate);
    }

    public function peekNextFormatted(?Carbon $at = null): string
    {
        $date = ($at ?? Carbon::now(StoreHoursService::TIMEZONE))
            ->timezone(StoreHoursService::TIMEZONE)
            ->toDateString();

        $max = Transaction::query()
            ->whereDate('business_date', $date)
            ->max('daily_number');

        return self::format(((int) $max) + 1);
    }

    public static function format(int $dailyNumber): string
    {
        return str_pad((string) $dailyNumber, 3, '0', STR_PAD_LEFT);
    }
}
