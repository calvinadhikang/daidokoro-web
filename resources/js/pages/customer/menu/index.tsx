import { Head, Link, usePage } from '@inertiajs/react';

import { MenuBrowsePanel } from '@/components/menu/menu-browse-panel';
import type { Customer } from '@/types/customer';
import type { Menu } from '@/types/menu';
import {
    serviceTypeLabel,
    type TransactionServiceType,
} from '@/types/transaction';

type PageProps = {
    customer: Customer | null;
    flash: {
        success?: string;
    };
};

type Props = {
    menus: Menu[];
    serviceType: TransactionServiceType | null;
};

export default function CustomerMenuIndex({ menus, serviceType }: Props) {
    const { customer, flash } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Order menu" />

            <div className="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <header className="sticky top-0 z-10 border-b border-[#e3e3e0] bg-[#FDFDFC]/95 px-4 py-4 backdrop-blur dark:border-[#3E3E3A] dark:bg-[#0a0a0a]/95">
                    <div className="mx-auto max-w-md">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <Link
                                    href="/"
                                    className="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    ← Home
                                </Link>
                                <h1 className="mt-2 text-2xl font-semibold">
                                    Order menu
                                </h1>
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

                        {flash.success && (
                            <p className="mt-3 rounded-md border border-[#abefc6] bg-[#ecfdf3] px-3 py-2 text-sm text-[#027a48] dark:border-[#085d3a] dark:bg-[#053321] dark:text-[#75e0a7]">
                                {flash.success}
                            </p>
                        )}
                    </div>
                </header>

                <main className="mx-auto flex w-full max-w-md flex-1 flex-col px-4 py-4">
                    <MenuBrowsePanel
                        menus={menus}
                        availability="available"
                        emptyMessage="No menu items available right now."
                    />
                </main>
            </div>
        </>
    );
}
