<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionItemRequest;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\MenuModel;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Services\MenuOrderLineBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function __construct(private MenuOrderLineBuilder $lineBuilder) {}

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
        $validated = $request->validated();

        $transaction = DB::transaction(function () use ($validated) {
            $transaction = Transaction::query()->create([
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'service_type' => $validated['service_type'] ?? 'dine_in',
                'status' => 'in_progress',
                'total_bill' => 0,
            ]);

            foreach ($validated['items'] ?? [] as $itemData) {
                $this->addItemToTransaction(
                    $transaction,
                    $itemData['menu_id'],
                    $itemData['quantity'],
                    $itemData['addon_option_ids'] ?? [],
                );
            }

            return $transaction;
        });

        return redirect()
            ->route('admin.transaction.show', $transaction)
            ->with('success', 'Transaction created successfully.');
    }

    public function show(Transaction $transaction): Response
    {
        $transaction->load('items');

        $menus = MenuModel::query()
            ->where('is_available', true)
            ->with(['addonGroups.options'])
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/transaction/show', [
            'transaction' => $transaction,
            'menus' => $menus,
        ]);
    }

    public function storeItem(StoreTransactionItemRequest $request, Transaction $transaction): RedirectResponse
    {
        if ($transaction->isPaid()) {
            throw ValidationException::withMessages([
                'menu_id' => 'Cannot add items to a paid transaction.',
            ]);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($transaction, $validated) {
            $this->addItemToTransaction(
                $transaction,
                $validated['menu_id'],
                $validated['quantity'],
                $validated['addon_option_ids'] ?? [],
            );
        });

        return redirect()
            ->route('admin.transaction.show', $transaction)
            ->with('success', 'Item added to transaction.');
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

    /**
     * @param  array<int, int>  $addonOptionIds
     */
    private function addItemToTransaction(
        Transaction $transaction,
        int $menuId,
        int $quantity,
        array $addonOptionIds,
    ): void {
        $menu = MenuModel::query()
            ->where('is_available', true)
            ->with(['addonGroups.options'])
            ->findOrFail($menuId);

        $lineItem = $this->lineBuilder->build($menu, $quantity, $addonOptionIds);

        TransactionItem::query()->create([
            'transaction_id' => $transaction->id,
            'menu_id' => $lineItem['menu_id'],
            'menu_name' => $lineItem['menu_name'],
            'quantity' => $lineItem['quantity'],
            'unit_price' => $lineItem['unit_price'],
            'line_total' => $lineItem['line_total'],
            'addons' => $lineItem['addons'],
        ]);

        $transaction->recalculateTotal();
    }
}
