import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

import {
    index as reportsIndex,
    menus as menusReport,
} from '@/actions/App/Http/Controllers/ReportController';
import {
    inputClassName,
    labelClassName,
} from '@/components/admin/menu-form';
import { cn } from '@/lib/utils';

type MenuReportItem = {
    rank: number;
    menu_id: number;
    menu_name: string;
    quantity_sold: number;
    revenue: number;
};

type Props = {
    filters: {
        preset: 'month' | 'range';
        from: string;
        to: string;
    };
    summary: {
        menu_count: number;
        quantity_sold: number;
        revenue: number;
    };
    items: MenuReportItem[];
};

function formatPrice(price: number): string {
    return price.toLocaleString();
}

export default function AdminReportsMenus({
    filters,
    summary,
    items,
}: Props) {
    const [preset, setPreset] = useState<'month' | 'range'>(filters.preset);
    const [from, setFrom] = useState(filters.from);
    const [to, setTo] = useState(filters.to);

    function applyMonth() {
        setPreset('month');
        router.get(
            menusReport.url({ query: { preset: 'month' } }),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function applyRange(event: React.FormEvent) {
        event.preventDefault();
        setPreset('range');
        router.get(
            menusReport.url({
                query: { preset: 'range', from, to },
            }),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="Laporan Menu" />
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
                            Laporan Menu
                        </h1>
                        <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Menu dengan penjualan terbanyak.
                        </p>
                    </header>

                    <div className="mb-4 grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            onClick={applyMonth}
                            className={cn(
                                'rounded-md border px-4 py-2.5 text-sm font-medium',
                                preset === 'month'
                                    ? 'border-[#1b1b18] bg-[#1b1b18] text-white dark:border-[#EDEDEC] dark:bg-[#EDEDEC] dark:text-[#1b1b18]'
                                    : 'border-[#e3e3e0] dark:border-[#3E3E3A]',
                            )}
                        >
                            Bulan ini
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

                    <section className="mb-4 grid grid-cols-3 gap-2">
                        <div className="rounded-lg border border-[#e3e3e0] bg-white p-3 dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <p className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                Menu
                            </p>
                            <p className="mt-1 text-base font-semibold tabular-nums">
                                {summary.menu_count}
                            </p>
                        </div>
                        <div className="rounded-lg border border-[#e3e3e0] bg-white p-3 dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <p className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                Terjual
                            </p>
                            <p className="mt-1 text-base font-semibold tabular-nums">
                                {summary.quantity_sold}
                            </p>
                        </div>
                        <div className="rounded-lg border border-[#e3e3e0] bg-white p-3 dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <p className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                Nilai
                            </p>
                            <p className="mt-1 text-base font-semibold tabular-nums">
                                {formatPrice(summary.revenue)}
                            </p>
                        </div>
                    </section>
                </div>

                <div className="mx-auto min-h-0 w-full max-w-lg flex-1 overflow-y-auto overscroll-contain">
                    {items.length === 0 ? (
                        <div className="rounded-lg border border-[#e3e3e0] bg-white p-10 text-center dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <p className="text-[#706f6c] dark:text-[#A1A09A]">
                                Tidak ada penjualan menu pada periode ini.
                            </p>
                        </div>
                    ) : (
                        <ul className="space-y-3 pb-4">
                            {items.map((item) => (
                                <li
                                    key={item.menu_id}
                                    className="rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0 flex-1">
                                            <p className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                                #{item.rank}
                                            </p>
                                            <p className="mt-1 truncate font-medium">
                                                {item.menu_name}
                                            </p>
                                            <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                                {item.quantity_sold} terjual
                                            </p>
                                        </div>
                                        <p className="shrink-0 text-sm font-medium tabular-nums">
                                            {formatPrice(item.revenue)}
                                        </p>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </>
    );
}
