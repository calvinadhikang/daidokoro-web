<?php

namespace App\Services;

use App\Models\SalesChannel;
use App\Models\Transaction;
use Illuminate\Support\Collection;

class SalesReportService
{
    public function __construct(
        private StoreHoursService $storeHours,
        private SalesChannelService $salesChannels,
    ) {}

    /**
     * @param  array{preset?: string|null, from?: string|null, to?: string|null, sales_channel_id?: int|string|null}  $input
     * @return array{
     *     filters: array{preset: 'today'|'range', from: string, to: string, sales_channel_id: int|string},
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

        $channelFilter = $input['sales_channel_id'] ?? null;
        $query = Transaction::query()
            ->with('salesChannel')
            ->whereDate('business_date', '>=', $from)
            ->whereDate('business_date', '<=', $to);

        if ($channelFilter !== 'all') {
            $channel = $this->salesChannels->resolve(
                is_numeric($channelFilter) ? (int) $channelFilter : null,
            );
            $query->where('sales_channel_id', $channel->id);
            $resolvedChannelId = $channel->id;
        } else {
            $resolvedChannelId = 'all';
        }

        $transactions = $query
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
                'sales_channel_id' => $resolvedChannelId,
            ],
            'summary' => [
                'revenue' => (int) $paidTransactions->sum('total_bill'),
                'total_count' => $transactions->count(),
                'paid_count' => $paidTransactions->count(),
                'unpaid_count' => $unpaidTransactions->count(),
                'unpaid_revenue' => (int) $unpaidTransactions->sum('total_bill'),
            ],
            'groups' => $groups,
            'channels' => $this->channelOptions(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function channelOptions(): array
    {
        return array_map(
            fn (SalesChannel $channel) => $this->salesChannels->format($channel),
            $this->salesChannels->listForIndex(),
        );
    }
}
