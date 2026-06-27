import { Head, Link, useForm } from '@inertiajs/react';

import { MenuOrderForm } from '@/components/menu/menu-order-form';
import type { Menu } from '@/types/menu';
import {
    serviceTypeLabel,
    type TransactionServiceType,
} from '@/types/transaction';

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

            <div className="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <header className="sticky top-0 z-10 border-b border-[#e3e3e0] bg-[#FDFDFC]/95 px-4 py-4 backdrop-blur dark:border-[#3E3E3A] dark:bg-[#0a0a0a]/95">
                    <div className="mx-auto max-w-md">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <Link
                                    href="/customer/menu"
                                    className="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    ← Back to menu
                                </Link>
                                <h1 className="mt-2 text-2xl font-semibold">
                                    Order item
                                </h1>
                            </div>
                            {serviceType !== null && (
                                <span className="shrink-0 rounded-full bg-[#eff8ff] px-2.5 py-1 text-xs font-medium text-[#175cd3] dark:bg-[#102a56] dark:text-[#84caff]">
                                    {serviceTypeLabel(serviceType)}
                                </span>
                            )}
                        </div>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-md flex-1 px-4 py-4 pb-8">
                    <MenuOrderForm
                        menu={menu}
                        onSubmit={handleSubmit}
                        submitLabel={
                            form.processing ? 'Adding...' : 'Add to order'
                        }
                        disabled={form.processing}
                        errors={{
                            quantity: form.errors.quantity,
                            addon_option_ids: form.errors.addon_option_ids,
                        }}
                    />
                </main>
            </div>
        </>
    );
}
