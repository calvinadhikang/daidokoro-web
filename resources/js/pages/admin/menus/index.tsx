import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import {
    create as createMenu,
    show as showMenu,
} from '@/actions/App/Http/Controllers/MenuController';
import { inputClassName } from '@/components/admin/menu-form';
import { MenuImage } from '@/components/admin/menu-image';
import { cn } from '@/lib/utils';
import {
    prepareMenuList,
    type MenuAvailabilityFilter,
    type MenuRecommendedFilter,
} from '@/lib/menu-list';
import type { Menu } from '@/types/menu';

type Props = {
    menus: Menu[];
};

function formatPrice(price: number): string {
    return price.toLocaleString();
}

const availabilityFilters: Array<{
    value: MenuAvailabilityFilter;
    label: string;
}> = [
    { value: 'all', label: 'All' },
    { value: 'available', label: 'Available' },
    { value: 'unavailable', label: 'Unavailable' },
];

const recommendedFilters: Array<{
    value: MenuRecommendedFilter;
    label: string;
}> = [
    { value: 'all', label: 'All' },
    { value: 'recommended', label: 'Recommended' },
];

function FilterButton({
    active,
    label,
    onClick,
}: {
    active: boolean;
    label: string;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'rounded-full border px-3 py-1.5 text-xs font-medium',
                active
                    ? 'border-[#1b1b18] bg-[#1b1b18] text-white dark:border-[#EDEDEC] dark:bg-[#EDEDEC] dark:text-[#1b1b18]'
                    : 'border-[#e3e3e0] text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]',
            )}
        >
            {label}
        </button>
    );
}

function MenuListItem({ menu }: { menu: Menu }) {
    return (
        <Link
            href={showMenu.url(menu.id)}
            className="block rounded-lg border border-[#e3e3e0] bg-white p-4 active:bg-[#FDFDFC] dark:border-[#3E3E3A] dark:bg-[#161615] dark:active:bg-[#0a0a0a]"
        >
            <div className="flex items-start gap-3">
                <MenuImage
                    src={menu.image}
                    alt={menu.name}
                    className="h-16 w-16 rounded-md border border-[#e3e3e0] bg-[#FDFDFC] dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                />
                <div className="min-w-0 flex-1">
                    <div className="flex items-start justify-between gap-3">
                        <div className="min-w-0 flex-1">
                            <div className="flex items-center gap-2">
                                <p className="truncate font-medium">
                                    {menu.name}
                                </p>
                                {menu.is_recommended && (
                                    <span className="shrink-0 rounded-full bg-[#eff8ff] px-2 py-0.5 text-[10px] font-medium text-[#175cd3] dark:bg-[#102a56] dark:text-[#84caff]">
                                        Recommended
                                    </span>
                                )}
                            </div>
                            <p className="mt-1 tabular-nums text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                {formatPrice(menu.price)}
                            </p>
                        </div>
                        <span
                            className={
                                menu.is_available
                                    ? 'shrink-0 rounded-full bg-[#ecfdf3] px-2.5 py-1 text-xs font-medium text-[#027a48] dark:bg-[#053321] dark:text-[#75e0a7]'
                                    : 'shrink-0 rounded-full bg-[#fef3f2] px-2.5 py-1 text-xs font-medium text-[#b42318] dark:bg-[#55160c] dark:text-[#fda29b]'
                            }
                        >
                            {menu.is_available ? 'Available' : 'Unavailable'}
                        </span>
                    </div>

                    {menu.addon_groups.length > 0 && (
                        <p className="mt-3 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                            {menu.addon_groups
                                .map(
                                    (group) =>
                                        `${group.name} (${group.options.length})`,
                                )
                                .join(' · ')}
                        </p>
                    )}
                </div>
            </div>
        </Link>
    );
}

export default function AdminMenusIndex({ menus }: Props) {
    const [search, setSearch] = useState('');
    const [availability, setAvailability] =
        useState<MenuAvailabilityFilter>('all');
    const [recommended, setRecommended] =
        useState<MenuRecommendedFilter>('all');

    const groupedMenus = useMemo(
        () => prepareMenuList(menus, search, availability, recommended),
        [menus, search, availability, recommended],
    );

    const filteredCount = useMemo(
        () => groupedMenus.reduce((sum, group) => sum + group.menus.length, 0),
        [groupedMenus],
    );

    const availableMenus = menus.filter((menu) => menu.is_available);
    const isFiltering =
        search.trim() !== '' ||
        availability !== 'all' ||
        recommended !== 'all';

    return (
        <>
            <Head title="Menu Master" />
            <div className="flex h-[calc(100dvh-7.5rem)] flex-col px-4 py-4">
                <div className="mx-auto flex w-full max-w-lg shrink-0 flex-col">
                    <header className="mb-4">
                        <h1 className="text-2xl font-semibold">Menu Master</h1>
                        <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            {isFiltering
                                ? `${filteredCount} of ${menus.length} menus`
                                : `${availableMenus.length} of ${menus.length} items available`}
                        </p>
                    </header>

                    <input
                        type="search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search menus..."
                        className={`${inputClassName} mb-3`}
                    />

                    <div className="mb-3 space-y-2">
                        <div className="flex flex-wrap gap-2">
                            {availabilityFilters.map((filter) => (
                                <FilterButton
                                    key={filter.value}
                                    active={availability === filter.value}
                                    label={filter.label}
                                    onClick={() =>
                                        setAvailability(filter.value)
                                    }
                                />
                            ))}
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {recommendedFilters.map((filter) => (
                                <FilterButton
                                    key={filter.value}
                                    active={recommended === filter.value}
                                    label={filter.label}
                                    onClick={() => setRecommended(filter.value)}
                                />
                            ))}
                        </div>
                    </div>

                    <Link
                        href={createMenu.url()}
                        className="mb-4 flex w-full items-center justify-center rounded-md bg-[#1b1b18] px-4 py-3 text-sm font-medium text-white dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
                    >
                        Add Menu
                    </Link>
                </div>

                <div className="mx-auto min-h-0 w-full max-w-lg flex-1 overflow-y-auto overscroll-contain">
                    {menus.length === 0 ? (
                        <div className="rounded-lg border border-[#e3e3e0] bg-white p-10 text-center dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <p className="text-[#706f6c] dark:text-[#A1A09A]">
                                No menu items yet.
                            </p>
                        </div>
                    ) : filteredCount === 0 ? (
                        <div className="rounded-lg border border-[#e3e3e0] bg-white p-10 text-center dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <p className="text-[#706f6c] dark:text-[#A1A09A]">
                                No menus match your filters.
                            </p>
                        </div>
                    ) : (
                        <div className="space-y-6 pb-4">
                            {groupedMenus.map((group, groupIndex) => (
                                <section
                                    key={`${groupIndex}-${group.letter}-${group.showUnavailableDivider ? 'unavailable' : 'available'}`}
                                >
                                    {group.showUnavailableDivider && (
                                        <p className="mb-3 text-xs font-medium tracking-wide text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                            Unavailable
                                        </p>
                                    )}
                                    <h2 className="mb-3 text-sm font-semibold text-[#706f6c] dark:text-[#A1A09A]">
                                        {group.letter}
                                    </h2>
                                    <ul className="space-y-3">
                                        {group.menus.map((menu) => (
                                            <li key={menu.id}>
                                                <MenuListItem menu={menu} />
                                            </li>
                                        ))}
                                    </ul>
                                </section>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
