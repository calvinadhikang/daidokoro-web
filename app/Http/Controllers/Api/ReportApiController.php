<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalesReportRequest;
use App\Models\Transaction;
use App\Services\SalesReportService;
use App\Support\TransactionApiFormatter;
use Illuminate\Http\JsonResponse;

class ReportApiController extends Controller
{
    public function __construct(private SalesReportService $salesReports) {}

    public function sales(SalesReportRequest $request): JsonResponse
    {
        $report = $this->salesReports->build($request->validated());

        $report['groups'] = $report['groups']
            ->map(fn (array $group): array => [
                'date' => $group['date'],
                'transactions' => $group['transactions']
                    ->map(fn (Transaction $transaction): array => [
                        ...TransactionApiFormatter::formatListItem($transaction),
                        'created_at' => $transaction->created_at?->toIso8601String(),
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        return response()->json($report);
    }
}
