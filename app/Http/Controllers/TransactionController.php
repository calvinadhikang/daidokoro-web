<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionItemRequest;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionItemRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\MenuModel;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Services\TransactionOrderService;
use App\Support\TransactionItemGrouper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function __construct(private TransactionOrderService $orderService) {}

    public function index(): Response
    {
        $transactions = Transaction::query()
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('admin/transaction/index', [
            'transactions' => $transactions,
        ]);
    }

    public function history(Request $request): Response
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $validated['from'] ?? now()->startOfMonth()->toDateString();
        $to = $validated['to'] ?? now()->toDateString();

        $transactions = Transaction::query()
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->orderByDesc('created_at')
            ->get();

        $earnings = (int) Transaction::query()
            ->where('status', 'paid')
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->sum('total_bill');

        $paidCount = $transactions->where('status', 'paid')->count();

        return Inertia::render('admin/transaction/history', [
            'transactions' => $transactions,
            'filters' => [
                'from' => $from,
                'to' => $to,
            ],
            'summary' => [
                'earnings' => $earnings,
                'paid_count' => $paidCount,
                'total_count' => $transactions->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        $menus = MenuModel::query()
            ->where('is_available', true)
            ->with(['addonGroups.options'])
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/transaction/create', [
            'menus' => $menus,
        ]);
    }

    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $transaction = $this->orderService->createAdminTransaction($request->validated());

        return redirect()
            ->route('admin.transaction.show', $transaction)
            ->with('success', 'Transaction created successfully.');
    }

    public function show(Transaction $transaction): Response
    {
        $transaction->load('items');

        $itemMenuIds = $transaction->items->pluck('menu_id');

        $menus = MenuModel::query()
            ->with(['addonGroups.options'])
            ->where(function ($query) use ($itemMenuIds) {
                $query->where('is_available', true);

                if ($itemMenuIds->isNotEmpty()) {
                    $query->orWhereIn('id', $itemMenuIds);
                }
            })
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/transaction/show', [
            'transaction' => $transaction,
            'itemGroups' => TransactionItemGrouper::groupByOrderedAt($transaction->items),
            'menus' => $menus,
        ]);
    }

    public function storeItem(StoreTransactionItemRequest $request, Transaction $transaction): RedirectResponse
    {
        $validated = $request->validated();

        $this->orderService->addMenuItem(
            $transaction,
            $validated['menu_id'],
            $validated['quantity'],
            $validated['addon_option_ids'] ?? [],
            $validated['note'] ?? null,
        );

        return redirect()
            ->route('admin.transaction.show', $transaction)
            ->with('success', 'Item added to transaction.');
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $this->orderService->updateHeader($transaction, $request->validated());

        return redirect()
            ->route('admin.transaction.show', $transaction)
            ->with('success', 'Transaction updated successfully.');
    }

    public function updateItem(
        UpdateTransactionItemRequest $request,
        Transaction $transaction,
        TransactionItem $item,
    ): RedirectResponse {
        if ($item->transaction_id !== $transaction->id) {
            abort(404);
        }

        $validated = $request->validated();

        $this->orderService->updateMenuItem(
            $item,
            $validated['quantity'],
            $validated['addon_option_ids'] ?? [],
            $validated['note'] ?? null,
        );

        return redirect()
            ->route('admin.transaction.show', $transaction)
            ->with('success', 'Item updated successfully.');
    }

    public function destroyItem(Transaction $transaction, TransactionItem $item): RedirectResponse
    {
        if ($item->transaction_id !== $transaction->id) {
            abort(404);
        }

        $this->orderService->deleteMenuItem($item);

        return redirect()
            ->route('admin.transaction.show', $transaction)
            ->with('success', 'Item removed from transaction.');
    }

    public function updateStatus(Transaction $transaction): RedirectResponse
    {
        if ($transaction->isPaid()) {
            return redirect()
                ->route('admin.transaction.show', $transaction)
                ->with('success', 'Transaction is already paid.');
        }

        $transaction->update(['status' => 'paid']);

        return redirect()
            ->route('admin.transaction.show', $transaction)
            ->with('success', 'Transaction marked as paid.');
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        if ($transaction->isPaid()) {
            return redirect()
                ->route('admin.transaction.show', $transaction)
                ->with('success', 'Paid transactions cannot be deleted.');
        }

        $transaction->delete();

        return redirect()
            ->route('admin.transaction.index')
            ->with('success', 'Transaction deleted successfully.');
    }
}
