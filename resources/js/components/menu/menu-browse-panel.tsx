import { Link, router } from '@inertiajs/react';
import { useCallback, useMemo, useState } from 'react';

import { toggleAvailability } from '@/actions/App/Http/Controllers/MenuBrowseController';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { inputClassName } from '@/components/admin/menu-form';
import { MenuImage } from '@/components/admin/menu-image';
import { CategoryFilterRow } from '@/components/menu/category-filter-row';
import {
    filterMenus,
    groupMenusForCustomer,
    prepareMenuList,
    sortMenus
    
    
} from '@/lib/menu-list';
import type {MenuAvailabilityFilter, MenuBrowseFilter} from '@/lib/menu-list';
import { useLongPress } from '@/lib/use-long-press';
import { cn } from '@/lib/utils';
import type { Menu, MenuCategory } from '@/types/menu';

function formatPrice(price: number): string {
    return price.toLocaleString();
}

function MenuCard({
    menu,
    showAvailabilityBadge,
    unavailableLabel,
    href,
    onLongPress,
    variant = 'default',
}: {
    menu: Menu;
    showAvailabilityBadge: boolean;
    unavailableLabel: string;
    href?: string;
    onLongPress?: () => void;
    variant?: 'default' | 'customer';
}) {
    const handleLongPress = useCallback(() => {
        onLongPress?.();
    }, [onLongPress]);

    const { handlers: longPressHandlers } = useLongPress({
        onLongPress: handleLongPress,
    });

    const isCustomer = variant === 'customer';
    const addonSummary =
        menu.addon_groups.length > 0
            ? menu.addon_groups.map((group) => group.name).join(' · ')
            : null;

    const content = isCustomer ? (
        <div className="flex items-stretch gap-3.5">
            <div className="relative h-24 w-24 shrink-0 overflow-hidden rounded-2xl bg-[#e3e3e0] dark:bg-[#3E3E3A]">
                <MenuImage
                    src={menu.image}
                    alt={menu.name}
                    className={cn(
                        'h-full w-full',
                        !menu.is_available && 'grayscale',
                    )}
                />
                {!menu.is_available && (
                    <span className="absolute inset-x-0 bottom-0 bg-black/55 px-1.5 py-1 text-center text-[10px] font-medium text-white">
                        {unavailableLabel}
                    </span>
                )}
            </div>
            <div className="flex min-w-0 flex-1 flex-col">
                <h2 className="line-clamp-2 text-base font-semibold tracking-tight">
                    {menu.name}
                </h2>
                <div className="mt-auto flex items-end justify-between gap-3 pt-2">
                    <p className="text-sm font-semibold tabular-nums">
                        {formatPrice(menu.price)}
                    </p>
                    {href ? (
                        <span
                            className="flex h-8 w-8 items-center justify-center rounded-full bg-[#1b1b18] text-white dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
                            aria-hidden="true"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2"
                                strokeLinecap="round"
                                className="size-4"
                            >
                                <path d="M12 5v14M5 12h14" />
                            </svg>
                        </span>
                    ) : null}
                </div>
            </div>
        </div>
    ) : (
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
                        <p className="mt-1 text-sm font-medium tabular-nums">
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
        'block border bg-white dark:border-[#3E3E3A] dark:bg-[#161615]',
        isCustomer
            ? 'rounded-2xl border-[#eee] p-3 dark:border-[#3E3E3A]'
            : 'rounded-lg border-[#e3e3e0] p-4',
        !menu.is_available && !isCustomer && 'opacity-70',
        href && 'active:bg-[#FDFDFC] dark:active:bg-[#0a0a0a]',
        onLongPress && 'touch-manipulation select-none',
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
    stickyFilters?: boolean;
    unavailableLabel?: string;
    summaryLabel?: string;
    emptyMessage?: string;
    menuHref?: (menu: Menu) => string | undefined;
    variant?: 'default' | 'customer';
};

export function MenuBrowsePanel({
    menus,
    availability,
    categories = [],
    showAvailabilityBadge = false,
    enableAvailabilityToggle = false,
    stickyFilters = false,
    unavailableLabel = 'Unavailable',
    summaryLabel,
    emptyMessage = 'No menu items available right now.',
    menuHref,
    variant = 'default',
}: MenuBrowsePanelProps) {
    const [search, setSearch] = useState('');
    const [browseFilter, setBrowseFilter] = useState<MenuBrowseFilter>('all');
    const [toggleTarget, setToggleTarget] = useState<Menu | null>(null);
    const [toggleLoading, setToggleLoading] = useState(false);

    const isCustomer = variant === 'customer';
    const groupedMenus = useMemo(() => {
        if (isCustomer) {
            const filtered = filterMenus(
                menus,
                search,
                availability,
                browseFilter,
            );
            const isNarrowed = search.trim() !== '' || browseFilter !== 'all';

            if (isNarrowed) {
                return [
                    {
                        key: 'results',
                        heading: null as string | null,
                        menus: sortMenus(filtered),
                        showUnavailableDivider: false,
                    },
                ];
            }

            return groupMenusForCustomer(filtered, categories).map((group) => ({
                key: group.title,
                heading: group.title,
                menus: group.menus,
                showUnavailableDivider: false,
            }));
        }

        return prepareMenuList(menus, search, availability, browseFilter).map(
            (group, groupIndex) => ({
                key: `${groupIndex}-${group.letter}`,
                heading: group.letter,
                menus: group.menus,
                showUnavailableDivider: Boolean(group.showUnavailableDivider),
            }),
        );
    }, [availability, browseFilter, categories, isCustomer, menus, search]);

    const filteredCount = useMemo(
        () => groupedMenus.reduce((sum, group) => sum + group.menus.length, 0),
        [groupedMenus],
    );

    const isFiltering = search.trim() !== '' || browseFilter !== 'all';
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

    const filters = (
        <>
            {(!isCustomer || isFiltering) && (
                <p
                    className={cn(
                        'text-sm text-[#706f6c] dark:text-[#A1A09A]',
                        isCustomer ? 'mb-3' : 'mb-4',
                    )}
                >
                    {isFiltering
                        ? `${filteredCount} of ${menus.length} items`
                        : (summaryLabel ?? defaultSummary)}
                </p>
            )}

            {isCustomer ? (
                <div className="relative mb-3">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="1.75"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-[#706f6c] dark:text-[#A1A09A]"
                        aria-hidden="true"
                    >
                        <circle cx="11" cy="11" r="7" />
                        <path d="M20 20l-3-3" />
                    </svg>
                    <input
                        type="search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search menu..."
                        className={`${inputClassName} rounded-xl py-2.5 pr-3 pl-10`}
                    />
                </div>
            ) : (
                <input
                    type="search"
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder="Search menu..."
                    className={`${inputClassName} mb-3`}
                />
            )}

            <CategoryFilterRow
                categories={categories}
                value={browseFilter}
                onChange={setBrowseFilter}
                className="mb-4"
            />
        </>
    );

    const emptyClassName = cn(
        'border border-[#e3e3e0] bg-white p-10 text-center dark:border-[#3E3E3A] dark:bg-[#161615]',
        isCustomer ? 'rounded-2xl' : 'rounded-lg',
    );

    const list =
        menus.length === 0 ? (
            <div className={emptyClassName}>
                <p className="text-[#706f6c] dark:text-[#A1A09A]">
                    {emptyMessage}
                </p>
            </div>
        ) : filteredCount === 0 ? (
            <div className={emptyClassName}>
                <p className="text-[#706f6c] dark:text-[#A1A09A]">
                    No menus match your search.
                </p>
            </div>
        ) : (
            <div
                className={cn(
                    isCustomer ? 'space-y-7' : 'space-y-6',
                    stickyFilters ? 'pb-4' : 'pb-8',
                )}
            >
                {groupedMenus.map((group) => (
                    <section key={group.key}>
                        {group.showUnavailableDivider && (
                            <p className="mb-3 text-xs font-medium tracking-wide text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                {unavailableLabel}
                            </p>
                        )}
                        {group.heading !== null && (
                            <h2
                                className={cn(
                                    'mb-3 font-semibold',
                                    isCustomer
                                        ? 'text-base tracking-tight'
                                        : 'text-sm text-[#706f6c] dark:text-[#A1A09A]',
                                )}
                            >
                                {group.heading}
                            </h2>
                        )}
                        <ul
                            className={isCustomer ? 'space-y-2.5' : 'space-y-3'}
                        >
                            {group.menus.map((menu) => (
                                <li key={menu.id}>
                                    <MenuCard
                                        menu={menu}
                                        variant={variant}
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
        );

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

            {stickyFilters ? (
                <div className="flex min-h-0 min-w-0 flex-1 flex-col">
                    <div className="min-w-0 shrink-0">{filters}</div>
                    <div className="min-h-0 flex-1 overflow-y-auto overscroll-contain">
                        {list}
                    </div>
                </div>
            ) : (
                <>
                    {filters}
                    {list}
                </>
            )}
        </>
    );
}
