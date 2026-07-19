<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/reports/index');
    }

    public function sales(Request $request): Response
    {
        $validated = $request->validate([
            'preset' => ['nullable', 'in:today,range'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $preset = $validated['preset'] ?? 'today';
        $today = now()->toDateString();

        if ($preset === 'today') {
            $from = $today;
            $to = $today;
        } else {
            $from = $validated['from'] ?? $today;
            $to = $validated['to'] ?? $today;
            $preset = 'range';
        }

        $transactions = Transaction::query()
            ->whereDate('business_date', '>=', $from)
            ->whereDate('business_date', '<=', $to)
            ->orderByDesc('business_date')
            ->orderByDesc('created_at')
            ->get();

        $paidTransactions = $transactions->where('status', 'paid');

        $groups = $transactions
            ->groupBy(fn (Transaction $transaction): string => $transaction->business_date->toDateString())
            ->map(fn ($dayTransactions, string $date): array => [
                'date' => $date,
                'transactions' => $dayTransactions->values(),
            ])
            ->values();

        return Inertia::render('admin/reports/sales', [
            'filters' => [
                'preset' => $preset,
                'from' => $from,
                'to' => $to,
            ],
            'summary' => [
                'revenue' => (int) $paidTransactions->sum('total_bill'),
                'total_count' => $transactions->count(),
                'paid_count' => $paidTransactions->count(),
            ],
            'groups' => $groups,
        ]);
    }
}
