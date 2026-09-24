import type { Menu, MenuCategory } from '@/types/menu';

export type MenuAvailabilityFilter = 'all' | 'available' | 'unavailable';
export type MenuBrowseFilter = 'all' | 'recommended' | number;

export type MenuLetterGroup = {
    letter: string;
    menus: Menu[];
    showUnavailableDivider?: boolean;
};

function compareMenuName(a: Menu, b: Menu): number {
    return a.name.localeCompare(b.name, undefined, { sensitivity: 'base' });
}

function getMenuLetter(name: string): string {
    const first = name.trim().charAt(0).toUpperCase();

    return /[A-Z]/.test(first) ? first : '#';
}

export function isHardcodedRecommendedCategory(name: string): boolean {
    return name.trim().toLowerCase() === 'recommended';
}

export function visibleMenuCategories(
    categories: MenuCategory[],
): MenuCategory[] {
    return categories.filter(
        (item) => !isHardcodedRecommendedCategory(item.name),
    );
}

export function sortMenus(menus: Menu[]): Menu[] {
    return [...menus].sort((a, b) => {
        if (a.is_available !== b.is_available) {
            return a.is_available ? -1 : 1;
        }

        return compareMenuName(a, b);
    });
}

export function filterMenus(
    menus: Menu[],
    search: string,
    availability: MenuAvailabilityFilter,
    browseFilter: MenuBrowseFilter = 'all',
): Menu[] {
    const query = search.trim().toLowerCase();

    return menus.filter((menu) => {
        if (query !== '' && !menu.name.toLowerCase().includes(query)) {
            return false;
        }

        if (availability === 'available' && !menu.is_available) {
            return false;
        }

        if (availability === 'unavailable' && menu.is_available) {
            return false;
        }

        if (browseFilter === 'recommended') {
            return menu.is_recommended;
        }

        if (browseFilter !== 'all') {
            const menuCategories = menu.categories ?? [];

            if (!menuCategories.some((item) => item.id === browseFilter)) {
                return false;
            }
        }

        return true;
    });
}

export function groupMenusByLetter(menus: Menu[]): MenuLetterGroup[] {
    const sorted = sortMenus(menus);
    const groups: MenuLetterGroup[] = [];
    let previousWasAvailable = true;

    for (const menu of sorted) {
        const letter = getMenuLetter(menu.name);
        const needsUnavailableDivider =
            previousWasAvailable && !menu.is_available;
        const lastGroup = groups.at(-1);

        if (
            lastGroup &&
            lastGroup.letter === letter &&
            !needsUnavailableDivider
        ) {
            lastGroup.menus.push(menu);
        } else {
            groups.push({
                letter,
                menus: [menu],
                showUnavailableDivider: needsUnavailableDivider,
            });
        }

        previousWasAvailable = menu.is_available;
    }

    return groups;
}

export function prepareMenuList(
    menus: Menu[],
    search: string,
    availability: MenuAvailabilityFilter,
    browseFilter: MenuBrowseFilter = 'all',
): MenuLetterGroup[] {
    const filtered = filterMenus(menus, search, availability, browseFilter);

    return groupMenusByLetter(filtered);
}

export type MenuNamedGroup = {
    title: string;
    menus: Menu[];
};

export function groupMenusForCustomer(
    menus: Menu[],
    categories: MenuCategory[],
): MenuNamedGroup[] {
    const available = [...menus.filter((menu) => menu.is_available)].sort(
        compareMenuName,
    );
    const unavailable = [...menus.filter((menu) => !menu.is_available)].sort(
        compareMenuName,
    );
    const assignedIds = new Set<number>();
    const groups: MenuNamedGroup[] = [];

    const recommended = available.filter((menu) => menu.is_recommended);

    if (recommended.length > 0) {
        groups.push({ title: 'Recommended', menus: recommended });

        for (const menu of recommended) {
            assignedIds.add(menu.id);
        }
    }

    for (const category of visibleMenuCategories(categories)) {
        const items = available.filter(
            (menu) =>
                !assignedIds.has(menu.id) &&
                (menu.categories ?? []).some((item) => item.id === category.id),
        );

        if (items.length === 0) {
            continue;
        }

        groups.push({ title: category.name, menus: items });

        for (const menu of items) {
            assignedIds.add(menu.id);
        }
    }

    const remaining = available.filter((menu) => !assignedIds.has(menu.id));

    if (remaining.length > 0) {
        groups.push({
            title: groups.length === 0 ? 'Menu' : 'More',
            menus: remaining,
        });
    }

    if (unavailable.length > 0) {
        groups.push({ title: 'Sold out', menus: unavailable });
    }

    return groups;
}
