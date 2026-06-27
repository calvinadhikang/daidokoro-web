import { Head, Link, router, useForm } from '@inertiajs/react';
import { useMemo, useRef, useState } from 'react';

import {
    index as transactionsIndex,
    store,
} from '@/actions/App/Http/Controllers/TransactionController';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import {
    inputClassName,
    labelClassName,
} from '@/components/admin/menu-form';
import {
    TransactionMenuPicker,
    type TransactionMenuPickerItem,
} from '@/components/admin/transaction-menu-picker';
import { cn } from '@/lib/utils';
import type { Menu } from '@/types/menu';
import type {
    CreateTransactionForm,
    TransactionAddon,
    TransactionServiceType,
} from '@/types/transaction';
import { serviceTypeLabel } from '@/types/transaction';

type Props = {
    menus: Menu[];
};

type LocalCartItem = TransactionMenuPickerItem & {
    key: number;
};

function formatPrice(price: number): string {
    return price.toLocaleString();
}

function FieldError({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return <p className="mt-1 text-xs text-[#b42318]">{message}</p>;
}

function ItemAddons({ addons }: { addons: TransactionAddon[] }) {
    if (addons.length === 0) {
        return null;
    }

    return (
        <ul className="mt-2 space-y-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">
            {addons.map((addon, index) => (
                <li key={`${addon.menu_addon_option_id}-${index}`}>
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
    );
}

export default function AdminTransactionCreate({ menus }: Props) {
    const [cart, setCart] = useState<LocalCartItem[]>([]);
    const [confirmOpen, setConfirmOpen] = useState(false);
    const nextKeyRef = useRef(0);

    const form = useForm<CreateTransactionForm>({
        customer_name: '',
        customer_phone: '',
        service_type: 'dine_in',
        items: [],
    });

    const cartTotal = useMemo(
        () => cart.reduce((sum, item) => sum + item.line_total, 0),
        [cart],
    );

    function handleAddToCart(item: TransactionMenuPickerItem) {
        const key = nextKeyRef.current;
        nextKeyRef.current += 1;
        setCart((current) => [...current, { ...item, key }]);
    }

    function removeFromCart(key: number) {
        setCart((current) => current.filter((item) => item.key !== key));
    }

    function handleSubmit(event: React.FormEvent) {
        event.preventDefault();
        setConfirmOpen(true);
    }

    function handleConfirmCreate() {
        form.transform((data) => ({
            customer_name: data.customer_name,
            customer_phone: data.customer_phone,
            service_type: data.service_type,
            items: cart.map((item) => ({
                menu_id: item.menu_id,
                quantity: item.quantity,
                addon_option_ids: item.addon_option_ids,
            })),
        }));

        form.post(store.url(), {
            onFinish: () => setConfirmOpen(false),
        });
    }

    const confirmDescription =
        cart.length === 0
            ? `Create a ${serviceTypeLabel(form.data.service_type).toLowerCase()} transaction for ${form.data.customer_name || 'this customer'} with no items yet?`
            : `Create a ${serviceTypeLabel(form.data.service_type).toLowerCase()} transaction for ${form.data.customer_name || 'this customer'} with ${cart.length} item${cart.length === 1 ? '' : 's'} totaling ${formatPrice(cartTotal)}?`;

    const serviceTypes: TransactionServiceType[] = ['dine_in', 'takeaway'];

    return (
        <>
            <Head title="New Transaction" />

            <ConfirmDialog
                open={confirmOpen}
                title="Create transaction?"
                description={confirmDescription}
                confirmLabel="Create transaction"
                loading={form.processing}
                onConfirm={handleConfirmCreate}
                onCancel={() => setConfirmOpen(false)}
            />

            <header className="sticky top-0 z-10 border-b border-[#e3e3e0] bg-[#FDFDFC]/95 px-4 py-4 backdrop-blur dark:border-[#3E3E3A] dark:bg-[#0a0a0a]/95">
                <div className="mx-auto flex max-w-lg items-center gap-3">
                    <Link
                        href={transactionsIndex.url()}
                        className="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Back
                    </Link>
                    <h1 className="text-lg font-semibold">New Transaction</h1>
                </div>
            </header>

            <form
                onSubmit={handleSubmit}
                className="mx-auto flex h-[calc(100dvh-8.5rem)] max-w-lg flex-col px-4 py-4"
            >
                <div className="min-h-0 flex-1 overflow-y-auto overscroll-contain pb-4">
                    <section className="mb-6 rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <h2 className="mb-4 text-base font-semibold">
                            Customer details
                        </h2>

                        <div className="space-y-4">
                            <div>
                                <label
                                    htmlFor="customer_name"
                                    className={labelClassName}
                                >
                                    Name
                                </label>
                                <input
                                    id="customer_name"
                                    type="text"
                                    value={form.data.customer_name}
                                    onChange={(event) =>
                                        form.setData(
                                            'customer_name',
                                            event.target.value,
                                        )
                                    }
                                    className={inputClassName}
                                    placeholder="Customer name"
                                />
                                <FieldError message={form.errors.customer_name} />
                            </div>

                            <div>
                                <label
                                    htmlFor="customer_phone"
                                    className={labelClassName}
                                >
                                    Phone
                                </label>
                                <input
                                    id="customer_phone"
                                    type="tel"
                                    value={form.data.customer_phone}
                                    onChange={(event) =>
                                        form.setData(
                                            'customer_phone',
                                            event.target.value,
                                        )
                                    }
                                    className={inputClassName}
                                    placeholder="Phone number"
                                />
                                <FieldError message={form.errors.customer_phone} />
                            </div>

                            <div>
                                <p className={labelClassName}>Order type</p>
                                <div className="grid grid-cols-2 gap-2">
                                    {serviceTypes.map((type) => (
                                        <button
                                            key={type}
                                            type="button"
                                            onClick={() =>
                                                form.setData(
                                                    'service_type',
                                                    type,
                                                )
                                            }
                                            className={cn(
                                                'rounded-md border px-3 py-2.5 text-sm font-medium',
                                                form.data.service_type === type
                                                    ? 'border-[#1b1b18] bg-[#1b1b18] text-white dark:border-[#EDEDEC] dark:bg-[#EDEDEC] dark:text-[#1b1b18]'
                                                    : 'border-[#e3e3e0] dark:border-[#3E3E3A]',
                                            )}
                                        >
                                            {serviceTypeLabel(type)}
                                        </button>
                                    ))}
                                </div>
                                <FieldError message={form.errors.service_type} />
                            </div>
                        </div>
                    </section>

                    <section className="mb-6">
                        <div className="mb-3 flex items-center justify-between">
                            <h2 className="text-base font-semibold">Order items</h2>
                            {cart.length > 0 && (
                                <p className="text-sm font-medium tabular-nums">
                                    {formatPrice(cartTotal)}
                                </p>
                            )}
                        </div>

                        {cart.length === 0 ? (
                            <div className="mb-4 rounded-lg border border-dashed border-[#e3e3e0] p-6 text-center text-sm text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]">
                                No items added yet.
                            </div>
                        ) : (
                            <ul className="mb-4 space-y-3">
                                {cart.map((item) => (
                                    <li
                                        key={item.key}
                                        className="rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="font-medium">
                                                    {item.menu_name}
                                                </p>
                                                <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                                    Qty {item.quantity} ·{' '}
                                                    {formatPrice(item.unit_price)}{' '}
                                                    each
                                                </p>
                                                <ItemAddons addons={item.addons} />
                                            </div>
                                            <div className="flex shrink-0 flex-col items-end gap-2">
                                                <p className="font-medium tabular-nums">
                                                    {formatPrice(item.line_total)}
                                                </p>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        removeFromCart(item.key)
                                                    }
                                                    className="text-xs text-[#b42318]"
                                                >
                                                    Remove
                                                </button>
                                            </div>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}

                        <FieldError
                            message={
                                form.errors.items ??
                                form.errors['items.0.menu_id']
                            }
                        />
                    </section>

                    <section>
                        <h2 className="mb-3 text-base font-semibold">
                            Add menu for customer
                        </h2>
                        <div className="rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <TransactionMenuPicker
                                menus={menus}
                                onAdd={handleAddToCart}
                            />
                        </div>
                    </section>
                </div>

                <div className="shrink-0 border-t border-[#e3e3e0] pt-4 dark:border-[#3E3E3A]">
                    <button
                        type="submit"
                        disabled={form.processing}
                        className="flex w-full items-center justify-center rounded-md bg-[#1b1b18] px-4 py-3 text-sm font-medium text-white disabled:opacity-50 dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
                    >
                        Create transaction
                    </button>
                </div>
            </form>
        </>
    );
}
