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

        if ($channelFilter === 'all') {
            $channel = null;
            $resolvedChannelId = 'all';
        } else {
            $channel = $this->salesChannels->resolve(
                is_numeric($channelFilter) ? (int) $channelFilter : null,
            );
            $resolvedChannelId = $channel->id;
        }

        $report = $this->buildForChannel($channel, $from, $to);

        return [
            ...$report,
            'filters' => [
                'preset' => $preset,
                'from' => $from,
                'to' => $to,
                'sales_channel_id' => $resolvedChannelId,
            ],
            'channels' => $this->channelOptions(),
        ];
    }

    /**
     * @param  array{preset?: string|null, from?: string|null, to?: string|null}  $input
     * @return array{
     *     filters: array{preset: 'today'|'range'|'event', from: string, to: string, event_id: int},
     *     summary: array{revenue: int, total_count: int, paid_count: int, unpaid_count: int, unpaid_revenue: int},
     *     groups: Collection<int, array{date: string, transactions: Collection<int, Transaction>}>,
     *     events: list<array<string, mixed>>
     * }
     */
    public function buildForEvent(int $eventId, array $input = []): array
    {
        $channel = $this->salesChannels->resolveEvent($eventId);
        $today = $this->storeHours->today();
        $preset = $input['preset'] ?? 'event';

        if ($preset === 'today') {
            $from = $today;
            $to = $today;
        } elseif ($preset === 'event') {
            $from = $channel->starts_at?->toDateString() ?? $today;
            $to = $channel->ends_at?->toDateString() ?? $today;
        } else {
            $from = $input['from'] ?? $today;
            $to = $input['to'] ?? $today;
            $preset = 'range';
        }

        $report = $this->buildForChannel($channel, $from, $to);

        return [
            ...$report,
            'filters' => [
                'preset' => $preset,
                'from' => $from,
                'to' => $to,
                'event_id' => $eventId,
            ],
            'events' => $this->eventOptions(),
        ];
    }

    /**
     * @return array{
     *     summary: array{revenue: int, total_count: int, paid_count: int, unpaid_count: int, unpaid_revenue: int},
     *     groups: Collection<int, array{date: string, transactions: Collection<int, Transaction>}>
     * }
     */
    private function buildForChannel(?SalesChannel $channel, string $from, string $to): array
    {
        $query = Transaction::query()
            ->with('salesChannel')
            ->whereDate('business_date', '>=', $from)
            ->whereDate('business_date', '<=', $to);

        if ($channel !== null) {
            $query->where('sales_channel_id', $channel->id);
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

    /**
     * @return list<array<string, mixed>>
     */
    private function eventOptions(): array
    {
        return array_map(
            fn (SalesChannel $channel) => $this->salesChannels->format($channel),
            $this->salesChannels->listAllEventsForReport(),
        );
    }
}
