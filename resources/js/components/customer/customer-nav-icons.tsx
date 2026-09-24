import type { ReactElement } from 'react';

import type { CustomerNavMatch } from '@/lib/customer-nav';

type IconProps = {
    className?: string;
};

function MenuIcon({ className }: IconProps) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.75"
            strokeLinecap="round"
            strokeLinejoin="round"
            className={className}
            aria-hidden="true"
        >
            <path d="M4 3v7a2 2 0 0 0 2 2h3a2 2 0 0 0 2-2V3" />
            <path d="M7.5 3v18" />
            <path d="M19 21V9.5A3.5 3.5 0 0 0 15.5 6v7.5c0 .8.7 1.5 1.5 1.5h2Z" />
        </svg>
    );
}

function CartIcon({ className }: IconProps) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.75"
            strokeLinecap="round"
            strokeLinejoin="round"
            className={className}
            aria-hidden="true"
        >
            <path d="M6.5 7h11l.8 11.2a2 2 0 0 1-2 2.1H7.7a2 2 0 0 1-2-2.1L6.5 7Z" />
            <path d="M9 7V5.8A3 3 0 0 1 12 3a3 3 0 0 1 3 2.8V7" />
        </svg>
    );
}

function OrderIcon({ className }: IconProps) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.75"
            strokeLinecap="round"
            strokeLinejoin="round"
            className={className}
            aria-hidden="true"
        >
            <path d="M7 3.5h10a1.5 1.5 0 0 1 1.5 1.5v15.2l-2.2-1.1-2.3 1.1-2-1.1-2 1.1-2.3-1.1-2.2 1.1V5A1.5 1.5 0 0 1 7 3.5Z" />
            <path d="M9 8.5h6" />
            <path d="M9 12h6" />
            <path d="M9 15.5h3.5" />
        </svg>
    );
}

const icons: Record<CustomerNavMatch, (props: IconProps) => ReactElement> = {
    menu: MenuIcon,
    cart: CartIcon,
    order: OrderIcon,
};

export function CustomerNavIcon({
    match,
    className,
}: {
    match: CustomerNavMatch;
    className?: string;
}) {
    const Icon = icons[match];

    return <Icon className={className} />;
}
