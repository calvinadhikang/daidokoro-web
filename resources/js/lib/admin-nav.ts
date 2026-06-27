import { edit as hoursEdit } from '@/routes/admin/hours';
import { index as menusIndex } from '@/routes/admin/menus';
import { index as categoriesIndex } from '@/routes/admin/categories';
import { history as transactionHistory, index as transactionsIndex } from '@/routes/admin/transaction';

export const primaryNavItems = [
    { label: 'Menus', href: menusIndex.url(), match: 'menus' as const },
    {
        label: 'Categories',
        href: categoriesIndex.url(),
        match: 'categories' as const,
    },
    {
        label: 'Transactions',
        href: transactionsIndex.url(),
        match: 'transactions' as const,
    },
] as const;

export const secondaryNavItems = [
    {
        label: 'Hours',
        description: 'Operating hours & closures',
        href: hoursEdit.url(),
        match: 'hours' as const,
    },
    {
        label: 'History',
        description: 'Past transactions',
        href: transactionHistory.url(),
        match: 'history' as const,
    },
] as const;

export type NavMatch =
    | (typeof primaryNavItems)[number]['match']
    | (typeof secondaryNavItems)[number]['match'];

type NavItem = {
    label: string;
    href: string;
    match: NavMatch;
};

export function isNavActive(url: string, item: NavItem): boolean {
    if (item.match === 'history') {
        return url === item.href || url.startsWith(`${item.href}?`);
    }

    if (item.match === 'hours') {
        return url === item.href || url.startsWith(`${item.href}?`);
    }

    if (item.match === 'categories') {
        return url === item.href || url.startsWith(`${item.href}/`);
    }

    if (item.match === 'menus') {
        return url === item.href || url.startsWith(`${item.href}/`);
    }

    if (item.match === 'transactions') {
        if (url === item.href || url.startsWith(`${item.href}?`)) {
            return true;
        }

        if (url.startsWith(`${item.href}/create`)) {
            return true;
        }

        return /^\/admin\/transaction\/\d+/.test(url);
    }

    return false;
}

export function isSecondaryNavActive(url: string): boolean {
    return secondaryNavItems.some((item) => isNavActive(url, item));
}
