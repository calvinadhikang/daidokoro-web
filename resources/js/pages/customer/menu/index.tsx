import { Head, Link, usePage } from '@inertiajs/react';

import { MenuBrowsePanel } from '@/components/menu/menu-browse-panel';
import { cn } from '@/lib/utils';
import type { CustomerNav } from '@/types/customer';
import type { Menu, MenuCategory } from '@/types/menu';
import { serviceTypeLabel } from '@/types/transaction';
import type { TransactionServiceType } from '@/types/transaction';

type PageProps = {
    customerNav: CustomerNav | null;
};

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
    const { customerNav } = usePage<PageProps>().props;
    const showCartBar = (customerNav?.cartCount ?? 0) > 0;

    return (
        <>
            <Head title="Menu" />

            <div
                className={cn(
                    'mx-auto flex max-w-md min-w-0 flex-col px-4 py-4',
                    showCartBar
                        ? '-mb-40 h-[calc(100dvh-10.5rem)]'
                        : '-mb-24 h-[calc(100dvh-8rem)]',
                )}
            >
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
