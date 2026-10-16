import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import { inputClassName } from '@/components/admin/menu-form';
import { formatOmakaseDate } from '@/lib/omakase';
import type { OmakaseSessionSummary } from '@/types/omakase';

type Props = {
    today: string;
    sessions: OmakaseSessionSummary[];
};

function SessionCard({
    session,
    today,
}: {
    session: OmakaseSessionSummary;
    today: string;
}) {
    const preview = session.menu_preview.join(', ');

    return (
        <Link
            href={`/admin/omakase/${session.id}`}
            className="block rounded-lg border border-[#e3e3e0] bg-white p-4 active:bg-[#FDFDFC] dark:border-[#3E3E3A] dark:bg-[#161615] dark:active:bg-[#0a0a0a]"
        >
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0 flex-1">
                    <p className="truncate font-medium">{session.name}</p>
                    <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        {formatOmakaseDate(session.date)}
                    </p>
                    {session.menus_count === 0 ? (
                        <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            No menus yet
                        </p>
                    ) : (
                        <p className="mt-1 truncate text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            {preview}
                            {session.menus_count >
                                session.menu_preview.length && '…'}
                        </p>
                    )}
                </div>
                <div className="flex shrink-0 flex-col items-end gap-2">
                    {session.date === today && (
                        <span className="rounded-full bg-[#ecfdf3] px-2.5 py-1 text-xs font-medium text-[#027a48] dark:bg-[#053321] dark:text-[#75e0a7]">
                            Today
                        </span>
                    )}
                    <span className="rounded-full bg-[#eff8ff] px-2.5 py-1 text-xs font-medium text-[#175cd3] dark:bg-[#102a56] dark:text-[#84caff]">
                        {session.menus_count}{' '}
                        {session.menus_count === 1 ? 'menu' : 'menus'}
                    </span>
                </div>
            </div>
        </Link>
    );
}

export default function AdminOmakaseIndex({ today, sessions }: Props) {
    const [search, setSearch] = useState('');

    const filtered = useMemo(() => {
        const query = search.trim().toLowerCase();

        if (query === '') {
            return sessions;
        }

        return sessions.filter((session) =>
            session.name.toLowerCase().includes(query),
        );
    }, [search, sessions]);

    const upcoming = filtered
        .filter((session) => session.date >= today)
        .sort((a, b) => a.date.localeCompare(b.date) || b.id - a.id);
    const past = filtered
        .filter((session) => session.date < today)
        .sort((a, b) => b.date.localeCompare(a.date) || b.id - a.id);

    return (
        <>
            <Head title="Omakase" />
            <div className="flex h-[calc(100dvh-7.5rem)] flex-col px-4 py-4">
                <div className="mx-auto flex w-full max-w-lg shrink-0 flex-col">
                    <header className="mb-4">
                        <h1 className="text-2xl font-semibold">Omakase</h1>
                        <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            {search.trim() !== ''
                                ? `${filtered.length} of ${sessions.length} sessions`
                                : `${sessions.length} ${sessions.length === 1 ? 'session' : 'sessions'}`}
                        </p>
                    </header>

                    <input
                        type="search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search sessions"
                        className={`${inputClassName} mb-4`}
                    />
                </div>

                <div className="mx-auto min-h-0 w-full max-w-lg flex-1 overflow-y-auto overscroll-contain">
                    {filtered.length === 0 ? (
                        <div className="rounded-lg border border-[#e3e3e0] bg-white p-10 text-center dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <p className="text-[#706f6c] dark:text-[#A1A09A]">
                                {search.trim() === ''
                                    ? 'No omakase sessions yet.'
                                    : 'No sessions match your search.'}
                            </p>
                        </div>
                    ) : (
                        <div className="space-y-6 pb-24">
                            <section>
                                <h2 className="mb-3 text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">
                                    Upcoming & today ({upcoming.length})
                                </h2>
                                {upcoming.length === 0 ? (
                                    <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                        No upcoming sessions.
                                    </p>
                                ) : (
                                    <ul className="space-y-3">
                                        {upcoming.map((session) => (
                                            <li key={session.id}>
                                                <SessionCard
                                                    session={session}
                                                    today={today}
                                                />
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </section>
                            <section>
                                <h2 className="mb-3 text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">
                                    Past ({past.length})
                                </h2>
                                {past.length === 0 ? (
                                    <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                        No past sessions.
                                    </p>
                                ) : (
                                    <ul className="space-y-3">
                                        {past.map((session) => (
                                            <li key={session.id}>
                                                <SessionCard
                                                    session={session}
                                                    today={today}
                                                />
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
                        href="/admin/omakase/create"
                        className="flex w-full items-center justify-center rounded-md bg-[#1b1b18] px-4 py-3 text-sm font-medium text-white dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
                    >
                        New session
                    </Link>
                </div>
            </div>
        </>
    );
}
