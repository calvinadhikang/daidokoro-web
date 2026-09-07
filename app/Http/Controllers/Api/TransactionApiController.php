<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApiTransactionRequest;
use App\Http\Requests\StoreTransactionItemRequest;
use App\Http\Requests\UpdateApiTransactionRequest;
use App\Http\Requests\UpdateTransactionItemRequest;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Services\StoreHoursService;
use App\Services\TransactionNumberService;
use App\Services\TransactionOrderService;
use App\Support\TransactionApiFormatter;
use Illuminate\Http\JsonResponse;

class TransactionApiController extends Controller
{
    public function __construct(
        private StoreHoursService $storeHours,
        private TransactionNumberService $transactionNumbers,
        private TransactionOrderService $orderService,
    ) {}

    public function store(StoreApiTransactionRequest $request): JsonResponse
    {
        $transaction = $this->orderService->createAdminTransaction($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully.',
            'transaction' => TransactionApiFormatter::formatDetail($transaction),
        ], 201);
    }

    public function nextNumber(): JsonResponse
    {
        return response()->json([
            'transaction_number' => $this->transactionNumbers->peekNextFormatted(),
        ]);
    }

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

    public function update(UpdateApiTransactionRequest $request, Transaction $transaction): JsonResponse
    {
        $transaction = $this->orderService->updateHeader($transaction, $request->validated());

        return $this->mutationResponse('Transaction updated successfully.', $transaction);
    }

    public function storeItem(StoreTransactionItemRequest $request, Transaction $transaction): JsonResponse
    {
        $validated = $request->validated();

        $this->orderService->addMenuItem(
            $transaction,
            $validated['menu_id'],
            $validated['quantity'],
            $validated['addon_option_ids'] ?? [],
            $validated['note'] ?? null,
        );

        return $this->mutationResponse(
            'Item added to transaction.',
            $transaction->fresh() ?? $transaction,
        );
    }

    public function updateItem(UpdateTransactionItemRequest $request, TransactionItem $item): JsonResponse
    {
        $validated = $request->validated();

        $item = $this->orderService->updateMenuItem(
            $item,
            $validated['quantity'],
            $validated['addon_option_ids'] ?? [],
            $validated['note'] ?? null,
        );

        return $this->mutationResponse(
            'Item updated successfully.',
            $item->transaction,
        );
    }

    public function destroyItem(TransactionItem $item): JsonResponse
    {
        $transaction = $this->orderService->deleteMenuItem($item);

        return $this->mutationResponse('Item removed from transaction.', $transaction);
    }

    private function mutationResponse(string $message, Transaction $transaction): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'transaction' => TransactionApiFormatter::formatDetail($transaction),
        ]);
    }
}
