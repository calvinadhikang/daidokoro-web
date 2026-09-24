import type { ReactElement } from 'react';

import type { primaryNavItems } from '@/lib/admin-nav';

type IconProps = {
    className?: string;
};

type PrimaryNavMatch = (typeof primaryNavItems)[number]['match'];

function MenusIcon({ className }: IconProps) {
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

function CategoriesIcon({ className }: IconProps) {
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
            <path d="M4 5.5A1.5 1.5 0 0 1 5.5 4h4A1.5 1.5 0 0 1 11 5.5v4A1.5 1.5 0 0 1 9.5 11h-4A1.5 1.5 0 0 1 4 9.5v-4Z" />
            <path d="M13 5.5A1.5 1.5 0 0 1 14.5 4h4A1.5 1.5 0 0 1 20 5.5v4a1.5 1.5 0 0 1-1.5 1.5h-4A1.5 1.5 0 0 1 13 9.5v-4Z" />
            <path d="M4 14.5A1.5 1.5 0 0 1 5.5 13h4a1.5 1.5 0 0 1 1.5 1.5v4A1.5 1.5 0 0 1 9.5 20h-4A1.5 1.5 0 0 1 4 18.5v-4Z" />
            <path d="M13 14.5a1.5 1.5 0 0 1 1.5-1.5h4a1.5 1.5 0 0 1 1.5 1.5v4a1.5 1.5 0 0 1-1.5 1.5h-4a1.5 1.5 0 0 1-1.5-1.5v-4Z" />
        </svg>
    );
}

function TransactionsIcon({ className }: IconProps) {
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

const icons: Record<PrimaryNavMatch, (props: IconProps) => ReactElement> = {
    menus: MenusIcon,
    categories: CategoriesIcon,
    transactions: TransactionsIcon,
};

export function AdminNavIcon({
    match,
    className,
}: {
    match: PrimaryNavMatch;
    className?: string;
}) {
    const Icon = icons[match];

    return <Icon className={className} />;
}
