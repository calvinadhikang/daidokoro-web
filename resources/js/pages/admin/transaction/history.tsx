import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

import {
    history as transactionHistory,
    show as showTransaction,
} from '@/actions/App/Http/Controllers/TransactionController';
import {
    inputClassName,
    labelClassName,
} from '@/components/admin/menu-form';
import type { Transaction } from '@/types/transaction';
import { serviceTypeLabel } from '@/types/transaction';

type Props = {
    transactions: Transaction[];
    filters: {
        from: string;
        to: string;
    };
    summary: {
        earnings: number;
        paid_count: number;
        total_count: number;
    };
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
        ? 'shrink-0 rounded-full bg-[#ecfdf3] px-2.5 py-1 text-xs font-medium text-[#027a48] dark:bg-[#053321] dark:text-[#75e0a7]'
        : 'shrink-0 rounded-full bg-[#fffaeb] px-2.5 py-1 text-xs font-medium text-[#b54708] dark:bg-[#4e1d09] dark:text-[#fec84b]';
}

export default function AdminTransactionHistory({
    transactions,
    filters,
    summary,
}: Props) {
    const [from, setFrom] = useState(filters.from);
    const [to, setTo] = useState(filters.to);

    function applyFilters(event: React.FormEvent) {
        event.preventDefault();

        router.get(
            transactionHistory.url({ query: { from, to } }),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="Transaction History" />
            <div className="flex h-[calc(100dvh-7.5rem)] flex-col px-4 py-4">
                <div className="mx-auto flex w-full max-w-lg shrink-0 flex-col">
                    <header className="mb-4">
                        <h1 className="text-2xl font-semibold">History</h1>
                        <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            {summary.total_count} transaction
                            {summary.total_count === 1 ? '' : 's'} in range
                        </p>
                    </header>

                    <form onSubmit={applyFilters} className="mb-4 space-y-3">
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label htmlFor="from" className={labelClassName}>
                                    From
                                </label>
                                <input
                                    id="from"
                                    type="date"
                                    value={from}
                                    onChange={(event) =>
                                        setFrom(event.target.value)
                                    }
                                    className={inputClassName}
                                />
                            </div>
                            <div>
                                <label htmlFor="to" className={labelClassName}>
                                    To
                                </label>
                                <input
                                    id="to"
                                    type="date"
                                    value={to}
                                    onChange={(event) => setTo(event.target.value)}
                                    className={inputClassName}
                                />
                            </div>
                        </div>
                        <button
                            type="submit"
                            className="w-full rounded-md border border-[#e3e3e0] px-4 py-2.5 text-sm font-medium dark:border-[#3E3E3A]"
                        >
                            Apply filter
                        </button>
                    </form>

                    <section className="mb-4 rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Earnings (paid)
                        </p>
                        <p className="mt-1 text-2xl font-semibold tabular-nums">
                            {formatPrice(summary.earnings)}
                        </p>
                        <p className="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                            {summary.paid_count} paid transaction
                            {summary.paid_count === 1 ? '' : 's'}
                        </p>
                    </section>
                </div>

                <div className="mx-auto min-h-0 w-full max-w-lg flex-1 overflow-y-auto overscroll-contain">
                    {transactions.length === 0 ? (
                        <div className="rounded-lg border border-[#e3e3e0] bg-white p-10 text-center dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <p className="text-[#706f6c] dark:text-[#A1A09A]">
                                No transactions in this date range.
                            </p>
                        </div>
                    ) : (
                        <ul className="space-y-3 pb-4">
                            {transactions.map((transaction) => (
                                <li key={transaction.id}>
                                    <Link
                                        href={showTransaction.url(
                                            transaction.id,
                                            { query: { from: 'history' } },
                                        )}
                                        className="block rounded-lg border border-[#e3e3e0] bg-white p-4 active:bg-[#FDFDFC] dark:border-[#3E3E3A] dark:bg-[#161615] dark:active:bg-[#0a0a0a]"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate font-medium">
                                                    {transaction.customer_name}
                                                </p>
                                                <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                                    {transaction.customer_phone}
                                                    {' · '}
                                                    {serviceTypeLabel(
                                                        transaction.service_type,
                                                    )}
                                                </p>
                                                <p className="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                                    {formatDate(
                                                        transaction.created_at,
                                                    )}
                                                </p>
                                            </div>
                                            <span
                                                className={statusClassName(
                                                    transaction.status,
                                                )}
                                            >
                                                {statusLabel(
                                                    transaction.status,
                                                )}
                                            </span>
                                        </div>

                                        <p className="mt-3 tabular-nums text-sm font-medium">
                                            Total{' '}
                                            {formatPrice(transaction.total_bill)}
                                        </p>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </>
    );
}
