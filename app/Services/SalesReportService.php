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

        $groups = $transactions
            ->groupBy(fn (Transaction $transaction): string => $transaction->business_date->toDateString())
            ->map(fn (Collection $dayTransactions, string $date): array => [
                'date' => $date,
                'transactions' => $dayTransactions->values(),
            ])
            ->values();

        return [
            'summary' => $this->summarize($transactions),
            'groups' => $groups,
        ];
    }

    /**
     * Paid and unpaid totals for every event channel in a date window.
     * Store transactions are excluded. Events with no transactions in the window are omitted.
     *
     * @param  array{preset?: string|null, from?: string|null, to?: string|null}  $input
     * @return array{
     *     filters: array{preset: 'month'|'range', from: string, to: string},
     *     summary: array{revenue: int, total_count: int, paid_count: int, unpaid_count: int, unpaid_revenue: int},
     *     events: list<array<string, mixed>>
     * }
     */
    public function buildEventSummary(array $input = []): array
    {
        $now = $this->storeHours->now();
        $preset = $input['preset'] ?? 'month';

        if ($preset === 'month') {
            $from = $now->copy()->startOfMonth()->toDateString();
            $to = $now->copy()->endOfMonth()->toDateString();
        } else {
            $from = $input['from'] ?? $now->toDateString();
            $to = $input['to'] ?? $now->toDateString();
            $preset = 'range';
        }

        $transactions = Transaction::query()
            ->with('salesChannel')
            ->whereDate('business_date', '>=', $from)
            ->whereDate('business_date', '<=', $to)
            ->whereHas('salesChannel', function ($query): void {
                $query->where('type', SalesChannel::TYPE_EVENT);
            })
            ->orderByDesc('business_date')
            ->orderByDesc('created_at')
            ->get();

        $events = $transactions
            ->groupBy('sales_channel_id')
            ->map(function (Collection $eventTransactions): ?array {
                $channel = $eventTransactions->first()?->salesChannel;

                if ($channel === null || ! $channel->isEvent()) {
                    return null;
                }

                return [
                    ...$this->salesChannels->format($channel),
                    'summary' => $this->summarize($eventTransactions),
                ];
            })
            ->filter()
            ->sort(function (array $left, array $right): int {
                $byRevenue = $right['summary']['revenue'] <=> $left['summary']['revenue'];

                if ($byRevenue !== 0) {
                    return $byRevenue;
                }

                return strcmp((string) ($right['starts_at'] ?? ''), (string) ($left['starts_at'] ?? ''));
            })
            ->values()
            ->all();

        return [
            'filters' => [
                'preset' => $preset === 'month' ? 'month' : 'range',
                'from' => $from,
                'to' => $to,
            ],
            'summary' => $this->summarize($transactions),
            'events' => $events,
        ];
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     * @return array{revenue: int, total_count: int, paid_count: int, unpaid_count: int, unpaid_revenue: int}
     */
    private function summarize(Collection $transactions): array
    {
        $paidTransactions = $transactions->where('status', 'paid');
        $unpaidTransactions = $transactions->where('status', 'in_progress');

        return [
            'revenue' => (int) $paidTransactions->sum('total_bill'),
            'total_count' => $transactions->count(),
            'paid_count' => $paidTransactions->count(),
            'unpaid_count' => $unpaidTransactions->count(),
            'unpaid_revenue' => (int) $unpaidTransactions->sum('total_bill'),
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
