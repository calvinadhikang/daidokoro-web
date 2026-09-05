<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Collection;

class SalesReportService
{
    public function __construct(private StoreHoursService $storeHours) {}

    /**
     * @param  array{preset?: string|null, from?: string|null, to?: string|null}  $input
     * @return array{
     *     filters: array{preset: 'today'|'range', from: string, to: string},
     *     summary: array{revenue: int, total_count: int, paid_count: int, unpaid_count: int, unpaid_revenue: int},
     *     groups: Collection<int, array{date: string, transactions: Collection<int, Transaction>}>
     * }
     */
    public function build(array $input = []): array
    {
        $today = $this->storeHours->today();
        $preset = $input['preset'] ?? 'today';

        if ($preset === 'today') {
            $from = $today;
            $to = $today;
        } else {
            $from = $input['from'] ?? $today;
            $to = $input['to'] ?? $today;
            $preset = 'range';
        }

        $transactions = Transaction::query()
            ->whereDate('business_date', '>=', $from)
            ->whereDate('business_date', '<=', $to)
            ->orderByDesc('business_date')
            ->orderByDesc('created_at')
            ->get();

        $paidTransactions = $transactions->where('status', 'paid');
        $unpaidTransactions = $transactions->where('status', 'in_progress');

        $groups = $transactions
            ->groupBy(fn (Transaction $transaction): string => $transaction->business_date->toDateString())
            ->map(fn (Collection $dayTransactions, string $date): array => [
                'date' => $date,
                'transactions' => $dayTransactions->values(),
            ])
            ->values();

        return [
            'filters' => [
                'preset' => $preset,
                'from' => $from,
                'to' => $to,
            ],
            'summary' => [
                'revenue' => (int) $paidTransactions->sum('total_bill'),
                'total_count' => $transactions->count(),
                'paid_count' => $paidTransactions->count(),
                'unpaid_count' => $unpaidTransactions->count(),
                'unpaid_revenue' => (int) $unpaidTransactions->sum('total_bill'),
            ],
            'groups' => $groups,
        ];
    }
}
