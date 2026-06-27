import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import {
    destroy,
    history as transactionHistory,
    index as transactionsIndex,
    updateStatus,
} from '@/actions/App/Http/Controllers/TransactionController';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { TransactionOrderForm } from '@/components/admin/transaction-order-form';
import type { Menu } from '@/types/menu';
import type { Transaction, TransactionItem } from '@/types/transaction';
import { serviceTypeLabel } from '@/types/transaction';

type Props = {
    transaction: Transaction;
    menus: Menu[];
};

function formatPrice(price: number): string {
    return price.toLocaleString();
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

function statusLabel(status: Transaction['status']): string {
    return status === 'paid' ? 'Paid' : 'In progress';
}

function statusClassName(status: Transaction['status']): string {
    return status === 'paid'
        ? 'rounded-full bg-[#ecfdf3] px-2.5 py-1 text-xs font-medium text-[#027a48] dark:bg-[#053321] dark:text-[#75e0a7]'
        : 'rounded-full bg-[#fffaeb] px-2.5 py-1 text-xs font-medium text-[#b54708] dark:bg-[#4e1d09] dark:text-[#fec84b]';
}

function ItemAddons({ item }: { item: TransactionItem }) {
    if (item.addons === null || item.addons.length === 0) {
        return null;
    }

    return (
        <ul className="mt-2 space-y-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">
            {item.addons.map((addon) => (
                <li key={addon.menu_addon_option_id}>
                    {addon.group_name}: {addon.name}
                    {addon.price > 0 && (
                        <span className="tabular-nums">
                            {' '}
                            (+{formatPrice(addon.price)})
                        </span>
                    )}
                </li>
            ))}
        </ul>
    );
}

export default function AdminTransactionShow({ transaction, menus }: Props) {
    const { url } = usePage();
    const backHref = url.includes('from=history')
        ? transactionHistory.url()
        : transactionsIndex.url();

    const items = transaction.items ?? [];
    const isPaid = transaction.status === 'paid';
    const [markPaidOpen, setMarkPaidOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [markPaidLoading, setMarkPaidLoading] = useState(false);
    const [deleteLoading, setDeleteLoading] = useState(false);

    function handleConfirmMarkPaid() {
        setMarkPaidLoading(true);
        router.patch(
            updateStatus.url(transaction.id),
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    setMarkPaidLoading(false);
                    setMarkPaidOpen(false);
                },
            },
        );
    }

    function handleConfirmDelete() {
        setDeleteLoading(true);
        router.delete(destroy.url(transaction.id), {
            onFinish: () => {
                setDeleteLoading(false);
                setDeleteOpen(false);
            },
        });
    }

    return (
        <>
            <Head title={`${transaction.customer_name} · Transaction`} />

            <ConfirmDialog
                open={markPaidOpen}
                title="Mark as paid?"
                description={`Confirm payment of ${formatPrice(transaction.total_bill)} for ${transaction.customer_name}. This cannot be undone.`}
                confirmLabel="Mark paid"
                loading={markPaidLoading}
                onConfirm={handleConfirmMarkPaid}
                onCancel={() => setMarkPaidOpen(false)}
            />

            <ConfirmDialog
                open={deleteOpen}
                title="Delete transaction?"
                description={`Delete the in-progress transaction for ${transaction.customer_name}? All ordered items will be removed.`}
                confirmLabel="Delete"
                variant="danger"
                loading={deleteLoading}
                onConfirm={handleConfirmDelete}
                onCancel={() => setDeleteOpen(false)}
            />

            <header className="sticky top-0 z-10 border-b border-[#e3e3e0] bg-[#FDFDFC]/95 px-4 py-4 backdrop-blur dark:border-[#3E3E3A] dark:bg-[#0a0a0a]/95">
                <div className="mx-auto flex max-w-lg items-center gap-3">
                    <Link
                        href={backHref}
                        className="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Back
                    </Link>
                    <h1 className="truncate text-lg font-semibold">
                        {transaction.customer_name}
                    </h1>
                </div>
            </header>

            <div className="mx-auto flex h-[calc(100dvh-8.5rem)] max-w-lg flex-col px-4 py-4">
                <section className="mb-4 shrink-0 rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                Customer
                            </p>
                            <p className="font-medium">
                                {transaction.customer_name}
                            </p>
                            <p className="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                Phone
                            </p>
                            <p>{transaction.customer_phone}</p>
                            <p className="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                Order type
                            </p>
                            <p>{serviceTypeLabel(transaction.service_type)}</p>
                        </div>
                        <span className={statusClassName(transaction.status)}>
                            {statusLabel(transaction.status)}
                        </span>
                    </div>

                    <div className="mt-4 flex items-end justify-between border-t border-[#e3e3e0] pt-4 dark:border-[#3E3E3A]">
                        <div>
                            <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                Total bill
                            </p>
                            <p className="text-xl font-semibold tabular-nums">
                                {formatPrice(transaction.total_bill)}
                            </p>
                            <p className="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                {formatDate(transaction.created_at)}
                            </p>
                        </div>

                        {!isPaid && (
                            <button
                                type="button"
                                onClick={() => setMarkPaidOpen(true)}
                                className="rounded-md border border-[#e3e3e0] px-3 py-2 text-sm font-medium dark:border-[#3E3E3A]"
                            >
                                Mark paid
                            </button>
                        )}
                    </div>
                </section>

                <div className="min-h-0 flex-1 overflow-y-auto overscroll-contain pb-4">
                    <h2 className="mb-3 text-base font-semibold">Ordered items</h2>

                    {items.length === 0 ? (
                        <div className="mb-6 rounded-lg border border-dashed border-[#e3e3e0] p-6 text-center text-sm text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]">
                            No items ordered yet.
                        </div>
                    ) : (
                        <ul className="mb-6 space-y-3">
                            {items.map((item) => (
                                <li
                                    key={item.id}
                                    className="rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <p className="font-medium">
                                                {item.menu_name}
                                            </p>
                                            <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                                Qty {item.quantity} ·{' '}
                                                {formatPrice(item.unit_price)}{' '}
                                                each
                                            </p>
                                            <ItemAddons item={item} />
                                        </div>
                                        <p className="shrink-0 font-medium tabular-nums">
                                            {formatPrice(item.line_total)}
                                        </p>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}

                    {!isPaid && (
                        <section className="mb-3">
                            <h2 className="mb-3 text-base font-semibold">
                                Add menu for customer
                            </h2>
                            <div className="rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <TransactionOrderForm
                                    transactionId={transaction.id}
                                    menus={menus}
                                />
                            </div>
                        </section>
                    )}

                    {!isPaid && (
                        <section className="mt-8 border-t border-[#e3e3e0] pt-6 dark:border-[#3E3E3A]">
                            <h2 className="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">
                                Danger zone
                            </h2>
                            <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                Permanently remove this in-progress transaction
                                and all its items.
                            </p>
                            <button
                                type="button"
                                onClick={() => setDeleteOpen(true)}
                                className="mt-4 w-full rounded-md border border-[#fda29b] px-4 py-2.5 text-sm font-medium text-[#b42318] dark:border-[#912018]"
                            >
                                Delete transaction
                            </button>
                        </section>
                    )}
                </div>
            </div>
        </>
    );
}
