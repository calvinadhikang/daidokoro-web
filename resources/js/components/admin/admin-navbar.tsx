import { Link, usePage } from '@inertiajs/react';

import { isNavActive, primaryNavItems } from '@/lib/admin-nav';
import { cn } from '@/lib/utils';

export function AdminNavbar() {
    const { url } = usePage();

    return (
        <nav className="fixed inset-x-0 bottom-0 z-30 border-t border-[#e3e3e0] bg-[#FDFDFC]/95 backdrop-blur dark:border-[#3E3E3A] dark:bg-[#0a0a0a]/95">
            <div className="mx-auto flex max-w-lg">
                {primaryNavItems.map((item) => {
                    const isActive = isNavActive(url, item);

                    return (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={cn(
                                'flex flex-1 flex-col items-center gap-1 px-2 py-3 text-xs font-medium',
                                isActive
                                    ? 'text-[#1b1b18] dark:text-[#EDEDEC]'
                                    : 'text-[#706f6c] dark:text-[#A1A09A]',
                            )}
                        >
                            <span
                                className={cn(
                                    'h-1 w-8 rounded-full',
                                    isActive
                                        ? 'bg-[#1b1b18] dark:bg-[#EDEDEC]'
                                        : 'bg-transparent',
                                )}
                            />
                            {item.label}
                        </Link>
                    );
                })}
            </div>
        </nav>
    );
}
