import { Link, usePage } from '@inertiajs/react';

import { formatPrice } from '@/components/menu/order-item-groups';
import { isCustomerCartUrl } from '@/lib/customer-nav';
import { cn } from '@/lib/utils';
import type { CustomerNav } from '@/types/customer';

type PageProps = {
    customerNav: CustomerNav | null;
    flash: {
        success?: string;
    };
};

export function CustomerCartBar() {
    const { url, props } = usePage<PageProps>();
    const { customerNav, flash } = props;
    const cartCount = customerNav?.cartCount ?? 0;
    const cartTotal = customerNav?.cartTotal ?? 0;

    if (cartCount === 0 || isCustomerCartUrl(url)) {
        return null;
    }

    const highlighted = Boolean(flash.success);

    return (
        <div className="pointer-events-none fixed inset-x-0 bottom-[calc(4.25rem+env(safe-area-inset-bottom))] z-40 px-4">
            <Link
                href="/customer/cart"
                className={cn(
                    'pointer-events-auto mx-auto flex max-w-md items-center justify-between gap-3 rounded-full border border-[#1b1b18] bg-[#1b1b18] px-5 py-3 text-white shadow-lg dark:border-[#EDEDEC] dark:bg-[#EDEDEC] dark:text-[#1b1b18]',
                    highlighted && 'cart-bar-highlight',
                )}
            >
                <span className="min-w-0">
                    <span className="block text-sm font-semibold">
                        View cart
                    </span>
                    <span className="block text-xs text-white/70 dark:text-[#1b1b18]/70">
                        {cartCount} {cartCount === 1 ? 'item' : 'items'}
                    </span>
                </span>
                <span className="shrink-0 text-sm font-semibold tabular-nums">
                    {formatPrice(cartTotal)}
                </span>
            </Link>
        </div>
    );
}
