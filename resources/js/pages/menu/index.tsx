import { Head, Link } from '@inertiajs/react';

import { MenuBrowsePanel } from '@/components/menu/menu-browse-panel';
import type { Menu } from '@/types/menu';

type Props = {
    menus: Menu[];
};

export default function MenuIndex({ menus }: Props) {
    return (
        <>
            <Head title="Menu" />

            <div className="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <header className="sticky top-0 z-10 border-b border-[#e3e3e0] bg-[#FDFDFC]/95 px-4 py-4 backdrop-blur dark:border-[#3E3E3A] dark:bg-[#0a0a0a]/95">
                    <div className="mx-auto max-w-md">
                        <Link
                            href="/"
                            className="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            ← Home
                        </Link>
                        <h1 className="mt-2 text-2xl font-semibold">Menu</h1>
                        <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Browse availability and prices
                        </p>
                    </div>
                </header>

                <main className="mx-auto flex w-full max-w-md flex-1 flex-col px-4 py-4">
                    <MenuBrowsePanel
                        menus={menus}
                        availability="all"
                        showAvailabilityBadge
                        emptyMessage="No menu items yet."
                    />
                </main>
            </div>
        </>
    );
}
