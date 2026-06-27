import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import { create as createTransaction, show as showTransaction } from '@/actions/App/Http/Controllers/TransactionController';
import { inputClassName } from '@/components/admin/menu-form';
import type { Transaction } from '@/types/transaction';
import { serviceTypeLabel } from '@/types/transaction';

type Props = {
    transactions: Transaction[];
};

function formatPrice(price: number): string {
    return price.toLocaleString();
}

function statusLabel(status: Transaction['status']): string {
    return status === 'paid' ? 'Paid' : 'In progress';
}

function statusClassName(status: Transaction['status']): string {
    return status === 'paid'
        ? 'shrink-0 rounded-full bg-[#ecfdf3] px-2.5 py-1 text-xs font-medium text-[#027a48] dark:bg-[#053321] dark:text-[#75e0a7]'
        : 'shrink-0 rounded-full bg-[#fffaeb] px-2.5 py-1 text-xs font-medium text-[#b54708] dark:bg-[#4e1d09] dark:text-[#fec84b]';
}

export default function AdminTransactionIndex({ transactions }: Props) {
    const [search, setSearch] = useState('');

    const filteredTransactions = useMemo(() => {
        const query = search.trim().toLowerCase();

        if (query === '') {
            return transactions;
        }

        return transactions.filter(
            (transaction) =>
                transaction.customer_name.toLowerCase().includes(query) ||
                transaction.customer_phone.includes(query),
        );
    }, [transactions, search]);

    const paidCount = transactions.filter((t) => t.status === 'paid').length;
    const isSearching = search.trim() !== '';

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

                    <input
                        type="search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search by name or phone..."
                        className={`${inputClassName} mb-4`}
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
                                No transactions yet.
                            </p>
                        </div>
                    ) : filteredTransactions.length === 0 ? (
                        <div className="rounded-lg border border-[#e3e3e0] bg-white p-10 text-center dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <p className="text-[#706f6c] dark:text-[#A1A09A]">
                                No transactions match &quot;{search.trim()}&quot;.
                            </p>
                        </div>
                    ) : (
                        <ul className="space-y-3 pb-4">
                            {filteredTransactions.map((transaction) => (
                                <li key={transaction.id}>
                                    <Link
                                        href={showTransaction.url(transaction.id)}
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
                                            </div>
                                            <span
                                                className={statusClassName(
                                                    transaction.status,
                                                )}
                                            >
                                                {statusLabel(transaction.status)}
                                            </span>
                                        </div>

                                        <p className="mt-3 tabular-nums text-sm font-medium">
                                            Total {formatPrice(transaction.total_bill)}
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
