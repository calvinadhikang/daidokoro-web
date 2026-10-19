import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import { QrisPaymentSection } from '@/components/customer/qris-payment-section';
import {
    OrderItemGroups,
    formatPrice,
} from '@/components/menu/order-item-groups';
import {
    serviceTypeLabel,
    transactionStatusLabel,
    type PaidVia,
    type TransactionItemGroup,
    type TransactionServiceType,
    type TransactionStatus,
} from '@/types/transaction';

type CustomerQris = {
    qr_content: string;
    amount: number;
    expires_at: string;
};

type CustomerOrderTransaction = {
    id: number;
    transaction_number: string;
    status: TransactionStatus;
    paid_via: PaidVia | null;
    total_bill: number;
    service_type: TransactionServiceType;
    created_at: string | null;
    item_groups: TransactionItemGroup[];
    qris: CustomerQris | null;
};

type Props = {
    serviceType: TransactionServiceType | null;
    transactions: CustomerOrderTransaction[];
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

export default function CustomerOrderIndex({
    serviceType,
    transactions,
}: Props) {
    const { errors } = usePage().props as { errors?: Record<string, string> };
    const [payingId, setPayingId] = useState<number | null>(null);
    const hasOrders = transactions.some(
        (transaction) => transaction.item_groups.length > 0,
    );
    const waitingForPayment = transactions.some(
        (transaction) =>
            transaction.status === 'in_progress' && transaction.qris !== null,
    );

    useEffect(() => {
        if (!waitingForPayment) {
            return;
        }

        const timer = window.setInterval(() => {
            router.visit(window.location.href, {
                only: ['transactions'],
                preserveScroll: true,
                preserveState: true,
                preserveUrl: true,
            });
        }, 5000);

        return () => window.clearInterval(timer);
    }, [waitingForPayment]);

    function pay(transactionId: number) {
        setPayingId(transactionId);
        router.post(
            `/customer/order/${transactionId}/qris`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setPayingId(null),
            },
        );
    }

    return (
        <>
            <Head title="Your order" />

            <div className="mx-auto max-w-md px-4 py-4">
                {errors?.qris && (
                    <div className="mb-4 rounded-lg border border-[#fecdca] bg-[#fef3f2] px-4 py-3 text-sm text-[#b42318] dark:border-[#55160c] dark:bg-[#55160c] dark:text-[#fda29b]">
                        {errors.qris}
                    </div>
                )}
                <header className="mb-5">
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <h1 className="text-2xl font-semibold tracking-tight">
                                Your orders
                            </h1>
                            <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                Bills sent to the kitchen today.
                            </p>
                        </div>
                        {serviceType !== null && (
                            <span className="shrink-0 rounded-full bg-[#F6EBE2] px-2.5 py-1 text-xs font-medium text-[#A4532E] dark:bg-[#3A2A22] dark:text-[#F3D5C2]">
                                {serviceTypeLabel(serviceType)}
                            </span>
                        )}
                    </div>
                </header>

                {hasOrders ? (
                    <div className="space-y-4">
                        {transactions.map((transaction) => {
                            const orderedAt = formatOrderedTime(
                                transaction.created_at,
                            );

                            return (
                                <section
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
                                            {transaction.item_groups.length >
                                            0 ? (
                                                <OrderItemGroups
                                                    groups={
                                                        transaction.item_groups
                                                    }
                                                    compact
                                                />
                                            ) : (
                                                <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                                    No items on this bill.
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

                                        {transaction.status !== 'paid' &&
                                            transaction.total_bill > 0 &&
                                            (transaction.qris !== null ? (
                                                <QrisPaymentSection
                                                    qrContent={
                                                        transaction.qris
                                                            .qr_content
                                                    }
                                                    amount={
                                                        transaction.qris.amount
                                                    }
                                                    expiresAt={
                                                        transaction.qris
                                                            .expires_at
                                                    }
                                                    filename={`qris-${transaction.transaction_number}.png`}
                                                    regenerating={
                                                        payingId ===
                                                        transaction.id
                                                    }
                                                    onRegenerate={() =>
                                                        pay(transaction.id)
                                                    }
                                                />
                                            ) : (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        pay(transaction.id)
                                                    }
                                                    disabled={
                                                        payingId ===
                                                        transaction.id
                                                    }
                                                    className="mt-4 w-full rounded-full bg-[#C56A3E] px-4 py-2.5 text-sm font-medium text-white disabled:opacity-50"
                                                >
                                                    {payingId === transaction.id
                                                        ? 'Creating QR…'
                                                        : 'Pay with QRIS'}
                                                </button>
                                            ))}
                                    </div>
                                </section>
                            );
                        })}
                    </div>
                ) : (
                    <div className="rounded-2xl border border-dashed border-[#e3e3e0] bg-white px-6 py-12 text-center dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#F6EBE2] text-[#C56A3E] dark:bg-[#3A2A22] dark:text-[#F3D5C2]">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="1.75"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                className="size-6"
                                aria-hidden="true"
                            >
                                <path d="M6 7h12l-1 12H7L6 7z" />
                                <path d="M9 7V5a3 3 0 0 1 6 0v2" />
                            </svg>
                        </div>
                        <p className="mt-4 text-base font-semibold">
                            No orders today yet
                        </p>
                        <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Dishes you send to the kitchen will show up here.
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
