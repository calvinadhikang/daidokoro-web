import { Head, Link } from '@inertiajs/react';
import { useCallback, useState } from 'react';

import { MenuImage } from '@/components/admin/menu-image';
import { MenuImageLightbox } from '@/components/menu/menu-image-lightbox';
import { cn } from '@/lib/utils';
import type { Menu } from '@/types/menu';
import type { NextOpening, StoreStatus } from '@/types/operating-hours';

type Props = {
    menu: Menu;
    storeStatus: StoreStatus;
    nextOpening: NextOpening | null;
    orderUrl: string;
};

function formatPrice(price: number): string {
    return `Rp ${price.toLocaleString('id-ID')}`;
}

export default function MenuShow({
    menu,
    storeStatus,
    nextOpening,
    orderUrl,
}: Props) {
    const [photoOpen, setPhotoOpen] = useState(false);
    const closePhoto = useCallback(() => setPhotoOpen(false), []);
    const categories = menu.categories ?? [];
    const canOrder = storeStatus.is_open && menu.is_available;

    return (
        <>
            <Head title={menu.name} />

            <div className="flex min-h-dvh flex-col bg-[#FBF6F1] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <div className="mx-auto w-full max-w-md flex-1 pb-36">
                    <div className="relative h-72 overflow-hidden bg-[#eceae6] sm:h-80 dark:bg-[#3E3E3A]">
                        <button
                            type="button"
                            onClick={() => setPhotoOpen(true)}
                            className="absolute inset-0 cursor-zoom-in"
                            aria-label={`View ${menu.name} photo`}
                        >
                            <MenuImage
                                src={menu.image}
                                alt={menu.name}
                                className={cn(
                                    'h-full w-full',
                                    !menu.is_available && 'grayscale',
                                )}
                            />
                        </button>

                        <Link
                            href="/menu"
                            aria-label="Back to menu"
                            className="absolute top-4 left-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-[#1b1b18] shadow-sm dark:bg-[#0a0a0a]/80 dark:text-[#EDEDEC]"
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

                        {!menu.is_available && (
                            <span className="absolute right-4 bottom-10 rounded-full bg-black/70 px-2.5 py-1 text-xs font-medium text-white">
                                Sold out
                            </span>
                        )}
                    </div>

                    <div className="relative -mt-6 rounded-t-3xl bg-[#FBF6F1] px-4 pt-6 pb-6 dark:bg-[#0a0a0a]">
                        {(menu.is_recommended || categories.length > 0) && (
                            <div className="mb-3 flex flex-wrap items-center gap-2">
                                {menu.is_recommended && (
                                    <span className="rounded-full bg-[#F6EBE2] px-2.5 py-1 text-[11px] font-medium text-[#A4532E] dark:bg-[#3A2A22] dark:text-[#F3D5C2]">
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

                        <div className="flex items-start justify-between gap-4">
                            <h1 className="text-2xl font-semibold tracking-tight">
                                {menu.name}
                            </h1>
                            <p className="shrink-0 text-right text-lg font-semibold text-[#C56A3E] tabular-nums">
                                {formatPrice(menu.price)}
                                {menu.pricing_type === 'weight_based' && (
                                    <span className="mt-0.5 block text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]">
                                        per 100g
                                    </span>
                                )}
                            </p>
                        </div>

                        <section className="mt-6">
                            <h2 className="text-sm font-semibold tracking-wide text-[#44403c] uppercase dark:text-[#D6D3D1]">
                                Add-ons
                            </h2>

                            {menu.addon_groups.length === 0 ? (
                                <p className="mt-3 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                    Tidak ada add-on untuk menu ini.
                                </p>
                            ) : (
                                <div className="mt-3 space-y-3">
                                    {menu.addon_groups.map((group) => (
                                        <div
                                            key={group.id}
                                            className="rounded-2xl border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]"
                                        >
                                            <div className="mb-3 flex items-start justify-between gap-3">
                                                <p className="text-sm font-medium">
                                                    {group.name}
                                                </p>
                                                <span className="shrink-0 text-[11px] font-medium text-[#706f6c] dark:text-[#A1A09A]">
                                                    {group.is_required
                                                        ? 'Required'
                                                        : 'Optional'}
                                                    {' · '}
                                                    {group.selection_type ===
                                                    'single'
                                                        ? 'Choose 1'
                                                        : 'Choose any'}
                                                </span>
                                            </div>

                                            <ul className="space-y-2">
                                                {group.options.map((option) => (
                                                    <li
                                                        key={option.id}
                                                        className={cn(
                                                            'flex items-center justify-between gap-3 rounded-xl border border-[#e3e3e0] px-3.5 py-3 text-sm dark:border-[#3E3E3A]',
                                                            !option.is_available &&
                                                                'opacity-60',
                                                        )}
                                                    >
                                                        <span>
                                                            {option.name}
                                                            {!option.is_available && (
                                                                <span className="ml-1.5 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                                                    Unavailable
                                                                </span>
                                                            )}
                                                        </span>
                                                        <span className="text-[#706f6c] tabular-nums dark:text-[#A1A09A]">
                                                            {option.price === 0
                                                                ? 'Free'
                                                                : `+${option.price.toLocaleString('id-ID')}`}
                                                        </span>
                                                    </li>
                                                ))}
                                            </ul>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </section>
                    </div>
                </div>

                <footer className="fixed inset-x-0 bottom-0 border-t border-[#E6D5C6] bg-[#FBF6F1]/95 px-4 py-4 backdrop-blur dark:border-[#3E3E3A] dark:bg-[#0a0a0a]/95">
                    <div className="mx-auto max-w-md pb-[env(safe-area-inset-bottom)]">
                        {canOrder ? (
                            <Link
                                href={orderUrl}
                                className="flex w-full items-center justify-center rounded-md bg-[#C56A3E] px-4 py-3 text-sm font-medium text-white active:bg-[#A4532E]"
                            >
                                Pesan sekarang
                            </Link>
                        ) : (
                            <button
                                type="button"
                                disabled
                                className="flex w-full cursor-not-allowed items-center justify-center rounded-md border border-[#d6d3d1] bg-[#F5F0EC] px-4 py-3 text-sm font-medium text-[#44403c] dark:border-[#57534e] dark:bg-[#161615] dark:text-[#D6D3D1]"
                            >
                                Pesan sekarang
                            </button>
                        )}

                        {!storeStatus.is_open && (
                            <div className="mt-3 text-center text-xs text-[#44403c] dark:text-[#D6D3D1]">
                                <p>Toko sedang tutup.</p>
                                <p className="mt-1">
                                    {nextOpening?.message ??
                                        'Jam buka belum tersedia.'}
                                </p>
                            </div>
                        )}

                        {storeStatus.is_open && !menu.is_available && (
                            <p className="mt-3 text-center text-xs text-[#44403c] dark:text-[#D6D3D1]">
                                Menu ini sedang habis.
                            </p>
                        )}
                    </div>
                </footer>
            </div>

            <MenuImageLightbox
                src={menu.image}
                alt={menu.name}
                open={photoOpen}
                onClose={closePhoto}
            />
        </>
    );
}
