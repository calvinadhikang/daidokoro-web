import { Head, Link } from '@inertiajs/react';

import {
    menus as menusReport,
    sales as salesReport,
} from '@/actions/App/Http/Controllers/ReportController';

export default function AdminReportsIndex() {
    return (
        <>
            <Head title="Laporan" />
            <div className="flex h-[calc(100dvh-7.5rem)] flex-col px-4 py-4">
                <div className="mx-auto flex w-full max-w-lg flex-1 flex-col">
                    <header className="mb-6">
                        <h1 className="text-2xl font-semibold">Laporan</h1>
                        <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Pilih jenis laporan yang ingin dilihat.
                        </p>
                    </header>

                    <div className="space-y-3">
                        <Link
                            href={salesReport.url()}
                            className="flex w-full items-center justify-center rounded-md border border-[#1b1b18] bg-[#1b1b18] px-4 py-3 text-sm font-medium text-white dark:border-[#EDEDEC] dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
                        >
                            Laporan Penjualan
                        </Link>
                        <Link
                            href={menusReport.url()}
                            className="flex w-full items-center justify-center rounded-md border border-[#e3e3e0] px-4 py-3 text-sm font-medium dark:border-[#3E3E3A]"
                        >
                            Laporan Menu
                        </Link>
                    </div>
                </div>
            </div>
        </>
    );
}
