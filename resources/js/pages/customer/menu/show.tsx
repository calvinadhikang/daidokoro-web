import { Head, Link, useForm } from '@inertiajs/react';

import { MenuOrderForm } from '@/components/menu/menu-order-form';
import type { Menu } from '@/types/menu';
import { serviceTypeLabel } from '@/types/transaction';
import type { TransactionServiceType } from '@/types/transaction';

type Props = {
    menu: Menu;
    serviceType: TransactionServiceType | null;
};

export default function CustomerMenuShow({ menu, serviceType }: Props) {
    const form = useForm({
        quantity: 1,
        addon_option_ids: [] as number[],
    });

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
                </header>

                <MenuOrderForm
                    menu={menu}
                    onSubmit={handleSubmit}
                    submitLabel={form.processing ? 'Adding...' : 'Add to cart'}
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
