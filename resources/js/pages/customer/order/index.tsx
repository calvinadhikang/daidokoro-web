import { Head, Link } from '@inertiajs/react';

import { OrderItemGroups, formatPrice } from '@/components/menu/order-item-groups';
import {
    serviceTypeLabel,
    type TransactionItemGroup,
    type TransactionServiceType,
    type TransactionStatus,
} from '@/types/transaction';

type CustomerOrderTransaction = {
    id: number;
    transaction_number: string;
    status: TransactionStatus;
    total_bill: number;
    service_type: TransactionServiceType;
    created_at: string | null;
    item_groups: TransactionItemGroup[];
};

type Props = {
    serviceType: TransactionServiceType | null;
    transactions: CustomerOrderTransaction[];
};

function statusLabel(status: TransactionStatus): string {
    return status === 'paid' ? 'Paid' : 'In progress';
}

function statusClassName(status: TransactionStatus): string {
    return status === 'paid'
        ? 'bg-[#ecfdf3] text-[#027a48] dark:bg-[#053321] dark:text-[#75e0a7]'
        : 'bg-[#fffaeb] text-[#b54708] dark:bg-[#4e1d09] dark:text-[#fdb022]';
}

export default function CustomerOrderIndex({
    serviceType,
    transactions,
}: Props) {
    const hasOrders = transactions.some(
        (transaction) => transaction.item_groups.length > 0,
    );

    return (
        <>
            <Head title="Your order" />

            <div className="mx-auto max-w-md px-4 py-4">
                <header className="mb-4">
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <h1 className="text-2xl font-semibold">
                                Your orders
                            </h1>
                            <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                Bills sent to the kitchen today.
                            </p>
                        </div>
                        {serviceType !== null && (
                            <span className="shrink-0 rounded-full bg-[#eff8ff] px-2.5 py-1 text-xs font-medium text-[#175cd3] dark:bg-[#102a56] dark:text-[#84caff]">
                                {serviceTypeLabel(serviceType)}
                            </span>
                        )}
                    </div>
                </header>

                {hasOrders ? (
                    <div className="space-y-4">
                        {transactions.map((transaction) => (
                            <section
                                key={transaction.id}
                                className="rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]"
                            >
                                <div className="mb-4 flex items-start justify-between gap-3">
                                    <div>
                                        <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                            Transaction
                                        </p>
                                        <p className="text-lg font-semibold tabular-nums">
                                            #{transaction.transaction_number}
                                        </p>
                                        <p className="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                            {serviceTypeLabel(
                                                transaction.service_type,
                                            )}
                                        </p>
                                    </div>
                                    <div className="text-right">
                                        <span
                                            className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${statusClassName(transaction.status)}`}
                                        >
                                            {statusLabel(transaction.status)}
                                        </span>
                                        <p className="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                            Bill total
                                        </p>
                                        <p className="text-xl font-semibold tabular-nums">
                                            {formatPrice(transaction.total_bill)}
                                        </p>
                                    </div>
                                </div>

                                {transaction.item_groups.length > 0 ? (
                                    <OrderItemGroups
                                        groups={transaction.item_groups}
                                        compact
                                    />
                                ) : (
                                    <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                        No items on this bill.
                                    </p>
                                )}
                            </section>
                        ))}
                    </div>
                ) : (
                    <div className="rounded-lg border border-dashed border-[#e3e3e0] p-8 text-center dark:border-[#3E3E3A]">
                        <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            No orders today yet.
                        </p>
                        <Link
                            href="/customer/menu"
                            className="mt-4 inline-block text-sm font-medium text-[#175cd3] dark:text-[#84caff]"
                        >
                            Browse menu
                        </Link>
                    </div>
                )}
            </div>
        </>
    );
}
