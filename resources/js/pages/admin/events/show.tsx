import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import {
    inputClassName,
    labelClassName,
} from '@/components/admin/menu-form';
import type { ChannelMenu, SalesChannel } from '@/types/sales-channel';

type Props = {
    channel: SalesChannel;
    assignedMenus: ChannelMenu[];
    storeMenus: ChannelMenu[];
};

export default function AdminEventsShow({
    channel,
    assignedMenus,
    storeMenus,
}: Props) {
    const form = useForm({
        name: channel.name,
        starts_at: channel.starts_at ?? '',
        ends_at: channel.ends_at ?? '',
    });
    const [search, setSearch] = useState('');
    const [assignedSearch, setAssignedSearch] = useState('');
    const [archiveOpen, setArchiveOpen] = useState(false);
    const [restoreOpen, setRestoreOpen] = useState(false);
    const pageErrors = usePage().props.errors as Record<string, string>;

    const assignedIds = useMemo(
        () => new Set(assignedMenus.map((menu) => menu.id)),
        [assignedMenus],
    );

    const filteredAssigned = useMemo(() => {
        const query = assignedSearch.trim().toLowerCase();

        if (query === '') {
            return assignedMenus;
        }

        return assignedMenus.filter((menu) =>
            menu.name.toLowerCase().includes(query),
        );
    }, [assignedMenus, assignedSearch]);

    const assignable = useMemo(() => {
        const query = search.trim().toLowerCase();

        return storeMenus.filter((menu) => {
            if (assignedIds.has(menu.id)) {
                return false;
            }

            return query === '' || menu.name.toLowerCase().includes(query);
        });
    }, [assignedIds, search, storeMenus]);

    function handleSubmit(event: React.FormEvent) {
        event.preventDefault();
        form.put(`/admin/events/${channel.id}`);
    }

    function assign(menu: ChannelMenu) {
        router.post(
            `/admin/events/${channel.id}/menus/assign`,
            { menus: [{ menu_id: menu.id }] },
            { preserveScroll: true },
        );
    }

    function unassign(menu: ChannelMenu) {
        router.delete(`/admin/events/${channel.id}/menus/${menu.id}`, {
            preserveScroll: true,
        });
    }

    function saveOverride(menu: ChannelMenu, value: string) {
        const trimmed = value.trim();
        const parsed =
            trimmed === '' ? null : parseInt(trimmed.replace(/\D/g, ''), 10);

        router.post(
            `/admin/events/${channel.id}/menus/${menu.id}`,
            {
                price_override: Number.isFinite(parsed) ? parsed : null,
            },
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title={channel.name} />
            <ConfirmDialog
                open={archiveOpen}
                title="Archive this event?"
                description="Archived events leave cashier mode. Keep ended events if you still need the report."
                confirmLabel="Archive"
                variant="danger"
                onConfirm={() =>
                    router.post(`/admin/events/${channel.id}/archive`)
                }
                onCancel={() => setArchiveOpen(false)}
            />
            <ConfirmDialog
                open={restoreOpen}
                title="Restore this event?"
                description="The event returns to cashier mode. If its dates are still current, the online store may close again."
                confirmLabel="Restore"
                onConfirm={() =>
                    router.post(`/admin/events/${channel.id}/unarchive`)
                }
                onCancel={() => setRestoreOpen(false)}
            />

            <div className="mx-auto max-w-lg px-4 py-4 pb-28">
                <Link
                    href="/admin/events"
                    className="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                >
                    ← Events
                </Link>
                <h1 className="mt-2 text-2xl font-semibold">{channel.name}</h1>
                <p className="mt-1 mb-6 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    {channel.is_archived
                        ? 'Archived'
                        : channel.status === 'active'
                          ? 'Active today'
                          : channel.status}
                    {' · '}
                    assign store menus or create one sold only here.
                </p>
                {channel.is_archived && (
                    <p className="mb-6 rounded-lg border border-[#b2ddff] bg-[#eff8ff] px-3 py-2 text-sm text-[#175cd3] dark:border-[#1849a9] dark:bg-[#102a56] dark:text-[#84caff]">
                        This event is archived and hidden from the cashier.
                    </p>
                )}
                {pageErrors.channel && (
                    <p className="mb-6 rounded-lg border border-[#fda29b] bg-[#fef3f2] px-3 py-2 text-sm text-[#b42318]">
                        {pageErrors.channel}
                    </p>
                )}

                <form onSubmit={handleSubmit} className="mb-8 space-y-4">
                    <div>
                        <label htmlFor="name" className={labelClassName}>
                            Name
                        </label>
                        <input
                            id="name"
                            type="text"
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                            className={inputClassName}
                        />
                        {form.errors.name && (
                            <p className="mt-1 text-xs text-[#b42318]">
                                {form.errors.name}
                            </p>
                        )}
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label htmlFor="starts_at" className={labelClassName}>
                                Starts
                            </label>
                            <input
                                id="starts_at"
                                type="date"
                                value={form.data.starts_at}
                                onChange={(event) =>
                                    form.setData('starts_at', event.target.value)
                                }
                                className={inputClassName}
                            />
                            {form.errors.starts_at && (
                                <p className="mt-1 text-xs text-[#b42318]">
                                    {form.errors.starts_at}
                                </p>
                            )}
                        </div>
                        <div>
                            <label htmlFor="ends_at" className={labelClassName}>
                                Ends
                            </label>
                            <input
                                id="ends_at"
                                type="date"
                                value={form.data.ends_at}
                                onChange={(event) =>
                                    form.setData('ends_at', event.target.value)
                                }
                                className={inputClassName}
                            />
                            {form.errors.ends_at && (
                                <p className="mt-1 text-xs text-[#b42318]">
                                    {form.errors.ends_at}
                                </p>
                            )}
                        </div>
                    </div>
                    <button
                        type="submit"
                        disabled={form.processing}
                        className="w-full rounded-md bg-[#1b1b18] px-4 py-3 text-sm font-medium text-white disabled:opacity-50 dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
                    >
                        {form.processing ? 'Saving...' : 'Save event'}
                    </button>
                </form>

                <section className="mb-8">
                    <h2 className="mb-3 text-base font-semibold">
                        Event menus
                    </h2>
                    <Link
                        href={`/admin/events/${channel.id}/menus/create`}
                        className="mb-3 flex w-full items-center justify-center rounded-md border border-[#e3e3e0] px-4 py-2.5 text-sm font-medium dark:border-[#3E3E3A]"
                    >
                        New menu for this event
                    </Link>
                    {assignedMenus.length === 0 ? (
                        <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            No menus yet. Create one for this event, or assign
                            a store menu below.
                        </p>
                    ) : (
                        <>
                            <input
                                type="search"
                                value={assignedSearch}
                                onChange={(event) =>
                                    setAssignedSearch(event.target.value)
                                }
                                placeholder="Search event menus"
                                className={`${inputClassName} mb-3`}
                            />
                            {filteredAssigned.length === 0 ? (
                                <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                    No matching event menus.
                                </p>
                            ) : (
                                <ul className="max-h-80 space-y-2 overflow-y-auto overscroll-contain">
                                    {filteredAssigned.map((menu) => (
                                <li
                                    key={menu.id}
                                    className="rounded-lg border border-[#e3e3e0] bg-white p-3 dark:border-[#3E3E3A] dark:bg-[#161615]"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <p className="truncate font-medium">
                                                {menu.name}
                                            </p>
                                            <p className="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                                {menu.price_label}
                                            </p>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => unassign(menu)}
                                            className="text-sm font-medium text-[#b42318]"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                    <label className="mt-3 block text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                        Price override (blank = store price)
                                        <input
                                            type="text"
                                            inputMode="numeric"
                                            defaultValue={
                                                menu.price_override === null ||
                                                menu.price_override ===
                                                    undefined
                                                    ? ''
                                                    : String(menu.price_override)
                                            }
                                            onBlur={(event) =>
                                                saveOverride(
                                                    menu,
                                                    event.target.value,
                                                )
                                            }
                                            className={`${inputClassName} mt-1`}
                                        />
                                    </label>
                                </li>
                            ))}
                        </ul>
                            )}
                        </>
                    )}
                </section>

                <section>
                    <h2 className="mb-3 text-base font-semibold">
                        Assign from store
                    </h2>
                    <input
                        type="search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search store menus"
                        className={`${inputClassName} mb-3`}
                    />
                    {assignable.length === 0 ? (
                        <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            No matching store menus.
                        </p>
                    ) : (
                        <ul className="max-h-80 space-y-2 overflow-y-auto overscroll-contain">
                            {assignable.map((menu) => (
                                <li key={menu.id}>
                                    <button
                                        type="button"
                                        onClick={() => assign(menu)}
                                        className="flex w-full items-center justify-between rounded-lg border border-[#e3e3e0] bg-white px-3 py-2.5 text-left dark:border-[#3E3E3A] dark:bg-[#161615]"
                                    >
                                        <span className="min-w-0">
                                            <span className="block truncate font-medium">
                                                {menu.name}
                                            </span>
                                            <span className="mt-0.5 block text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                                {menu.price_label}
                                            </span>
                                        </span>
                                        <span className="text-sm font-medium text-[#175cd3]">
                                            Assign
                                        </span>
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <section className="mt-10 border-t border-[#e3e3e0] pt-6 dark:border-[#3E3E3A]">
                    <h2 className="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">
                        Danger zone
                    </h2>
                    {channel.is_archived ? (
                        <button
                            type="button"
                            onClick={() => setRestoreOpen(true)}
                            className="mt-4 w-full rounded-md border border-[#abefc6] px-4 py-2.5 text-sm font-medium text-[#027a48] dark:border-[#085d3a]"
                        >
                            Restore event
                        </button>
                    ) : (
                        <button
                            type="button"
                            onClick={() => setArchiveOpen(true)}
                            className="mt-4 w-full rounded-md border border-[#fda29b] px-4 py-2.5 text-sm font-medium text-[#b42318] dark:border-[#912018]"
                        >
                            Archive event
                        </button>
                    )}
                </section>
            </div>
        </>
    );
}
