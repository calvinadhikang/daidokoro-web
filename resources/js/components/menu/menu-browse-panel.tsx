import { Link, router } from '@inertiajs/react';
import { useCallback, useMemo, useState } from 'react';

import { toggleAvailability } from '@/actions/App/Http/Controllers/MenuBrowseController';
import { inputClassName } from '@/components/admin/menu-form';
import { MenuImage } from '@/components/admin/menu-image';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { cn } from '@/lib/utils';
import { useLongPress } from '@/lib/use-long-press';
import {
    prepareMenuList,
    type MenuAvailabilityFilter,
    type MenuCategoryFilter,
    type MenuRecommendedFilter,
} from '@/lib/menu-list';
import type { Menu, MenuCategory } from '@/types/menu';

function formatPrice(price: number): string {
    return price.toLocaleString();
}

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

function MenuCard({
    menu,
    showAvailabilityBadge,
    unavailableLabel,
    href,
    onLongPress,
}: {
    menu: Menu;
    showAvailabilityBadge: boolean;
    unavailableLabel: string;
    href?: string;
    onLongPress?: () => void;
}) {
    const handleLongPress = useCallback(() => {
        onLongPress?.();
    }, [onLongPress]);

    const { handlers: longPressHandlers } = useLongPress({
        onLongPress: handleLongPress,
    });

    const addonSummary =
        menu.addon_groups.length > 0
            ? menu.addon_groups.map((group) => group.name).join(' · ')
            : null;

    const content = (
        <div className="flex items-start gap-3">
            <MenuImage
                src={menu.image}
                alt={menu.name}
                className="h-20 w-20 rounded-md border border-[#e3e3e0] bg-[#FDFDFC] dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
            />
            <div className="min-w-0 flex-1">
                <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-2">
                            <h2 className="font-medium">{menu.name}</h2>
                            {menu.is_recommended && (
                                <span className="shrink-0 rounded-full bg-[#eff8ff] px-2 py-0.5 text-[10px] font-medium text-[#175cd3] dark:bg-[#102a56] dark:text-[#84caff]">
                                    Recommended
                                </span>
                            )}
                        </div>
                        <p className="mt-1 tabular-nums text-sm font-medium">
                            {formatPrice(menu.price)}
                        </p>
                    </div>
                    {showAvailabilityBadge && (
                        <span
                            className={
                                menu.is_available
                                    ? 'shrink-0 rounded-full bg-[#ecfdf3] px-2.5 py-1 text-xs font-medium text-[#027a48] dark:bg-[#053321] dark:text-[#75e0a7]'
                                    : 'shrink-0 rounded-full bg-[#fef3f2] px-2.5 py-1 text-xs font-medium text-[#b42318] dark:bg-[#55160c] dark:text-[#fda29b]'
                            }
                        >
                            {menu.is_available ? 'Available' : unavailableLabel}
                        </span>
                    )}
                </div>

                {addonSummary !== null && (
                    <p className="mt-2 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                        {addonSummary}
                    </p>
                )}
            </div>
        </div>
    );

    const className = cn(
        'block rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]',
        !menu.is_available && 'opacity-70',
        href &&
            'active:bg-[#FDFDFC] dark:active:bg-[#0a0a0a]',
        onLongPress && 'select-none touch-manipulation',
    );

    const interactiveProps = onLongPress ? longPressHandlers : undefined;

    if (href) {
        return (
            <Link href={href} className={className} {...interactiveProps}>
                {content}
            </Link>
        );
    }

    return (
        <article className={className} {...interactiveProps}>
            {content}
        </article>
    );
}

type MenuBrowsePanelProps = {
    menus: Menu[];
    availability: MenuAvailabilityFilter;
    categories?: MenuCategory[];
    showAvailabilityBadge?: boolean;
    enableAvailabilityToggle?: boolean;
    unavailableLabel?: string;
    summaryLabel?: string;
    emptyMessage?: string;
    menuHref?: (menu: Menu) => string | undefined;
};

export function MenuBrowsePanel({
    menus,
    availability,
    categories = [],
    showAvailabilityBadge = false,
    enableAvailabilityToggle = false,
    unavailableLabel = 'Unavailable',
    summaryLabel,
    emptyMessage = 'No menu items available right now.',
    menuHref,
}: MenuBrowsePanelProps) {
    const [search, setSearch] = useState('');
    const [recommended, setRecommended] =
        useState<MenuRecommendedFilter>('all');
    const [category, setCategory] = useState<MenuCategoryFilter>('all');
    const [toggleTarget, setToggleTarget] = useState<Menu | null>(null);
    const [toggleLoading, setToggleLoading] = useState(false);

    const groupedMenus = useMemo(
        () =>
            prepareMenuList(
                menus,
                search,
                availability,
                recommended,
                category,
            ),
        [menus, search, availability, recommended, category],
    );

    const filteredCount = useMemo(
        () => groupedMenus.reduce((sum, group) => sum + group.menus.length, 0),
        [groupedMenus],
    );

    const isFiltering =
        search.trim() !== '' ||
        recommended !== 'all' ||
        category !== 'all';
    const availableCount = menus.filter((menu) => menu.is_available).length;
    const defaultSummary =
        availability === 'available'
            ? `${menus.length} item${menus.length === 1 ? '' : 's'} available`
            : `${availableCount} of ${menus.length} items available`;

    function handleConfirmToggle() {
        if (toggleTarget === null) {
            return;
        }

        setToggleLoading(true);
        router.patch(
            toggleAvailability.url(toggleTarget.id),
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    setToggleLoading(false);
                    setToggleTarget(null);
                },
            },
        );
    }

    return (
        <>
            <ConfirmDialog
                open={toggleTarget !== null}
                title={
                    toggleTarget?.is_available
                        ? 'Mark as unavailable?'
                        : 'Mark as available?'
                }
                description={
                    toggleTarget === null
                        ? ''
                        : `Toggle availability for "${toggleTarget.name}".`
                }
                confirmLabel={
                    toggleTarget?.is_available
                        ? 'Mark unavailable'
                        : 'Mark available'
                }
                loading={toggleLoading}
                onConfirm={handleConfirmToggle}
                onCancel={() => {
                    if (!toggleLoading) {
                        setToggleTarget(null);
                    }
                }}
            />

            <p className="mb-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                {isFiltering
                    ? `${filteredCount} of ${menus.length} items`
                    : (summaryLabel ?? defaultSummary)}
            </p>

            <input
                type="search"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="Search menu..."
                className={`${inputClassName} mb-3`}
            />

            <div className="mb-4 flex flex-wrap gap-2">
                <FilterButton
                    active={recommended === 'all'}
                    label="All"
                    onClick={() => setRecommended('all')}
                />
                <FilterButton
                    active={recommended === 'recommended'}
                    label="Recommended"
                    onClick={() => setRecommended('recommended')}
                />
                {categories.map((item) => (
                    <FilterButton
                        key={item.id}
                        active={category === item.id}
                        label={item.name}
                        onClick={() =>
                            setCategory(
                                category === item.id ? 'all' : item.id,
                            )
                        }
                    />
                ))}
            </div>

            {menus.length === 0 ? (
                <div className="rounded-lg border border-[#e3e3e0] bg-white p-10 text-center dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <p className="text-[#706f6c] dark:text-[#A1A09A]">
                        {emptyMessage}
                    </p>
                </div>
            ) : filteredCount === 0 ? (
                <div className="rounded-lg border border-[#e3e3e0] bg-white p-10 text-center dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <p className="text-[#706f6c] dark:text-[#A1A09A]">
                        No menus match your search.
                    </p>
                </div>
            ) : (
                <div className="space-y-6 pb-8">
                    {groupedMenus.map((group, groupIndex) => (
                        <section key={`${groupIndex}-${group.letter}`}>
                            {group.showUnavailableDivider && (
                                <p className="mb-3 text-xs font-medium tracking-wide text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                    {unavailableLabel}
                                </p>
                            )}
                            <h2 className="mb-3 text-sm font-semibold text-[#706f6c] dark:text-[#A1A09A]">
                                {group.letter}
                            </h2>
                            <ul className="space-y-3">
                                {group.menus.map((menu) => (
                                    <li key={menu.id}>
                                        <MenuCard
                                            menu={menu}
                                            showAvailabilityBadge={
                                                showAvailabilityBadge
                                            }
                                            unavailableLabel={unavailableLabel}
                                            href={menuHref?.(menu)}
                                            onLongPress={
                                                enableAvailabilityToggle
                                                    ? () => setToggleTarget(menu)
                                                    : undefined
                                            }
                                        />
                                    </li>
                                ))}
                            </ul>
                        </section>
                    ))}
                </div>
            )}
        </>
    );
}
