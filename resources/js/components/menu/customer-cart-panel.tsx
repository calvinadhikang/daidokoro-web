import { router } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useState } from 'react';

import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { formatPrice } from '@/components/menu/order-item-groups';
import { cn } from '@/lib/utils';
import type { CartItem } from '@/types/customer';

const MAX_QUANTITY = 99;

function QuantityButton({
    label,
    disabled,
    onClick,
}: {
    label: string;
    disabled: boolean;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            aria-label={label}
            disabled={disabled}
            onClick={onClick}
            className="flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-[#e3e3e0] text-lg leading-none disabled:opacity-40 dark:border-[#3E3E3A]"
        >
            {label === 'Increase quantity' ? '+' : '−'}
        </button>
    );
}

type CartItemRowProps = {
    item: CartItem;
    index: number;
    busy: boolean;
    onDecrease: (index: number) => void;
    onIncrease: (index: number) => void;
    onRemove: (index: number) => void;
};

function CartItemRow({
    item,
    index,
    busy,
    onDecrease,
    onIncrease,
    onRemove,
}: CartItemRowProps) {
    return (
        <li className="rounded-md border border-[#e3e3e0] bg-white p-3 dark:border-[#3E3E3A] dark:bg-[#161615]">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="text-sm font-medium">{item.menu_name}</p>
                    <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        {formatPrice(item.unit_price)} each
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

            <div className="mt-3 flex items-center justify-between gap-3">
                <div className="flex items-center gap-2">
                    <QuantityButton
                        label="Decrease quantity"
                        disabled={busy || item.quantity <= 1}
                        onClick={() => onDecrease(index)}
                    />
                    <span
                        className={cn(
                            'min-w-6 text-center text-sm font-medium tabular-nums',
                            busy && 'opacity-50',
                        )}
                    >
                        {item.quantity}
                    </span>
                    <QuantityButton
                        label="Increase quantity"
                        disabled={busy || item.quantity >= MAX_QUANTITY}
                        onClick={() => onIncrease(index)}
                    />
                </div>
                <button
                    type="button"
                    onClick={() => onRemove(index)}
                    disabled={busy}
                    className="rounded-md px-2 py-1.5 text-xs font-medium text-[#b42318] disabled:opacity-50"
                >
                    Remove
                </button>
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

const CHECKOUT_NOTICE =
    'Mohon maaf tidak menerima tambahan order, semua order masuk di awal terima kasih, DAIDOKORO';

export function CustomerCartPanel({
    cart,
    cartTotal,
    emptyMessage,
    emptyAction,
}: CustomerCartPanelProps) {
    const [checkingOut, setCheckingOut] = useState(false);
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [busyIndex, setBusyIndex] = useState<number | null>(null);

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

    function handleQuantityChange(index: number, quantity: number) {
        setBusyIndex(index);
        router.patch(
            `/customer/cart/items/${index}`,
            { quantity },
            {
                preserveScroll: true,
                onFinish: () => setBusyIndex(null),
            },
        );
    }

    function handleRemove(index: number) {
        setBusyIndex(index);
        router.delete(`/customer/cart/items/${index}`, {
            preserveScroll: true,
            onFinish: () => setBusyIndex(null),
        });
    }

    function handleCheckout() {
        setConfirmOpen(false);
        setCheckingOut(true);
        router.post(
            '/customer/cart/checkout',
            {},
            {
                onSuccess: () => {
                    router.reload({ only: ['customerNav'] });
                },
                onFinish: () => setCheckingOut(false),
            },
        );
    }

    const controlsBusy = checkingOut || busyIndex !== null;

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
                            busy={controlsBusy}
                            onDecrease={(itemIndex) =>
                                handleQuantityChange(
                                    itemIndex,
                                    cart[itemIndex].quantity - 1,
                                )
                            }
                            onIncrease={(itemIndex) =>
                                handleQuantityChange(
                                    itemIndex,
                                    cart[itemIndex].quantity + 1,
                                )
                            }
                            onRemove={handleRemove}
                        />
                    ))}
                </ul>
            </section>

            <button
                type="button"
                onClick={() => setConfirmOpen(true)}
                disabled={controlsBusy}
                className="w-full rounded-md border border-[#1b1b18] bg-[#1b1b18] px-4 py-3 text-sm font-medium text-white disabled:opacity-50 dark:border-[#EDEDEC] dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
            >
                {checkingOut
                    ? 'Sending order...'
                    : `Checkout · ${formatPrice(cartTotal)}`}
            </button>

            <ConfirmDialog
                open={confirmOpen}
                title="Perhatian"
                description={CHECKOUT_NOTICE}
                confirmLabel="Lanjut pesan"
                cancelLabel="Batal"
                loading={checkingOut}
                onConfirm={handleCheckout}
                onCancel={() => setConfirmOpen(false)}
            />
        </div>
    );
}
