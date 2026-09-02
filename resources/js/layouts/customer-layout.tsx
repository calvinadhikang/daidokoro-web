import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';

import { CustomerCartBar } from '@/components/customer/customer-cart-bar';
import { CustomerNavbar } from '@/components/customer/customer-navbar';
import { isCustomerCartUrl } from '@/lib/customer-nav';
import { cn } from '@/lib/utils';
import type { Customer, CustomerNav } from '@/types/customer';

type CustomerLayoutProps = {
    children: ReactNode;
};

type PageProps = {
    flash: {
        success?: string;
    };
    customer: Customer | null;
    customerNav: CustomerNav | null;
};

export default function CustomerLayout({ children }: CustomerLayoutProps) {
    const { url, props } = usePage<PageProps>();
    const { flash, customer, customerNav } = props;
    const showCartBar =
        (customerNav?.cartCount ?? 0) > 0 && !isCustomerCartUrl(url);

    return (
        <div className="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
            {flash.success && (
                <div className="px-4 pt-4">
                    <div className="mx-auto max-w-md rounded-lg border border-[#abefc6] bg-[#ecfdf3] px-4 py-3 text-sm text-[#027a48] dark:border-[#053321] dark:bg-[#053321] dark:text-[#75e0a7]">
                        {flash.success}
                    </div>
                </div>
            )}

            {customer !== null && (
                <div className="mx-auto flex max-w-md items-center justify-between gap-3 px-4 pt-4">
                    <p className="truncate text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        Hi, {customer.name}
                    </p>
                    <Link
                        href="/customer/logout"
                        method="post"
                        as="button"
                        className="shrink-0 text-sm font-medium text-[#706f6c] underline-offset-2 hover:underline dark:text-[#A1A09A]"
                    >
                        Logout
                    </Link>
                </div>
            )}

            <main className={cn(showCartBar ? 'pb-40' : 'pb-24')}>
                {children}
            </main>

            <CustomerCartBar />
            <CustomerNavbar />
        </div>
    );
}
