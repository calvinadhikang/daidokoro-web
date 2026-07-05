import { Head, Link, useForm, usePage } from '@inertiajs/react';

import { MenuOrderForm } from '@/components/menu/menu-order-form';
import type { Menu } from '@/types/menu';
import {
    serviceTypeLabel,
    type TransactionServiceType,
} from '@/types/transaction';

type PageProps = {
    customerNav: {
        cartCount: number;
        hasOrder: boolean;
    } | null;
};

type Props = {
    menu: Menu;
    serviceType: TransactionServiceType | null;
    cartCount: number;
};

export default function CustomerMenuShow({
    menu,
    serviceType,
    cartCount,
}: Props) {
    const { customerNav } = usePage<PageProps>().props;
    const form = useForm({
        quantity: 1,
        addon_option_ids: [] as number[],
    });

    const badgeCount = customerNav?.cartCount ?? cartCount;

    function handleSubmit(item: {
        quantity: number;
        addon_option_ids: number[];
    }) {
        form.transform(() => ({
            quantity: item.quantity,
            addon_option_ids: item.addon_option_ids,
        }));

        form.post(`/customer/menu/${menu.id}`);
    }

    return (
        <>
            <Head title={menu.name} />

            <div className="mx-auto max-w-md px-4 py-4">
                <header className="mb-4">
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <Link
                                href="/customer/menu"
                                className="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                ← Back to menu
                            </Link>
                            <h1 className="mt-2 text-2xl font-semibold">
                                {menu.name}
                            </h1>
                        </div>
                        {serviceType !== null && (
                            <span className="shrink-0 rounded-full bg-[#eff8ff] px-2.5 py-1 text-xs font-medium text-[#175cd3] dark:bg-[#102a56] dark:text-[#84caff]">
                                {serviceTypeLabel(serviceType)}
                            </span>
                        )}
                    </div>
                    {badgeCount > 0 && (
                        <Link
                            href="/customer/cart"
                            className="mt-3 inline-flex rounded-full bg-[#f5f5f4] px-2.5 py-1 text-xs font-medium text-[#44403c] dark:bg-[#292524] dark:text-[#d6d3d1]"
                        >
                            Cart · {badgeCount}{' '}
                            {badgeCount === 1 ? 'item' : 'items'}
                        </Link>
                    )}
                </header>

                <MenuOrderForm
                    menu={menu}
                    onSubmit={handleSubmit}
                    submitLabel={
                        form.processing ? 'Adding...' : 'Add to cart'
                    }
                    disabled={form.processing}
                    errors={{
                        quantity: form.errors.quantity,
                        addon_option_ids: form.errors.addon_option_ids,
                    }}
                />
            </div>
        </>
    );
}
