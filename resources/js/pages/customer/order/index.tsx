import { Head, Link } from '@inertiajs/react';

import { OrderItemGroups, formatPrice } from '@/components/menu/order-item-groups';
import {
    serviceTypeLabel,
    type Transaction,
    type TransactionItemGroup,
    type TransactionServiceType,
} from '@/types/transaction';

type Props = {
    serviceType: TransactionServiceType | null;
    transaction: Transaction | null;
    itemGroups: TransactionItemGroup[];
};

export default function CustomerOrderIndex({
    serviceType,
    transaction,
    itemGroups,
}: Props) {
    const hasOrder = transaction !== null && itemGroups.length > 0;

    return (
        <>
            <Head title="Your order" />

            <div className="mx-auto max-w-md px-4 py-4">
                <header className="mb-4">
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <h1 className="text-2xl font-semibold">
                                Your order
                            </h1>
                            <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                Items already sent to the kitchen.
                            </p>
                        </div>
                        {serviceType !== null && (
                            <span className="shrink-0 rounded-full bg-[#eff8ff] px-2.5 py-1 text-xs font-medium text-[#175cd3] dark:bg-[#102a56] dark:text-[#84caff]">
                                {serviceTypeLabel(serviceType)}
                            </span>
                        )}
                    </div>
                </header>

                {hasOrder && transaction !== null ? (
                    <section className="rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <div className="mb-4 flex items-end justify-between gap-3">
                            <div>
                                <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                    Transaction
                                </p>
                                <p className="text-lg font-semibold tabular-nums">
                                    #{transaction.transaction_number}
                                </p>
                            </div>
                            <div className="text-right">
                                <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                    Current bill
                                </p>
                                <p className="text-xl font-semibold tabular-nums">
                                    {formatPrice(transaction.total_bill)}
                                </p>
                            </div>
                        </div>

                        <OrderItemGroups groups={itemGroups} compact />
                    </section>
                ) : (
                    <div className="rounded-lg border border-dashed border-[#e3e3e0] p-8 text-center dark:border-[#3E3E3A]">
                        <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            No items sent yet.
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
