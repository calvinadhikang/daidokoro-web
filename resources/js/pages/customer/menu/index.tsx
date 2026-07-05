import { Head, Link, usePage } from '@inertiajs/react';

import { MenuBrowsePanel } from '@/components/menu/menu-browse-panel';
import type { Customer } from '@/types/customer';
import type { Menu, MenuCategory } from '@/types/menu';
import {
    serviceTypeLabel,
    type TransactionServiceType,
} from '@/types/transaction';

type PageProps = {
    customer: Customer | null;
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
    const { customer } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Menu" />

            <div className="mx-auto max-w-md px-4 py-4">
                <header className="mb-4">
                    <Link
                        href="/"
                        className="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        ← Home
                    </Link>
                    <div className="mt-2 flex items-start justify-between gap-3">
                        <div>
                            <h1 className="text-2xl font-semibold">Menu</h1>
                            {customer !== null && (
                                <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                    Hi, {customer.name}
                                </p>
                            )}
                        </div>
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
                    availability="available"
                    menuHref={(menu) => `/customer/menu/${menu.id}`}
                    emptyMessage="No menu items available right now."
                />
            </div>
        </>
    );
}
