import { Head, Link, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import {
    create as createTransaction,
    index as transactionIndex,
    show as showTransaction,
} from '@/actions/App/Http/Controllers/TransactionController';
import { TableCodeBadge } from '@/components/admin/table-code-badge';
import { inputClassName } from '@/components/admin/menu-form';
import { cn } from '@/lib/utils';
import type { Transaction } from '@/types/transaction';
import { serviceTypeLabel, transactionStatusLabel } from '@/types/transaction';

type Props = {
    transactions: Transaction[];
    filters: {
        date: string;
        today: string;
    };
};

function formatPrice(price: number): string {
    return price.toLocaleString();
}

function shiftDate(date: string, days: number): string {
    const [year, month, day] = date.split('-').map(Number);
    const next = new Date(year, month - 1, day);
    next.setDate(next.getDate() + days);

    const y = next.getFullYear();
    const m = String(next.getMonth() + 1).padStart(2, '0');
    const d = String(next.getDate()).padStart(2, '0');

    return `${y}-${m}-${d}`;
}

function formatDayLabel(date: string, today: string): string {
    const [year, month, day] = date.split('-').map(Number);
    const value = new Date(year, month - 1, day);
    const formatted = value.toLocaleDateString(undefined, {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
    });

    if (date === today) {
        return `Today · ${formatted}`;
    }

    if (date === shiftDate(today, -1)) {
        return `Yesterday · ${formatted}`;
    }

    return formatted;
}

function formatTime(iso: string): string {
    return new Date(iso).toLocaleTimeString(undefined, {
        hour: '2-digit',
        minute: '2-digit',
    });
}

function statusClassName(status: Transaction['status']): string {
    return status === 'paid'
        ? 'shrink-0 rounded-full bg-[#ecfdf3] px-2.5 py-1 text-xs font-medium text-[#027a48] dark:bg-[#053321] dark:text-[#75e0a7]'
        : 'shrink-0 rounded-full bg-[#fffaeb] px-2.5 py-1 text-xs font-medium text-[#b54708] dark:bg-[#4e1d09] dark:text-[#fec84b]';
}

const adminPillClassName =
    'shrink-0 rounded-full bg-[#eff8ff] px-2.5 py-1 text-xs font-medium text-[#175cd3] dark:bg-[#102a56] dark:text-[#84caff]';

const stepButtonClassName =
    'flex size-11 shrink-0 items-center justify-center rounded-md border border-[#e3e3e0] bg-white text-[#1b1b18] disabled:cursor-not-allowed disabled:opacity-40 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]';

function DayChevron({ direction }: { direction: 'left' | 'right' }) {
    return (
        <svg
            viewBox="0 0 20 20"
            fill="none"
            aria-hidden="true"
            className="size-5"
        >
            <path
                d={direction === 'left' ? 'M12.5 4.5 7 10l5.5 5.5' : 'M7.5 4.5 13 10l-5.5 5.5'}
                stroke="currentColor"
                strokeWidth="1.75"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}

export default function AdminTransactionIndex({
    transactions,
    filters,
}: Props) {
    const [search, setSearch] = useState('');
    const isToday = filters.date === filters.today;

    const filteredTransactions = useMemo(() => {
        const query = search.trim().toLowerCase();

        if (query === '') {
            return transactions;
        }

        return transactions.filter(
            (transaction) =>
                transaction.customer_name.toLowerCase().includes(query) ||
                transaction.customer_phone.includes(query) ||
                (transaction.customer_phone_display ?? '')
                    .toLowerCase()
                    .includes(query) ||
                (transaction.customer_phone_local ?? '').includes(query) ||
                transaction.transaction_number.includes(query) ||
                `#${transaction.transaction_number}`.includes(query) ||
                (transaction.table_code ?? '').toLowerCase().includes(query),
        );
    }, [transactions, search]);

    const paidCount = transactions.filter((t) => t.status === 'paid').length;
    const isSearching = search.trim() !== '';

    function visitDate(date: string) {
        setSearch('');
        router.get(
            transactionIndex.url({ query: { date } }),
            {},
            { preserveScroll: false, replace: true },
        );
    }

    return (
        <>
            <Head title="Transactions" />
            <div className="flex h-[calc(100dvh-7.5rem)] flex-col px-4 py-4">
                <div className="mx-auto flex w-full max-w-lg shrink-0 flex-col">
                    <header className="mb-4">
                        <h1 className="text-2xl font-semibold">Transactions</h1>
                        <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            {isSearching
                                ? `${filteredTransactions.length} of ${transactions.length} transactions`
                                : `${paidCount} paid · ${transactions.length - paidCount} in progress`}
                        </p>
                    </header>

                    <div className="mb-4">
                        <div className="flex items-center gap-2">
                            <button
                                type="button"
                                aria-label="Previous day"
                                onClick={() =>
                                    visitDate(shiftDate(filters.date, -1))
                                }
                                className={stepButtonClassName}
                            >
                                <DayChevron direction="left" />
                            </button>

                            <div className="relative min-w-0 flex-1">
                                <input
                                    type="date"
                                    value={filters.date}
                                    max={filters.today}
                                    onChange={(event) => {
                                        if (event.target.value !== '') {
                                            visitDate(event.target.value);
                                        }
                                    }}
                                    aria-label="Choose date"
                                    className="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0"
                                />
                                <div className="flex h-11 items-center justify-center rounded-md border border-[#e3e3e0] bg-white px-3 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                    <span className="truncate text-sm font-medium">
                                        {formatDayLabel(
                                            filters.date,
                                            filters.today,
                                        )}
                                    </span>
                                </div>
                            </div>

                            <button
                                type="button"
                                aria-label="Next day"
                                disabled={filters.date >= filters.today}
                                onClick={() =>
                                    visitDate(shiftDate(filters.date, 1))
                                }
                                className={stepButtonClassName}
                            >
                                <DayChevron direction="right" />
                            </button>
                        </div>

                        {!isToday && (
                            <button
                                type="button"
                                onClick={() => visitDate(filters.today)}
                                className="mt-2 w-full text-center text-sm font-medium text-[#706f6c] underline-offset-2 hover:underline dark:text-[#A1A09A]"
                            >
                                Back to today
                            </button>
                        )}
                    </div>

                    <input
                        type="search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search by number, name, phone, or table..."
                        className={cn(inputClassName, 'mb-4')}
                    />

                    <Link
                        href={createTransaction.url()}
                        className="mb-4 flex w-full items-center justify-center rounded-md bg-[#1b1b18] px-4 py-3 text-sm font-medium text-white dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
                    >
                        New Transaction
                    </Link>
                </div>

                <div className="mx-auto min-h-0 w-full max-w-lg flex-1 overflow-y-auto overscroll-contain">
                    {transactions.length === 0 ? (
                        <div className="rounded-lg border border-[#e3e3e0] bg-white p-10 text-center dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <p className="text-[#706f6c] dark:text-[#A1A09A]">
                                No transactions on this date.
                            </p>
                        </div>
                    ) : filteredTransactions.length === 0 ? (
                        <div className="rounded-lg border border-[#e3e3e0] bg-white p-10 text-center dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <p className="text-[#706f6c] dark:text-[#A1A09A]">
                                No transactions match &quot;{search.trim()}
                                &quot;.
                            </p>
                        </div>
                    ) : (
                        <ul className="space-y-3 pb-4">
                            {filteredTransactions.map((transaction) => (
                                <li key={transaction.id}>
                                    <Link
                                        href={showTransaction.url(
                                            transaction.id,
                                        )}
                                        className="block rounded-lg border border-[#e3e3e0] bg-white p-4 active:bg-[#FDFDFC] dark:border-[#3E3E3A] dark:bg-[#161615] dark:active:bg-[#0a0a0a]"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate font-medium">
                                                    #
                                                    {
                                                        transaction.transaction_number
                                                    }
                                                    {' · '}
                                                    {transaction.customer_name}
                                                </p>
                                                <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                                    {transaction.customer_phone_display ??
                                                        transaction.customer_phone}
                                                    {' · '}
                                                    {serviceTypeLabel(
                                                        transaction.service_type,
                                                    )}
                                                </p>
                                            </div>
                                            <div className="flex shrink-0 flex-col items-end gap-1.5">
                                                <TableCodeBadge
                                                    code={
                                                        transaction.table_code
                                                    }
                                                />
                                                {transaction.is_admin_created && (
                                                    <span
                                                        className={
                                                            adminPillClassName
                                                        }
                                                    >
                                                        Admin
                                                    </span>
                                                )}
                                                <span
                                                    className={statusClassName(
                                                        transaction.status,
                                                    )}
                                                >
                                                    {transactionStatusLabel(
                                                        transaction.status,
                                                        transaction.paid_via,
                                                    )}
                                                </span>
                                            </div>
                                        </div>

                                        <div className="mt-3 flex items-baseline justify-between gap-3">
                                            <p className="text-xs tabular-nums text-[#706f6c] dark:text-[#A1A09A]">
                                                {formatTime(
                                                    transaction.created_at,
                                                )}
                                            </p>
                                            <p className="text-sm font-medium tabular-nums">
                                                Total{' '}
                                                {formatPrice(
                                                    transaction.total_bill,
                                                )}
                                            </p>
                                        </div>
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
