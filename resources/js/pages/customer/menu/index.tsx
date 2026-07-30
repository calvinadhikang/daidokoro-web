import { Head, Link } from '@inertiajs/react';

import { MenuBrowsePanel } from '@/components/menu/menu-browse-panel';
import type { Menu, MenuCategory } from '@/types/menu';
import {
    serviceTypeLabel,
    type TransactionServiceType,
} from '@/types/transaction';

type Props = {
    menus: Menu[];
    categories: MenuCategory[];
    serviceType: TransactionServiceType | null;
};

export default function CustomerMenuIndex({
    menus,
    categories,
    serviceType,
}: Props) {
    return (
        <>
            <Head title="Menu" />

            <div className="mx-auto -mb-24 flex h-[calc(100dvh-8rem)] max-w-md flex-col px-4 py-4">
                <header className="mb-4 shrink-0">
                    <Link
                        href="/"
                        className="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        ← Home
                    </Link>
                    <div className="mt-2 flex items-start justify-between gap-3">
                        <h1 className="text-2xl font-semibold">Menu</h1>
                        {serviceType !== null && (
                            <span className="shrink-0 rounded-full bg-[#eff8ff] px-2.5 py-1 text-xs font-medium text-[#175cd3] dark:bg-[#102a56] dark:text-[#84caff]">
                                {serviceTypeLabel(serviceType)}
                            </span>
                        )}
                    </div>
                </header>

                <MenuBrowsePanel
                    menus={menus}
                    categories={categories}
                    availability="all"
                    stickyFilters
                    unavailableLabel="Sold out"
                    menuHref={(menu) =>
                        menu.is_available
                            ? `/customer/menu/${menu.id}`
                            : undefined
                    }
                    emptyMessage="No menu items available right now."
                />
            </div>
        </>
    );
}
