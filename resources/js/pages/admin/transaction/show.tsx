import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import {
    destroy,
    history as transactionHistory,
    index as transactionsIndex,
    updateStatus,
} from '@/actions/App/Http/Controllers/TransactionController';
import { sales as salesReport } from '@/actions/App/Http/Controllers/ReportController';
import {
    OrderItemGroups,
    formatPrice,
} from '@/components/menu/order-item-groups';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { TableCodeBadge } from '@/components/admin/table-code-badge';
import { TransactionOrderForm } from '@/components/admin/transaction-order-form';
import type { Menu } from '@/types/menu';
import type {
    Transaction,
    TransactionItemGroup,
} from '@/types/transaction';
import { serviceTypeLabel } from '@/types/transaction';

type Props = {
    transaction: Transaction;
    itemGroups: TransactionItemGroup[];
    menus: Menu[];
};

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

const adminPillClassName =
    'rounded-full bg-[#eff8ff] px-2.5 py-1 text-xs font-medium text-[#175cd3] dark:bg-[#102a56] dark:text-[#84caff]';

export default function AdminTransactionShow({
    transaction,
    itemGroups,
    menus,
}: Props) {
    const { url } = usePage();
    const backHref = url.includes('from=history')
        ? transactionHistory.url()
        : url.includes('from=reports')
          ? salesReport.url()
          : transactionsIndex.url();

    const isPaid = transaction.status === 'paid';
    const itemCount =
        transaction.items?.length ??
        itemGroups.reduce((sum, group) => sum + group.items.length, 0);
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
            <Head title={`#${transaction.transaction_number} · ${transaction.customer_name}`} />

            <ConfirmDialog
                open={markPaidOpen}
                title="Mark as paid?"
                description={`Confirm payment of ${formatPrice(transaction.total_bill)} for #${transaction.transaction_number} · ${transaction.customer_name}. This cannot be undone.`}
                confirmLabel="Mark paid"
                loading={markPaidLoading}
                onConfirm={handleConfirmMarkPaid}
                onCancel={() => setMarkPaidOpen(false)}
            />

            <ConfirmDialog
                open={deleteOpen}
                title="Delete transaction?"
                description={`Delete the in-progress transaction #${transaction.transaction_number} for ${transaction.customer_name}? All ordered items will be removed.`}
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
                        #{transaction.transaction_number}
                        {' · '}
                        {transaction.customer_name}
                        {transaction.table_code
                            ? ` · Meja ${transaction.table_code}`
                            : ''}
                    </h1>
                </div>
            </header>

            <div className="mx-auto flex h-[calc(100dvh-8.5rem)] max-w-lg flex-col px-4 py-4">
                <section className="mb-3 shrink-0 rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                Transaction number
                            </p>
                            <p className="font-medium tabular-nums">
                                #{transaction.transaction_number}
                            </p>
                            <p className="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
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
                            {transaction.table_code && (
                                <>
                                    <p className="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                        Meja
                                    </p>
                                    <p className="font-medium tabular-nums">
                                        {transaction.table_code}
                                    </p>
                                </>
                            )}
                        </div>
                        <div className="flex flex-col items-end gap-1.5">
                            <TableCodeBadge code={transaction.table_code} />
                            {transaction.is_admin_created && (
                                <span className={adminPillClassName}>Admin</span>
                            )}
                            <span className={statusClassName(transaction.status)}>
                                {statusLabel(transaction.status)}
                            </span>
                        </div>
                    </div>

                    <div className="mt-4 grid grid-cols-2 gap-3 border-t border-[#e3e3e0] pt-4 dark:border-[#3E3E3A]">
                        <div>
                            <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                Total items
                            </p>
                            <p className="text-xl font-semibold tabular-nums">
                                {itemCount}
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                Total bill
                            </p>
                            <p className="text-xl font-semibold tabular-nums">
                                {formatPrice(transaction.total_bill)}
                            </p>
                        </div>
                    </div>
                    <p className="mt-2 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                        {formatDate(transaction.created_at)}
                    </p>
                </section>

                {!isPaid && (
                    <button
                        type="button"
                        onClick={() => setMarkPaidOpen(true)}
                        className="mb-4 w-full shrink-0 rounded-md border border-[#f5c518] bg-[#f5c518] px-4 py-3 text-sm font-semibold text-[#1b1b18] dark:border-[#f5c518] dark:bg-[#f5c518] dark:text-[#1b1b18]"
                    >
                        Mark as paid
                    </button>
                )}

                <div className="min-h-0 flex-1 overflow-y-auto overscroll-contain pb-4">
                    <h2 className="mb-3 text-base font-semibold">Ordered items</h2>

                    <div className="mb-6">
                        <OrderItemGroups groups={itemGroups} />
                    </div>

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
