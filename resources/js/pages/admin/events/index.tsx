import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import { inputClassName } from '@/components/admin/menu-form';
import type { SalesChannel } from '@/types/sales-channel';

type Props = {
    channels: SalesChannel[];
};

function statusLabel(channel: SalesChannel): string {
    if (channel.is_archived) {
        return 'Archived';
    }
    if (channel.status === 'active') {
        return 'Active';
    }
    if (channel.status === 'upcoming') {
        return 'Upcoming';
    }
    if (channel.status === 'ended') {
        return 'Ended';
    }

    return 'Event';
}

function statusClassName(channel: SalesChannel): string {
    if (channel.is_archived) {
        return 'bg-[#f2f4f7] text-[#475467] dark:bg-[#1d2939] dark:text-[#d0d5dd]';
    }
    if (channel.status === 'active') {
        return 'bg-[#ecfdf3] text-[#027a48] dark:bg-[#053321] dark:text-[#75e0a7]';
    }
    if (channel.status === 'upcoming') {
        return 'bg-[#eff8ff] text-[#175cd3] dark:bg-[#102a56] dark:text-[#84caff]';
    }

    return 'bg-[#fff7ed] text-[#c2410c] dark:bg-[#431407] dark:text-[#fdba74]';
}

function dateRange(channel: SalesChannel): string {
    if (!channel.starts_at || !channel.ends_at) {
        return 'No dates';
    }

    if (channel.starts_at === channel.ends_at) {
        return channel.starts_at;
    }

    return `${channel.starts_at} – ${channel.ends_at}`;
}

function isPastOrArchived(channel: SalesChannel): boolean {
    return channel.is_archived || channel.status === 'ended';
}

function EventCard({ channel }: { channel: SalesChannel }) {
    return (
        <Link
            href={`/admin/events/${channel.id}`}
            className="block rounded-lg border border-[#e3e3e0] bg-white p-4 active:bg-[#FDFDFC] dark:border-[#3E3E3A] dark:bg-[#161615] dark:active:bg-[#0a0a0a]"
        >
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="truncate font-medium">{channel.name}</p>
                    <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        {dateRange(channel)}
                    </p>
                </div>
                <span
                    className={`shrink-0 rounded-full px-2.5 py-1 text-xs font-medium ${statusClassName(channel)}`}
                >
                    {statusLabel(channel)}
                </span>
            </div>
        </Link>
    );
}

export default function AdminEventsIndex({ channels }: Props) {
    const [search, setSearch] = useState('');
    const events = useMemo(() => {
        const query = search.trim().toLowerCase();

        if (query === '') {
            return channels;
        }

        return channels.filter((channel) =>
            channel.name.toLowerCase().includes(query),
        );
    }, [channels, search]);

    const current = events.filter((channel) => !isPastOrArchived(channel));
    const past = events.filter((channel) => isPastOrArchived(channel));

    return (
        <>
            <Head title="Events" />
            <div className="flex h-[calc(100dvh-7.5rem)] flex-col px-4 py-4">
                <div className="mx-auto flex w-full max-w-lg shrink-0 flex-col">
                    <header className="mb-4">
                        <h1 className="text-2xl font-semibold">Events</h1>
                        <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Bazaar dates with their own menus, prices, and
                            reports.
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
                                {search.trim() === ''
                                    ? 'No events yet.'
                                    : 'No events match your search.'}
                            </p>
                        </div>
                    ) : (
                        <div className="space-y-6 pb-24">
                            <section>
                                <h2 className="mb-3 text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">
                                    Current ({current.length})
                                </h2>
                                {current.length === 0 ? (
                                    <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                        No upcoming or active events.
                                    </p>
                                ) : (
                                    <ul className="space-y-3">
                                        {current.map((channel) => (
                                            <li key={channel.id}>
                                                <EventCard channel={channel} />
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </section>
                            <section>
                                <h2 className="mb-3 text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">
                                    Ended & archived ({past.length})
                                </h2>
                                {past.length === 0 ? (
                                    <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                        No ended or archived events.
                                    </p>
                                ) : (
                                    <ul className="space-y-3">
                                        {past.map((channel) => (
                                            <li key={channel.id}>
                                                <EventCard channel={channel} />
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </section>
                        </div>
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
