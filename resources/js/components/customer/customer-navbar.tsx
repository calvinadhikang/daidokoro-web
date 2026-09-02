import { Link, usePage } from '@inertiajs/react';

import { customerNavItems, isCustomerNavActive } from '@/lib/customer-nav';
import { cn } from '@/lib/utils';
import type { CustomerNav } from '@/types/customer';

type PageProps = {
    customerNav: CustomerNav | null;
    flash: {
        success?: string;
    };
};

export function CustomerNavbar() {
    const { url, props } = usePage<PageProps>();
    const { customerNav, flash } = props;
    const cartCount = customerNav?.cartCount ?? 0;
    const highlightCart = cartCount > 0 && Boolean(flash.success);

    return (
        <nav className="fixed inset-x-0 bottom-0 z-30 border-t border-[#e3e3e0] bg-[#FDFDFC]/95 backdrop-blur dark:border-[#3E3E3A] dark:bg-[#0a0a0a]/95">
            <div className="mx-auto flex max-w-md pb-[env(safe-area-inset-bottom)]">
                {customerNavItems.map((item) => {
                    const isActive = isCustomerNavActive(url, item);
                    const isCart = item.match === 'cart';
                    const badge =
                        isCart && cartCount > 0
                            ? cartCount
                            : item.match === 'order' && customerNav?.hasOrder
                              ? '•'
                              : null;

                    return (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={cn(
                                'relative flex flex-1 flex-col items-center gap-1 px-2 py-3 text-xs font-medium',
                                isActive || (isCart && cartCount > 0)
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
                                    <span
                                        className={cn(
                                            'absolute -top-2 -right-3 flex min-h-4 min-w-4 items-center justify-center rounded-full px-1 text-[10px] font-semibold',
                                            isCart
                                                ? 'bg-[#175cd3] text-white dark:bg-[#84caff] dark:text-[#102a56]'
                                                : 'bg-[#1b1b18] text-white dark:bg-[#EDEDEC] dark:text-[#1b1b18]',
                                            highlightCart &&
                                                isCart &&
                                                'cart-nav-badge-highlight',
                                        )}
                                    >
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
