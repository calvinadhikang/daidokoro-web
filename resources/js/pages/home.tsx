import { Head, Link } from '@inertiajs/react';

import { cn } from '@/lib/utils';
import type { NextSession, StoreStatus } from '@/types/operating-hours';

type Props = {
    storeStatus: StoreStatus;
    nextSession: NextSession | null;
};

function OrderButton({
    label,
    href,
    disabled,
}: {
    label: string;
    href: string;
    disabled: boolean;
}) {
    if (disabled) {
        return (
            <button
                type="button"
                disabled
                className={cn(
                    'flex-1 rounded-md px-4 py-3 text-sm font-medium transition-colors',
                    'cursor-not-allowed border border-[#e3e3e0] bg-[#FDFDFC] text-[#706f6c] opacity-60 dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#A1A09A]',
                )}
            >
                {label}
            </button>
        );
    }

    return (
        <Link
            href={href}
            className={cn(
                'flex flex-1 items-center justify-center rounded-md px-4 py-3 text-sm font-medium transition-colors',
                'border border-[#1b1b18] bg-[#1b1b18] text-white active:bg-[#333] dark:border-[#EDEDEC] dark:bg-[#EDEDEC] dark:text-[#1b1b18] dark:active:bg-[#d4d4d2]',
            )}
        >
            {label}
        </Link>
    );
}

export default function Home({ storeStatus, nextSession }: Props) {
    const sessionLabel =
        nextSession === null
            ? storeStatus.is_open
                ? null
                : storeStatus.reason === 'outside_hours'
                  ? 'No more sessions today'
                  : storeStatus.message
            : nextSession.context === 'current'
              ? `Session ${nextSession.session_number} · ${nextSession.time_range_formatted}`
              : `Next session · ${nextSession.time_range_formatted}`;

    return (
        <>
            <Head title="Order" />

            <div className="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <main className="mx-auto flex w-full max-w-md flex-1 flex-col px-4 py-8">
                    <header className="mb-8">
                        <h1 className="text-2xl font-semibold">Daidokoro</h1>
                        <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Order takeaway or dine in
                        </p>
                    </header>

                    <section
                        className={cn(
                            'mb-6 rounded-lg border p-5',
                            storeStatus.is_open
                                ? 'border-[#abefc6] bg-[#ecfdf3] dark:border-[#085d3a] dark:bg-[#053321]'
                                : 'border-[#fecdca] bg-[#fef3f2] dark:border-[#912018] dark:bg-[#55160c]',
                        )}
                    >
                        <div className="flex items-center gap-3">
                            <span
                                className={cn(
                                    'inline-flex h-2.5 w-2.5 shrink-0 rounded-full',
                                    storeStatus.is_open
                                        ? 'bg-[#12b76a]'
                                        : 'bg-[#f04438]',
                                )}
                                aria-hidden
                            />
                            <p
                                className={cn(
                                    'text-lg font-semibold',
                                    storeStatus.is_open
                                        ? 'text-[#027a48] dark:text-[#75e0a7]'
                                        : 'text-[#b42318] dark:text-[#fda29b]',
                                )}
                            >
                                {storeStatus.is_open ? 'Open now' : 'Closed'}
                            </p>
                        </div>

                        {!storeStatus.is_open && (
                            <p
                                className={cn(
                                    'mt-2 text-sm',
                                    'text-[#b42318]/80 dark:text-[#fda29b]/80',
                                )}
                            >
                                {storeStatus.message}
                            </p>
                        )}

                        {sessionLabel !== null && (
                            <p className="mt-3 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                {sessionLabel}
                            </p>
                        )}

                        <p className="mt-4 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                            {storeStatus.checked_at_formatted}
                        </p>
                    </section>

                    <section className="mb-6">
                        <Link
                            href="/menu"
                            className="flex w-full items-center justify-center rounded-md border border-[#e3e3e0] px-4 py-3 text-sm font-medium dark:border-[#3E3E3A]"
                        >
                            View menu & prices
                        </Link>
                    </section>

                    <section className="mt-auto">
                        <p className="mb-3 text-sm font-medium">Start an order</p>
                        <div className="flex gap-3">
                            <OrderButton
                                label="Takeaway"
                                href="/customer/login?service_type=takeaway"
                                disabled={!storeStatus.is_open}
                            />
                            <OrderButton
                                label="Dine in"
                                href="/customer/login?service_type=dine_in"
                                disabled={!storeStatus.is_open}
                            />
                        </div>
                        {!storeStatus.is_open && (
                            <p className="mt-3 text-center text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                Ordering is available during operating hours only.
                            </p>
                        )}
                    </section>
                </main>
            </div>
        </>
    );
}
