import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

import {
    index as reportsIndex,
    sales as salesReport,
} from '@/actions/App/Http/Controllers/ReportController';
import { show as showTransaction } from '@/actions/App/Http/Controllers/TransactionController';
import {
    inputClassName,
    labelClassName,
} from '@/components/admin/menu-form';
import { TableCodeBadge } from '@/components/admin/table-code-badge';
import { cn } from '@/lib/utils';
import type { Transaction } from '@/types/transaction';
import { serviceTypeLabel } from '@/types/transaction';
import type { SalesChannel } from '@/types/sales-channel';

type SalesGroup = {
    date: string;
    transactions: Transaction[];
};

type Props = {
    filters: {
        preset: 'today' | 'range';
        from: string;
        to: string;
        sales_channel_id: number | string;
    };
    summary: {
        revenue: number;
        total_count: number;
        paid_count: number;
        unpaid_count: number;
        unpaid_revenue: number;
    };
    groups: SalesGroup[];
    channels: SalesChannel[];
};

function formatPrice(price: number): string {
    return price.toLocaleString();
}

function formatDayLabel(date: string): string {
    return new Date(`${date}T00:00:00`).toLocaleDateString(undefined, {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

function formatTime(iso: string): string {
    return new Date(iso).toLocaleTimeString(undefined, {
        hour: '2-digit',
        minute: '2-digit',
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

const adminPillClassName =
    'shrink-0 rounded-full bg-[#eff8ff] px-2.5 py-1 text-xs font-medium text-[#175cd3] dark:bg-[#102a56] dark:text-[#84caff]';

export default function AdminReportsSales({
    filters,
    summary,
    groups,
    channels,
}: Props) {
    const [preset, setPreset] = useState<'today' | 'range'>(filters.preset);
    const [from, setFrom] = useState(filters.from);
    const [to, setTo] = useState(filters.to);
    const [channelId, setChannelId] = useState(String(filters.sales_channel_id));

    function applyToday() {
        setPreset('today');
        router.get(
            salesReport.url({ query: { preset: 'today', sales_channel_id: channelId } }),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function applyRange(event: React.FormEvent) {
        event.preventDefault();
        setPreset('range');
        router.get(
            salesReport.url({
                query: { preset: 'range', from, to, sales_channel_id: channelId },
            }),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="Laporan Penjualan" />
            <div className="flex h-[calc(100dvh-7.5rem)] flex-col px-4 py-4">
                <div className="mx-auto flex w-full max-w-lg shrink-0 flex-col">
                    <header className="mb-4">
                        <Link
                            href={reportsIndex.url()}
                            className="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            ← Laporan
                        </Link>
                        <h1 className="mt-2 text-2xl font-semibold">
                            Laporan Penjualan
                        </h1>
                        <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Ringkasan pendapatan dan transaksi.
                        </p>
                    </header>

                    <div className="mb-4">
                        <label htmlFor="sales-channel" className={labelClassName}>
                            Saluran
                        </label>
                        <select
                            id="sales-channel"
                            value={channelId}
                            onChange={(event) => {
                                const next = event.target.value;
                                setChannelId(next);
                                router.get(
                                    salesReport.url({
                                        query: {
                                            preset,
                                            from,
                                            to,
                                            sales_channel_id: next,
                                        },
                                    }),
                                    {},
                                    { preserveState: true, preserveScroll: true },
                                );
                            }}
                            className={inputClassName}
                        >
                            {channels.map((channel) => (
                                <option key={channel.id} value={channel.id}>
                                    {channel.name}
                                </option>
                            ))}
                            <option value="all">Semua</option>
                        </select>
                    </div>

                    <div className="mb-4 grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            onClick={applyToday}
                            className={cn(
                                'rounded-md border px-4 py-2.5 text-sm font-medium',
                                preset === 'today'
                                    ? 'border-[#1b1b18] bg-[#1b1b18] text-white dark:border-[#EDEDEC] dark:bg-[#EDEDEC] dark:text-[#1b1b18]'
                                    : 'border-[#e3e3e0] dark:border-[#3E3E3A]',
                            )}
                        >
                            Hari ini
                        </button>
                        <button
                            type="button"
                            onClick={() => setPreset('range')}
                            className={cn(
                                'rounded-md border px-4 py-2.5 text-sm font-medium',
                                preset === 'range'
                                    ? 'border-[#1b1b18] bg-[#1b1b18] text-white dark:border-[#EDEDEC] dark:bg-[#EDEDEC] dark:text-[#1b1b18]'
                                    : 'border-[#e3e3e0] dark:border-[#3E3E3A]',
                            )}
                        >
                            Rentang tanggal
                        </button>
                    </div>

                    {preset === 'range' && (
                        <form onSubmit={applyRange} className="mb-4 space-y-3">
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label
                                        htmlFor="from"
                                        className={labelClassName}
                                    >
                                        Dari
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
                                    <label
                                        htmlFor="to"
                                        className={labelClassName}
                                    >
                                        Sampai
                                    </label>
                                    <input
                                        id="to"
                                        type="date"
                                        value={to}
                                        onChange={(event) =>
                                            setTo(event.target.value)
                                        }
                                        className={inputClassName}
                                    />
                                </div>
                            </div>
                            <button
                                type="submit"
                                className="w-full rounded-md border border-[#e3e3e0] px-4 py-2.5 text-sm font-medium dark:border-[#3E3E3A]"
                            >
                                Terapkan filter
                            </button>
                        </form>
                    )}

                    <section className="mb-4 space-y-2">
                        <div className="grid grid-cols-2 gap-2">
                            <div className="rounded-lg border border-[#e3e3e0] bg-white p-3 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <p className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                    Pendapatan
                                </p>
                                <p className="mt-1 text-base font-semibold tabular-nums">
                                    {formatPrice(summary.revenue)}
                                </p>
                            </div>
                            <div className="rounded-lg border border-[#e3e3e0] bg-white p-3 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <p className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                    Pendapatan belum paid
                                </p>
                                <p className="mt-1 text-base font-semibold tabular-nums">
                                    {formatPrice(summary.unpaid_revenue)}
                                </p>
                            </div>
                        </div>
                        <div className="grid grid-cols-3 gap-2">
                            <div className="rounded-lg border border-[#e3e3e0] bg-white p-3 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <p className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                    Transaksi
                                </p>
                                <p className="mt-1 text-base font-semibold tabular-nums">
                                    {summary.total_count}
                                </p>
                            </div>
                            <div className="rounded-lg border border-[#e3e3e0] bg-white p-3 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <p className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                    Paid
                                </p>
                                <p className="mt-1 text-base font-semibold tabular-nums">
                                    {summary.paid_count}
                                </p>
                            </div>
                            <div className="rounded-lg border border-[#e3e3e0] bg-white p-3 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <p className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                    Belum paid
                                </p>
                                <p className="mt-1 text-base font-semibold tabular-nums">
                                    {summary.unpaid_count}
                                </p>
                            </div>
                        </div>
                    </section>
                </div>

                <div className="mx-auto min-h-0 w-full max-w-lg flex-1 overflow-y-auto overscroll-contain">
                    {groups.length === 0 ? (
                        <div className="rounded-lg border border-[#e3e3e0] bg-white p-10 text-center dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <p className="text-[#706f6c] dark:text-[#A1A09A]">
                                Tidak ada transaksi pada periode ini.
                            </p>
                        </div>
                    ) : (
                        <div className="space-y-6 pb-4">
                            {groups.map((group) => (
                                <section key={group.date}>
                                    <h2 className="mb-3 text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">
                                        {formatDayLabel(group.date)}
                                    </h2>
                                    <ul className="space-y-3">
                                        {group.transactions.map(
                                            (transaction) => (
                                                <li key={transaction.id}>
                                                    <Link
                                                        href={showTransaction.url(
                                                            transaction.id,
                                                            {
                                                                query: {
                                                                    from: 'reports',
                                                                },
                                                            },
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
                                                                    {
                                                                        transaction.customer_name
                                                                    }
                                                                </p>
                                                                <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                                                    {serviceTypeLabel(
                                                                        transaction.service_type,
                                                                    )}
                                                                    {' · '}
                                                                    {formatTime(
                                                                        transaction.created_at,
                                                                    )}
                                                                </p>
                                                            </div>
                                                            <div className="flex shrink-0 flex-col items-end gap-1.5">
                                                                <TableCodeBadge
                                                                    code={
                                                                        transaction.table_code
                                                                    }
                                                                />
                                                                {transaction.sales_channel?.type === 'event' && (
                                                                    <span className="shrink-0 rounded-full bg-[#fff7ed] px-2.5 py-1 text-xs font-medium text-[#c2410c] dark:bg-[#431407] dark:text-[#fdba74]">
                                                                        {transaction.sales_channel.name}
                                                                    </span>
                                                                )}
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
                                                                    {statusLabel(
                                                                        transaction.status,
                                                                    )}
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <p className="mt-3 text-sm font-medium tabular-nums">
                                                            {formatPrice(
                                                                transaction.total_bill,
                                                            )}
                                                        </p>
                                                    </Link>
                                                </li>
                                            ),
                                        )}
                                    </ul>
                                </section>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
