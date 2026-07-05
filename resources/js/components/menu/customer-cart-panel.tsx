import { router } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useState } from 'react';

import { formatPrice } from '@/components/menu/order-item-groups';
import type { CartItem } from '@/types/customer';

type CartItemRowProps = {
    item: CartItem;
    index: number;
};

function CartItemRow({ item, index }: CartItemRowProps) {
    return (
        <li className="rounded-md border border-[#e3e3e0] bg-white p-3 dark:border-[#3E3E3A] dark:bg-[#161615]">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="text-sm font-medium">{item.menu_name}</p>
                    <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        Qty {item.quantity} · {formatPrice(item.unit_price)} each
                    </p>
                    {item.addons.length > 0 && (
                        <ul className="mt-2 space-y-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                            {item.addons.map((addon) => (
                                <li key={`${index}-${addon.menu_addon_option_id}`}>
                                    {addon.group_name}: {addon.name}
                                    {addon.price > 0 && (
                                        <span className="tabular-nums">
                                            {' '}
                                            (+{formatPrice(addon.price)})
                                        </span>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
                <p className="shrink-0 text-sm font-medium tabular-nums">
                    {formatPrice(item.line_total)}
                </p>
            </div>
        </li>
    );
}

type CustomerCartPanelProps = {
    cart: CartItem[];
    cartTotal: number;
    emptyMessage?: string;
    emptyAction?: ReactNode;
};

export function CustomerCartPanel({
    cart,
    cartTotal,
    emptyMessage,
    emptyAction,
}: CustomerCartPanelProps) {
    const [checkingOut, setCheckingOut] = useState(false);

    if (cart.length === 0) {
        if (emptyMessage === undefined) {
            return null;
        }

        return (
            <div className="rounded-lg border border-dashed border-[#e3e3e0] p-8 text-center dark:border-[#3E3E3A]">
                <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    {emptyMessage}
                </p>
                {emptyAction}
            </div>
        );
    }

    function handleCheckout() {
        setCheckingOut(true);
        router.post(
            '/customer/cart/checkout',
            {},
            {
                onFinish: () => setCheckingOut(false),
            },
        );
    }

    return (
        <div className="space-y-4">
            <section className="rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
                <div className="mb-4 flex items-end justify-between gap-3">
                    <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        {cart.length} {cart.length === 1 ? 'item' : 'items'}
                    </p>
                    <p className="text-lg font-semibold tabular-nums">
                        {formatPrice(cartTotal)}
                    </p>
                </div>

                <ul className="space-y-3">
                    {cart.map((item, index) => (
                        <CartItemRow
                            key={`${item.menu_id}-${index}`}
                            item={item}
                            index={index}
                        />
                    ))}
                </ul>
            </section>

            <button
                type="button"
                onClick={handleCheckout}
                disabled={checkingOut}
                className="w-full rounded-md border border-[#1b1b18] bg-[#1b1b18] px-4 py-3 text-sm font-medium text-white disabled:opacity-50 dark:border-[#EDEDEC] dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
            >
                {checkingOut
                    ? 'Sending order...'
                    : `Checkout · ${formatPrice(cartTotal)}`}
            </button>
        </div>
    );
}
