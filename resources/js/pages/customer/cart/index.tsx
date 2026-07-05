import { Head, Link, usePage } from '@inertiajs/react';

import { CustomerCartPanel } from '@/components/menu/customer-cart-panel';
import type { CartItem } from '@/types/customer';
import {
    serviceTypeLabel,
    type TransactionServiceType,
} from '@/types/transaction';

type PageProps = {
    errors: {
        cart?: string;
    };
};

type Props = {
    serviceType: TransactionServiceType | null;
    cart: CartItem[];
    cartTotal: number;
};

export default function CustomerCartIndex({
    serviceType,
    cart,
    cartTotal,
}: Props) {
    const { errors } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Cart" />

            <div className="mx-auto max-w-md px-4 py-4">
                <header className="mb-4">
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <h1 className="text-2xl font-semibold">Cart</h1>
                            <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                Review items before sending your order.
                            </p>
                        </div>
                        {serviceType !== null && (
                            <span className="shrink-0 rounded-full bg-[#eff8ff] px-2.5 py-1 text-xs font-medium text-[#175cd3] dark:bg-[#102a56] dark:text-[#84caff]">
                                {serviceTypeLabel(serviceType)}
                            </span>
                        )}
                    </div>
                </header>

                {errors.cart && (
                    <p className="mb-4 rounded-md border border-[#fda29b] bg-[#fef3f2] px-3 py-2 text-sm text-[#b42318] dark:border-[#912018] dark:bg-[#55160c] dark:text-[#fda29b]">
                        {errors.cart}
                    </p>
                )}

                <CustomerCartPanel
                    cart={cart}
                    cartTotal={cartTotal}
                    emptyMessage="Your cart is empty."
                    emptyAction={
                        <Link
                            href="/customer/menu"
                            className="mt-4 inline-block text-sm font-medium text-[#175cd3] dark:text-[#84caff]"
                        >
                            Browse menu
                        </Link>
                    }
                />
            </div>
        </>
    );
}
