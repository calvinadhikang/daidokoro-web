import { usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';

import {
    AdminMenuButton,
    AdminMenuDrawer,
} from '@/components/admin/admin-menu-drawer';
import { AdminNavbar } from '@/components/admin/admin-navbar';
import { isSecondaryNavActive } from '@/lib/admin-nav';
import { cn } from '@/lib/utils';

type AdminLayoutProps = {
    children: ReactNode;
};

type PageProps = {
    flash: {
        success?: string;
    };
};

export default function AdminLayout({ children }: AdminLayoutProps) {
    const { url, props } = usePage<PageProps>();
    const { flash } = props;
    const [menuOpen, setMenuOpen] = useState(false);
    const secondaryActive = isSecondaryNavActive(url);

    return (
        <div className="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
            <header className="sticky top-0 z-20 border-b border-[#e3e3e0] bg-[#FDFDFC]/95 px-4 py-3 backdrop-blur dark:border-[#3E3E3A] dark:bg-[#0a0a0a]/95">
                <div className="mx-auto flex max-w-lg items-center gap-3">
                    <AdminMenuButton
                        open={menuOpen}
                        onClick={() => setMenuOpen((current) => !current)}
                        active={secondaryActive || menuOpen}
                    />
                    <div className="min-w-0 flex-1">
                        <p className="text-xs font-medium tracking-wide text-[#706f6c] uppercase dark:text-[#A1A09A]">
                            Admin
                        </p>
                        {secondaryActive && (
                            <p className="truncate text-sm font-medium">
                                {url.startsWith('/admin/hours')
                                    ? 'Hours'
                                    : 'History'}
                            </p>
                        )}
                    </div>
                    <span
                        className={cn(
                            'size-9 shrink-0',
                            !secondaryActive && 'invisible',
                        )}
                        aria-hidden="true"
                    />
                </div>
            </header>

            <AdminMenuDrawer
                open={menuOpen}
                onClose={() => setMenuOpen(false)}
            />

            {flash.success && (
                <div className="px-4 pt-4">
                    <div className="mx-auto max-w-lg rounded-lg border border-[#abefc6] bg-[#ecfdf3] px-4 py-3 text-sm text-[#027a48] dark:border-[#053321] dark:bg-[#053321] dark:text-[#75e0a7]">
                        {flash.success}
                    </div>
                </div>
            )}

            <main className="pb-20">{children}</main>

            <AdminNavbar />
        </div>
    );
}
