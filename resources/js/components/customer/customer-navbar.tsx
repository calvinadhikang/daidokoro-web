import { Link, usePage } from '@inertiajs/react';

import { customerNavItems, isCustomerNavActive } from '@/lib/customer-nav';
import { cn } from '@/lib/utils';

type PageProps = {
    customerNav: {
        cartCount: number;
        hasOrder: boolean;
    } | null;
};

export function CustomerNavbar() {
    const { url, props } = usePage<PageProps>();
    const { customerNav } = props;

    return (
        <nav className="fixed inset-x-0 bottom-0 z-30 border-t border-[#e3e3e0] bg-[#FDFDFC]/95 backdrop-blur dark:border-[#3E3E3A] dark:bg-[#0a0a0a]/95">
            <div className="mx-auto flex max-w-md pb-[env(safe-area-inset-bottom)]">
                {customerNavItems.map((item) => {
                    const isActive = isCustomerNavActive(url, item);
                    const badge =
                        item.match === 'cart' && (customerNav?.cartCount ?? 0) > 0
                            ? customerNav?.cartCount
                            : item.match === 'order' && customerNav?.hasOrder
                              ? '•'
                              : null;

                    return (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={cn(
                                'relative flex flex-1 flex-col items-center gap-1 px-2 py-3 text-xs font-medium',
                                isActive
                                    ? 'text-[#1b1b18] dark:text-[#EDEDEC]'
                                    : 'text-[#706f6c] dark:text-[#A1A09A]',
                            )}
                        >
                            <span
                                className={cn(
                                    'h-1 w-8 rounded-full',
                                    isActive
                                        ? 'bg-[#1b1b18] dark:bg-[#EDEDEC]'
                                        : 'bg-transparent',
                                )}
                            />
                            <span className="relative">
                                {item.label}
                                {badge !== null && (
                                    <span className="absolute -top-2 -right-3 flex min-h-4 min-w-4 items-center justify-center rounded-full bg-[#1b1b18] px-1 text-[10px] font-semibold text-white dark:bg-[#EDEDEC] dark:text-[#1b1b18]">
                                        {badge}
                                    </span>
                                )}
                            </span>
                        </Link>
                    );
                })}
            </div>
        </nav>
    );
}
