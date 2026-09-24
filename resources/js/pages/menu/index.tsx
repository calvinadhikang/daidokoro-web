import { Head, Link } from '@inertiajs/react';

import { MenuBrowsePanel } from '@/components/menu/menu-browse-panel';
import { cn } from '@/lib/utils';
import type { Menu, MenuCategory } from '@/types/menu';
import type { StoreStatus } from '@/types/operating-hours';

type Props = {
    menus: Menu[];
    categories: MenuCategory[];
    storeStatus: StoreStatus;
};

export default function MenuIndex({ menus, categories, storeStatus }: Props) {
    return (
        <>
            <Head title="Menu" />

            <div className="flex h-dvh min-h-0 flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <header className="shrink-0 border-b border-[#e3e3e0] bg-[#FDFDFC]/95 px-4 py-3 backdrop-blur dark:border-[#3E3E3A] dark:bg-[#0a0a0a]/95">
                    <div className="mx-auto flex max-w-md items-center gap-3">
                        <Link
                            href="/"
                            className="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            ← Home
                        </Link>
                        <p className="text-sm font-medium">Daidokoro</p>
                    </div>
                </header>

                <main className="mx-auto flex w-full min-h-0 min-w-0 max-w-md flex-1 flex-col px-4 pt-4">
                    <header className="mb-4 shrink-0">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Menu
                        </h1>
                        <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Lihat menu dan harga. Pesan lewat tombol di bawah.
                        </p>
                    </header>

                    <MenuBrowsePanel
                        menus={menus}
                        categories={categories}
                        availability="all"
                        variant="customer"
                        stickyFilters
                        enableAvailabilityToggle
                        unavailableLabel="Sold out"
                        emptyMessage="No menu items yet."
                    />
                </main>

                <footer className="shrink-0 border-t border-[#e3e3e0] bg-[#FDFDFC]/95 px-4 py-4 backdrop-blur dark:border-[#3E3E3A] dark:bg-[#0a0a0a]/95">
                    <div className="mx-auto max-w-md">
                        {storeStatus.is_open ? (
                            <Link
                                href="/customer/login"
                                className={cn(
                                    'flex w-full items-center justify-center rounded-md px-4 py-3 text-sm font-medium transition-colors',
                                    'border border-[#1b1b18] bg-[#1b1b18] text-white active:bg-[#333] dark:border-[#EDEDEC] dark:bg-[#EDEDEC] dark:text-[#1b1b18] dark:active:bg-[#d4d4d2]',
                                )}
                            >
                                Pesan sekarang
                            </Link>
                        ) : (
                            <button
                                type="button"
                                disabled
                                className={cn(
                                    'flex w-full items-center justify-center rounded-md px-4 py-3 text-sm font-medium transition-colors',
                                    'cursor-not-allowed border border-[#e3e3e0] bg-[#FDFDFC] text-[#706f6c] opacity-60 dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#A1A09A]',
                                )}
                            >
                                Pesan sekarang
                            </button>
                        )}
                        {!storeStatus.is_open && (
                            <p className="mt-3 text-center text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                Ordering is available during operating hours
                                only.
                            </p>
                        )}
                    </div>
                </footer>
            </div>
        </>
    );
}
