<?php

namespace App\Services;

use App\Models\TransactionItem;

class MenuReportService
{
    public function __construct(private StoreHoursService $storeHours) {}

    /**
     * @param  array{preset?: string|null, from?: string|null, to?: string|null}  $input
     * @return array{
     *     filters: array{preset: 'month'|'range', from: string, to: string},
     *     summary: array{menu_count: int, quantity_sold: int, revenue: int},
     *     items: list<array{rank: int, menu_id: int, menu_name: string, quantity_sold: int, revenue: int}>
     * }
     */
    public function build(array $input = []): array
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

        $rows = TransactionItem::query()
            ->selectRaw('transaction_items.menu_id as menu_id')
            ->selectRaw('MAX(transaction_items.menu_name) as menu_name')
            ->selectRaw('SUM(transaction_items.quantity) as quantity_sold')
            ->selectRaw('SUM(transaction_items.line_total) as revenue')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->whereDate('transactions.business_date', '>=', $from)
            ->whereDate('transactions.business_date', '<=', $to)
            ->groupBy('transaction_items.menu_id')
            ->orderByDesc('quantity_sold')
            ->orderByDesc('revenue')
            ->orderBy('menu_name')
            ->get();

        $items = $rows
            ->values()
            ->map(fn ($row, int $index): array => [
                'rank' => $index + 1,
                'menu_id' => (int) $row->menu_id,
                'menu_name' => (string) $row->menu_name,
                'quantity_sold' => (int) $row->quantity_sold,
                'revenue' => (int) $row->revenue,
            ])
            ->all();

        return [
            'filters' => [
                'preset' => $preset,
                'from' => $from,
                'to' => $to,
            ],
            'summary' => [
                'menu_count' => count($items),
                'quantity_sold' => (int) array_sum(array_column($items, 'quantity_sold')),
                'revenue' => (int) array_sum(array_column($items, 'revenue')),
            ],
            'items' => $items,
        ];
    }
}
