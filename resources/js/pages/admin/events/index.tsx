import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import { inputClassName } from '@/components/admin/menu-form';
import type { SalesChannel } from '@/types/sales-channel';

type Props = {
    channels: SalesChannel[];
};

function statusLabel(status: SalesChannel['status']): string {
    if (status === 'active') {
        return 'Active';
    }
    if (status === 'upcoming') {
        return 'Upcoming';
    }
    if (status === 'ended') {
        return 'Ended';
    }

    return 'Store';
}

function dateRange(channel: SalesChannel): string {
    if (!channel.starts_at || !channel.ends_at) {
        return 'Always open';
    }

    if (channel.starts_at === channel.ends_at) {
        return channel.starts_at;
    }

    return `${channel.starts_at} – ${channel.ends_at}`;
}

export default function AdminEventsIndex({ channels }: Props) {
    const [search, setSearch] = useState('');
    const events = useMemo(() => {
        const query = search.trim().toLowerCase();
        const list = channels.filter((channel) => channel.type === 'event');

        if (query === '') {
            return list;
        }

        return list.filter((channel) =>
            channel.name.toLowerCase().includes(query),
        );
    }, [channels, search]);

    return (
        <>
            <Head title="Events" />
            <div className="flex h-[calc(100dvh-7.5rem)] flex-col px-4 py-4">
                <div className="mx-auto flex w-full max-w-lg shrink-0 flex-col">
                    <header className="mb-4">
                        <h1 className="text-2xl font-semibold">Events</h1>
                        <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Bazaar dates reuse store menus with their own prices
                            and reports.
                        </p>
                    </header>

                    <input
                        type="search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search events"
                        className={`${inputClassName} mb-4`}
                    />
                </div>

                <div className="mx-auto min-h-0 w-full max-w-lg flex-1 overflow-y-auto overscroll-contain">
                    {events.length === 0 ? (
                        <div className="rounded-lg border border-[#e3e3e0] bg-white p-10 text-center dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <p className="text-[#706f6c] dark:text-[#A1A09A]">
                                No events yet.
                            </p>
                        </div>
                    ) : (
                        <ul className="space-y-3 pb-24">
                            {events.map((channel) => (
                                <li key={channel.id}>
                                    <Link
                                        href={`/admin/events/${channel.id}`}
                                        className="block rounded-lg border border-[#e3e3e0] bg-white p-4 active:bg-[#FDFDFC] dark:border-[#3E3E3A] dark:bg-[#161615] dark:active:bg-[#0a0a0a]"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="truncate font-medium">
                                                    {channel.name}
                                                </p>
                                                <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                                    {dateRange(channel)}
                                                </p>
                                            </div>
                                            <span className="shrink-0 rounded-full bg-[#fff7ed] px-2.5 py-1 text-xs font-medium text-[#c2410c] dark:bg-[#431407] dark:text-[#fdba74]">
                                                {statusLabel(channel.status)}
                                            </span>
                                        </div>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                <div className="fixed inset-x-0 bottom-16 z-20 mx-auto w-full max-w-lg px-4 pb-[env(safe-area-inset-bottom)]">
                    <Link
                        href="/admin/events/create"
                        className="flex w-full items-center justify-center rounded-md bg-[#1b1b18] px-4 py-3 text-sm font-medium text-white dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
                    >
                        New event
                    </Link>
                </div>
            </div>
        </>
    );
}
