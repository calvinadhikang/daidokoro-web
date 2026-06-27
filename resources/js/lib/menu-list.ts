import type { Menu } from '@/types/menu';

export type MenuAvailabilityFilter = 'all' | 'available' | 'unavailable';
export type MenuRecommendedFilter = 'all' | 'recommended';

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
    recommended: MenuRecommendedFilter,
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

        if (recommended === 'recommended' && !menu.is_recommended) {
            return false;
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
    recommended: MenuRecommendedFilter,
): MenuLetterGroup[] {
    const filtered = filterMenus(menus, search, availability, recommended);

    return groupMenusByLetter(filtered);
}
