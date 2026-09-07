import { Head, Link, useForm } from '@inertiajs/react';

import { MenuImage } from '@/components/admin/menu-image';
import { MenuOrderForm } from '@/components/menu/menu-order-form';
import { formatPrice } from '@/components/menu/order-item-groups';
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
        note: '' as string | null,
    });

    function handleSubmit(item: {
        quantity: number;
        addon_option_ids: number[];
        note: string | null;
    }) {
        form.transform(() => ({
            quantity: item.quantity,
            addon_option_ids: item.addon_option_ids,
            note: item.note,
        }));

        form.post(`/customer/menu/${menu.id}`);
    }

    const categories = menu.categories ?? [];

    return (
        <>
            <Head title={menu.name} />

            <div className="mx-auto max-w-md">
                <div className="relative h-72 overflow-hidden bg-[#e3e3e0] dark:bg-[#3E3E3A]">
                    <MenuImage
                        src={menu.image}
                        alt={menu.name}
                        className="h-full w-full"
                    />
                    <div className="absolute inset-0 bg-linear-to-t from-black/55 via-black/10 to-black/25" />

                    <Link
                        href="/customer/menu"
                        aria-label="Back to menu"
                        className="absolute top-4 left-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-[#1b1b18] shadow-sm backdrop-blur dark:bg-[#0a0a0a]/80 dark:text-[#EDEDEC]"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            className="size-5"
                            aria-hidden="true"
                        >
                            <path d="M15 18l-6-6 6-6" />
                        </svg>
                    </Link>

                    {serviceType !== null && (
                        <span className="absolute top-4 right-4 rounded-full bg-white/90 px-2.5 py-1 text-xs font-medium text-[#1b1b18] shadow-sm backdrop-blur dark:bg-[#0a0a0a]/80 dark:text-[#EDEDEC]">
                            {serviceTypeLabel(serviceType)}
                        </span>
                    )}
                </div>

                <div className="relative -mt-6 rounded-t-3xl bg-[#FDFDFC] px-4 pt-5 pb-4 dark:bg-[#0a0a0a]">
                    {(menu.is_recommended || categories.length > 0) && (
                        <div className="mb-3 flex flex-wrap items-center gap-2">
                            {menu.is_recommended && (
                                <span className="rounded-full bg-[#eff8ff] px-2.5 py-1 text-[11px] font-medium text-[#175cd3] dark:bg-[#102a56] dark:text-[#84caff]">
                                    Recommended
                                </span>
                            )}
                            {categories.map((category) => (
                                <span
                                    key={category.id}
                                    className="rounded-full bg-[#f5f5f4] px-2.5 py-1 text-[11px] font-medium text-[#706f6c] dark:bg-[#1b1b18] dark:text-[#A1A09A]"
                                >
                                    {category.name}
                                </span>
                            ))}
                        </div>
                    )}

                    <h1 className="text-2xl font-semibold tracking-tight">
                        {menu.name}
                    </h1>
                    <p className="mt-1.5 text-xl font-semibold tabular-nums">
                        {formatPrice(menu.price)}
                    </p>

                    <div className="mt-5">
                        <MenuOrderForm
                            menu={menu}
                            variant="customer"
                            onSubmit={handleSubmit}
                            submitLabel={
                                form.processing ? 'Adding...' : 'Add to cart'
                            }
                            disabled={form.processing}
                            errors={{
                                quantity: form.errors.quantity,
                                addon_option_ids: form.errors.addon_option_ids,
                                note: form.errors.note,
                            }}
                        />
                    </div>
                </div>
            </div>
        </>
    );
}
