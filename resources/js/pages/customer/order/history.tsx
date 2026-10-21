import { Head, Link } from '@inertiajs/react';

import {
    OrderItemGroups,
    formatPrice,
} from '@/components/menu/order-item-groups';
import { cn } from '@/lib/utils';
import { serviceTypeLabel, transactionStatusLabel } from '@/types/transaction';
import type {
    PaidVia,
    TransactionItemGroup,
    TransactionServiceType,
    TransactionStatus,
} from '@/types/transaction';

type HistoryTransaction = {
    id: number;
    transaction_number: string;
    status: TransactionStatus;
    paid_via: PaidVia | null;
    total_bill: number;
    service_type: TransactionServiceType;
    created_at: string | null;
    item_groups: TransactionItemGroup[];
};

type HistoryGroup = {
    date: string;
    label: string;
    transactions: HistoryTransaction[];
};

type Props = {
    sort: 'newest' | 'oldest';
    groups: HistoryGroup[];
};

function statusClassName(status: TransactionStatus): string {
    return status === 'paid'
        ? 'bg-[#ecfdf3] text-[#027a48] dark:bg-[#053321] dark:text-[#75e0a7]'
        : 'bg-[#fffaeb] text-[#b54708] dark:bg-[#4e1d09] dark:text-[#fdb022]';
}

function formatOrderedTime(iso: string | null): string | null {
    if (iso === null) {
        return null;
    }

    return new Date(iso).toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

const sortOptions = [
    { value: 'newest', label: 'Newest', href: '/customer/order/history' },
    {
        value: 'oldest',
        label: 'Oldest',
        href: '/customer/order/history?sort=oldest',
    },
] as const;

export default function CustomerOrderHistory({ sort, groups }: Props) {
    const hasTransactions = groups.some(
        (group) => group.transactions.length > 0,
    );

    return (
        <>
            <Head title="Order history" />

            <div className="mx-auto max-w-md px-4 py-4">
                <header className="mb-5">
                    <Link
                        href="/customer/order"
                        className="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        ← Today&apos;s orders
                    </Link>
                    <h1 className="mt-3 text-2xl font-semibold tracking-tight">
                        Order history
                    </h1>
                    <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        {sort === 'newest'
                            ? 'Grouped by date, starting with the nearest day.'
                            : 'Grouped by date, starting with the oldest day.'}
                    </p>
                </header>

                <div className="mb-5 flex rounded-full bg-white p-1 dark:bg-[#161615]">
                    {sortOptions.map((option) => {
                        const isActive = sort === option.value;

                        return (
                            <Link
                                key={option.value}
                                href={option.href}
                                preserveScroll
                                className={cn(
                                    'flex-1 rounded-full px-3 py-2 text-center text-sm font-medium',
                                    isActive
                                        ? 'bg-[#C56A3E] text-white'
                                        : 'text-[#706f6c] dark:text-[#A1A09A]',
                                )}
                                aria-current={isActive ? 'page' : undefined}
                            >
                                {option.label}
                            </Link>
                        );
                    })}
                </div>

                {hasTransactions ? (
                    <div className="space-y-6">
                        {groups.map((group) => (
                            <section key={group.date}>
                                <h2 className="mb-3 text-xs font-semibold tracking-wide text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    {group.label}
                                </h2>
                                <div className="space-y-3">
                                    {group.transactions.map((transaction) => {
                                        const orderedAt = formatOrderedTime(
                                            transaction.created_at,
                                        );

                                        return (
                                            <article
                                                key={transaction.id}
                                                className="overflow-hidden rounded-2xl border border-[#eee] bg-white shadow-[0_1px_2px_rgba(27,27,24,0.04)] dark:border-[#3E3E3A] dark:bg-[#161615] dark:shadow-none"
                                            >
                                                <div
                                                    className={`h-1 ${transaction.status === 'paid' ? 'bg-[#12b76a]' : 'bg-[#f79009]'}`}
                                                    aria-hidden="true"
                                                />
                                                <div className="p-4">
                                                    <div className="flex items-start justify-between gap-3">
                                                        <div className="min-w-0">
                                                            <p className="text-xs font-medium tracking-wide text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                                                {serviceTypeLabel(
                                                                    transaction.service_type,
                                                                )}
                                                                {orderedAt
                                                                    ? ` · ${orderedAt}`
                                                                    : ''}
                                                            </p>
                                                            <p className="mt-1 text-lg font-semibold tracking-tight tabular-nums">
                                                                #
                                                                {
                                                                    transaction.transaction_number
                                                                }
                                                            </p>
                                                        </div>
                                                        <span
                                                            className={`inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-medium ${statusClassName(transaction.status)}`}
                                                        >
                                                            {transactionStatusLabel(
                                                                transaction.status,
                                                                transaction.paid_via,
                                                            )}
                                                        </span>
                                                    </div>

                                                    <div className="mt-4 border-t border-dashed border-[#e3e3e0] pt-4 dark:border-[#3E3E3A]">
                                                        {transaction
                                                            .item_groups
                                                            .length > 0 ? (
                                                            <OrderItemGroups
                                                                groups={
                                                                    transaction.item_groups
                                                                }
                                                                compact
                                                            />
                                                        ) : (
                                                            <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                                                No items on
                                                                this bill.
                                                            </p>
                                                        )}
                                                    </div>

                                                    <div className="mt-4 flex items-center justify-between border-t border-[#e3e3e0] pt-3 dark:border-[#3E3E3A]">
                                                        <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                                            Bill total
                                                        </p>
                                                        <p className="text-lg font-semibold text-[#C56A3E] tabular-nums">
                                                            Rp{' '}
                                                            {formatPrice(
                                                                transaction.total_bill,
                                                            )}
                                                        </p>
                                                    </div>
                                                </div>
                                            </article>
                                        );
                                    })}
                                </div>
                            </section>
                        ))}
                    </div>
                ) : (
                    <div className="rounded-2xl border border-dashed border-[#e3e3e0] bg-white px-6 py-12 text-center dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <p className="text-base font-semibold">
                            No orders yet
                        </p>
                        <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Bills from this phone number will show up here.
                        </p>
                        <Link
                            href="/customer/menu"
                            className="mt-5 inline-flex items-center justify-center rounded-full bg-[#C56A3E] px-4 py-2.5 text-sm font-medium text-white"
                        >
                            Browse menu
                        </Link>
                    </div>
                )}
            </div>
        </>
    );
}
