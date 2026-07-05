<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\StoreHoursService;
use App\Support\TransactionApiFormatter;
use Illuminate\Http\JsonResponse;

class TransactionApiController extends Controller
{
    public function __construct(private StoreHoursService $storeHours) {}

    public function today(): JsonResponse
    {
        $query = Transaction::query()->orderByDesc('created_at');

        $sessionWindow = $this->storeHours->currentSessionWindow();

        if ($sessionWindow !== null) {
            $query->whereBetween('created_at', [
                $sessionWindow['starts_at'],
                $sessionWindow['ends_at'],
            ]);
        } else {
            $query->whereDate('created_at', $this->storeHours->today());
        }

        $transactions = $query->get();

        return response()->json(
            $transactions
                ->map(fn (Transaction $transaction) => TransactionApiFormatter::formatListItem($transaction))
                ->values()
        );
    }

    public function detail(Transaction $transaction): JsonResponse
    {
        return response()->json(TransactionApiFormatter::formatDetail($transaction));
    }

    public function markPaid(Transaction $transaction): JsonResponse
    {
        if ($transaction->isPaid()) {
            return response()->json([
                'success' => true,
                'message' => 'Transaction is already paid.',
                'transaction' => TransactionApiFormatter::formatDetail($transaction),
            ]);
        }

        $transaction->update(['status' => 'paid']);

        return response()->json([
            'success' => true,
            'message' => 'Transaction marked as paid.',
            'transaction' => TransactionApiFormatter::formatDetail($transaction->fresh()),
        ]);
    }

    public function destroy(Transaction $transaction): JsonResponse
    {
        if ($transaction->isPaid()) {
            return response()->json([
                'success' => false,
                'message' => 'Paid transactions cannot be deleted.',
            ], 422);
        }

        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transaction deleted successfully.',
        ]);
    }
}
