export const customerNavItems = [
    { label: 'Menu', href: '/customer/menu', match: 'menu' as const },
    { label: 'Cart', href: '/customer/cart', match: 'cart' as const },
    { label: 'Order', href: '/customer/order', match: 'order' as const },
] as const;

export type CustomerNavMatch = (typeof customerNavItems)[number]['match'];

type CustomerNavItem = {
    label: string;
    href: string;
    match: CustomerNavMatch;
};

export function isCustomerNavActive(
    url: string,
    item: CustomerNavItem,
): boolean {
    if (item.match === 'menu') {
        return url === item.href || url.startsWith(`${item.href}/`);
    }

    if (item.match === 'cart') {
        return url === item.href || url.startsWith(`${item.href}?`);
    }

    return url === item.href || url.startsWith(`${item.href}?`);
}

export function isCustomerAppUrl(url: string): boolean {
    return url.startsWith('/customer/') && !url.startsWith('/customer/login');
}

export function isCustomerCartUrl(url: string): boolean {
    return url === '/customer/cart' || url.startsWith('/customer/cart?');
}
