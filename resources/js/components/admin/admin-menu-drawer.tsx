import { Link, usePage } from '@inertiajs/react';
import { useEffect, useId, useRef, useState } from 'react';

import {
    isNavActive,
    secondaryNavItems,
} from '@/lib/admin-nav';
import { cn } from '@/lib/utils';

const DRAWER_TRANSITION_MS = 280;

type AdminMenuDrawerProps = {
    open: boolean;
    onClose: () => void;
};

export function AdminMenuDrawer({ open, onClose }: AdminMenuDrawerProps) {
    const { url } = usePage();
    const titleId = useId();
    const closeRef = useRef<HTMLButtonElement>(null);
    const [mounted, setMounted] = useState(open);
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        if (open) {
            setMounted(true);
            const frame = requestAnimationFrame(() => {
                requestAnimationFrame(() => setVisible(true));
            });

            return () => cancelAnimationFrame(frame);
        }

        setVisible(false);
    }, [open]);

    useEffect(() => {
        if (visible || !mounted || open) {
            return;
        }

        const timer = window.setTimeout(
            () => setMounted(false),
            DRAWER_TRANSITION_MS,
        );

        return () => window.clearTimeout(timer);
    }, [visible, mounted, open]);

    useEffect(() => {
        if (!mounted) {
            return;
        }

        if (visible) {
            closeRef.current?.focus();
        }

        function handleKeyDown(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                onClose();
            }
        }

        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.body.style.overflow = '';
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, [mounted, visible, onClose]);

    if (!mounted) {
        return null;
    }

    return (
        <div className="fixed inset-0 z-50">
            <button
                type="button"
                aria-label="Close menu"
                className={cn(
                    'absolute inset-0 bg-black/40 transition-opacity ease-out motion-reduce:transition-none',
                    visible ? 'opacity-100' : 'opacity-0',
                )}
                style={{ transitionDuration: `${DRAWER_TRANSITION_MS}ms` }}
                onClick={onClose}
            />

            <aside
                role="dialog"
                aria-modal="true"
                aria-labelledby={titleId}
                className={cn(
                    'relative flex h-full w-[min(100%,20rem)] flex-col border-r border-[#e3e3e0] bg-[#FDFDFC] shadow-xl transition-transform ease-out will-change-transform motion-reduce:transition-none dark:border-[#3E3E3A] dark:bg-[#0a0a0a]',
                    visible ? 'translate-x-0' : '-translate-x-full',
                )}
                style={{ transitionDuration: `${DRAWER_TRANSITION_MS}ms` }}
            >
                <div className="flex items-center justify-between border-b border-[#e3e3e0] px-4 py-4 dark:border-[#3E3E3A]">
                    <div>
                        <p
                            id={titleId}
                            className="text-xs font-medium tracking-wide text-[#706f6c] uppercase dark:text-[#A1A09A]"
                        >
                            Admin
                        </p>
                        <p className="mt-0.5 text-sm font-semibold">More</p>
                    </div>
                    <button
                        ref={closeRef}
                        type="button"
                        onClick={onClose}
                        aria-label="Close menu"
                        className="rounded-md p-2 text-[#706f6c] active:bg-[#e3e3e0] dark:text-[#A1A09A] dark:active:bg-[#3E3E3A]"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            className="size-5"
                            aria-hidden="true"
                        >
                            <path d="M6 6l12 12M18 6L6 18" />
                        </svg>
                    </button>
                </div>

                <nav className="flex-1 overflow-y-auto p-3">
                    <ul className="space-y-2">
                        {secondaryNavItems.map((item, index) => {
                            const isActive = isNavActive(url, item);

                            return (
                                <li
                                    key={item.href}
                                    className={cn(
                                        'transition-all ease-out motion-reduce:transition-none',
                                        visible
                                            ? 'translate-x-0 opacity-100'
                                            : '-translate-x-3 opacity-0',
                                    )}
                                    style={{
                                        transitionDuration: `${DRAWER_TRANSITION_MS}ms`,
                                        transitionDelay: visible
                                            ? `${80 + index * 40}ms`
                                            : '0ms',
                                    }}
                                >
                                    <Link
                                        href={item.href}
                                        onClick={onClose}
                                        className={cn(
                                            'block rounded-lg border px-4 py-3',
                                            isActive
                                                ? 'border-[#1b1b18] bg-white dark:border-[#EDEDEC] dark:bg-[#161615]'
                                                : 'border-[#e3e3e0] bg-white active:bg-[#FDFDFC] dark:border-[#3E3E3A] dark:bg-[#161615] dark:active:bg-[#0a0a0a]',
                                        )}
                                    >
                                        <p className="font-medium">
                                            {item.label}
                                        </p>
                                        <p className="mt-0.5 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                            {item.description}
                                        </p>
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                </nav>
            </aside>
        </div>
    );
}

export function AdminMenuButton({
    open,
    onClick,
    active,
}: {
    open: boolean;
    onClick: () => void;
    active: boolean;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-label={open ? 'Close menu' : 'Open menu'}
            aria-expanded={open}
            className={cn(
                'rounded-md p-2 active:bg-[#e3e3e0] dark:active:bg-[#3E3E3A]',
                active
                    ? 'text-[#1b1b18] dark:text-[#EDEDEC]'
                    : 'text-[#706f6c] dark:text-[#A1A09A]',
            )}
        >
            <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                className="size-5"
                aria-hidden="true"
            >
                <path
                    d="M4 7h16"
                    className={cn(
                        'origin-center transition-transform ease-out motion-reduce:transition-none',
                        open && 'translate-y-[5px] rotate-45',
                    )}
                    style={{ transitionDuration: `${DRAWER_TRANSITION_MS}ms` }}
                />
                <path
                    d="M4 12h16"
                    className={cn(
                        'transition-opacity ease-out motion-reduce:transition-none',
                        open && 'opacity-0',
                    )}
                    style={{ transitionDuration: `${DRAWER_TRANSITION_MS}ms` }}
                />
                <path
                    d="M4 17h16"
                    className={cn(
                        'origin-center transition-transform ease-out motion-reduce:transition-none',
                        open && '-translate-y-[5px] -rotate-45',
                    )}
                    style={{ transitionDuration: `${DRAWER_TRANSITION_MS}ms` }}
                />
            </svg>
        </button>
    );
}
