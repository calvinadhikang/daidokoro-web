import { Head, Link } from '@inertiajs/react';

import { cn } from '@/lib/utils';
import type { StoreStatus } from '@/types/operating-hours';

type Props = {
    tableCode: string;
    storeStatus: StoreStatus;
};

function ServiceButton({
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

export default function TableEntry({ tableCode, storeStatus }: Props) {
    return (
        <>
            <Head title={`Table ${tableCode}`} />

            <div className="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <main className="mx-auto flex w-full max-w-md flex-1 flex-col px-4 py-8">
                    <header className="mb-8">
                        <Link
                            href="/"
                            className="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            ← Home
                        </Link>
                        <h1 className="mt-4 text-2xl font-semibold">
                            Table {tableCode}
                        </h1>
                        <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            How would you like to order?
                        </p>
                    </header>

                    <section className="mb-6 rounded-md border border-[#e3e3e0] bg-white px-4 py-3 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]">
                        You scanned table{' '}
                        <span className="font-semibold tabular-nums">
                            {tableCode}
                        </span>
                        .
                    </section>

                    <section className="mt-auto">
                        <p className="mb-3 text-sm font-medium">
                            Choose service type
                        </p>
                        <div className="flex gap-3">
                            <ServiceButton
                                label="Dine in"
                                href={`/t/${tableCode}/dine_in`}
                                disabled={!storeStatus.is_open}
                            />
                            <ServiceButton
                                label="Dine out"
                                href={`/t/${tableCode}/takeaway`}
                                disabled={!storeStatus.is_open}
                            />
                        </div>
                        {!storeStatus.is_open && (
                            <p className="mt-3 text-center text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                {storeStatus.message}
                            </p>
                        )}
                    </section>
                </main>
            </div>
        </>
    );
}
