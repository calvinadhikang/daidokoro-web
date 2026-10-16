import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { inputClassName, labelClassName } from '@/components/admin/menu-form';
import { formatOmakaseDate, omakaseSessionOptionLabel } from '@/lib/omakase';
import type {
    OmakaseMenuItem,
    OmakaseSessionDetail,
    OmakaseSessionOption,
} from '@/types/omakase';

type Props = {
    session: OmakaseSessionDetail;
    importSources: OmakaseSessionOption[];
};

function OmakaseMenuRow({
    sessionId,
    index,
    menu,
    onDelete,
}: {
    sessionId: number;
    index: number;
    menu: OmakaseMenuItem;
    onDelete: (menu: OmakaseMenuItem) => void;
}) {
    const [editing, setEditing] = useState(false);
    const form = useForm({ menu_name: menu.name });

    function startEdit() {
        form.setData('menu_name', menu.name);
        form.clearErrors();
        setEditing(true);
    }

    function save(event: React.FormEvent) {
        event.preventDefault();
        form.put(`/admin/omakase/${sessionId}/menus/${menu.id}`, {
            preserveScroll: true,
            onSuccess: () => setEditing(false),
        });
    }

    return (
        <li className="flex items-start gap-3 rounded-lg border border-[#e3e3e0] bg-white p-3 dark:border-[#3E3E3A] dark:bg-[#161615]">
            <span className="mt-2 w-6 shrink-0 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                {index + 1}
            </span>
            {editing ? (
                <form onSubmit={save} className="min-w-0 flex-1 space-y-2">
                    <input
                        id={`menu-${menu.id}-name`}
                        type="text"
                        value={form.data.menu_name}
                        onChange={(event) =>
                            form.setData('menu_name', event.target.value)
                        }
                        className={inputClassName}
                        maxLength={255}
                        aria-label="Menu name"
                    />
                    {form.errors.menu_name && (
                        <p className="text-xs text-[#b42318]">
                            {form.errors.menu_name}
                        </p>
                    )}
                    <div className="flex gap-3">
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="text-sm font-medium disabled:opacity-50"
                        >
                            {form.processing ? 'Saving...' : 'Save'}
                        </button>
                        <button
                            type="button"
                            onClick={() => setEditing(false)}
                            className="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            ) : (
                <>
                    <p className="min-w-0 flex-1 pt-1.5 font-medium break-words">
                        {menu.name}
                    </p>
                    <button
                        type="button"
                        onClick={startEdit}
                        className="pt-1.5 text-sm font-medium"
                    >
                        Edit
                    </button>
                    <button
                        type="button"
                        onClick={() => onDelete(menu)}
                        className="pt-1.5 text-sm font-medium text-[#b42318]"
                    >
                        Delete
                    </button>
                </>
            )}
        </li>
    );
}

function AdminOmakaseShowBody({ session, importSources }: Props) {
    const sessionForm = useForm({
        name: session.name,
        date: session.date,
    });
    const addForm = useForm({ menu_name: '' });
    const importForm = useForm({ source_session_id: '' });
    const [deleteSessionOpen, setDeleteSessionOpen] = useState(false);
    const [deleteSessionLoading, setDeleteSessionLoading] = useState(false);
    const [menuToDelete, setMenuToDelete] = useState<OmakaseMenuItem | null>(
        null,
    );
    const [deleteMenuLoading, setDeleteMenuLoading] = useState(false);

    const selectedSource = importSources.find(
        (source) => String(source.id) === importForm.data.source_session_id,
    );

    function saveSession(event: React.FormEvent) {
        event.preventDefault();
        sessionForm.put(`/admin/omakase/${session.id}`);
    }

    function addMenu(event: React.FormEvent) {
        event.preventDefault();
        addForm.post(`/admin/omakase/${session.id}/menus`, {
            preserveScroll: true,
            onSuccess: () => addForm.reset('menu_name'),
        });
    }

    function importMenus(event: React.FormEvent) {
        event.preventDefault();
        importForm.post(`/admin/omakase/${session.id}/import`, {
            preserveScroll: true,
            onSuccess: () => importForm.reset('source_session_id'),
        });
    }

    function confirmDeleteSession() {
        setDeleteSessionLoading(true);
        router.delete(`/admin/omakase/${session.id}`, {
            onFinish: () => {
                setDeleteSessionLoading(false);
                setDeleteSessionOpen(false);
            },
        });
    }

    function confirmDeleteMenu() {
        if (!menuToDelete) {
            return;
        }

        setDeleteMenuLoading(true);
        router.delete(`/admin/omakase/${session.id}/menus/${menuToDelete.id}`, {
            preserveScroll: true,
            onFinish: () => {
                setDeleteMenuLoading(false);
                setMenuToDelete(null);
            },
        });
    }

    return (
        <>
            <Head title={session.name} />
            <ConfirmDialog
                open={deleteSessionOpen}
                title="Delete this session?"
                description={`Delete "${session.name}" and its menus? This cannot be undone.`}
                confirmLabel="Delete"
                variant="danger"
                loading={deleteSessionLoading}
                onConfirm={confirmDeleteSession}
                onCancel={() => setDeleteSessionOpen(false)}
            />
            <ConfirmDialog
                open={menuToDelete !== null}
                title="Delete this menu?"
                description={
                    menuToDelete
                        ? `Delete "${menuToDelete.name}" from this session?`
                        : ''
                }
                confirmLabel="Delete"
                variant="danger"
                loading={deleteMenuLoading}
                onConfirm={confirmDeleteMenu}
                onCancel={() => setMenuToDelete(null)}
            />

            <div className="mx-auto max-w-lg px-4 py-4 pb-28">
                <Link
                    href="/admin/omakase"
                    className="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                >
                    ← Omakase
                </Link>
                <h1 className="mt-2 text-2xl font-semibold">{session.name}</h1>
                <p className="mt-1 mb-6 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    {formatOmakaseDate(session.date)}
                    {' · '}
                    {session.menus.length}{' '}
                    {session.menus.length === 1 ? 'menu' : 'menus'}
                </p>

                <Link
                    href={`/admin/omakase/create?from=${session.id}`}
                    className="mb-8 flex w-full items-center justify-center rounded-md border border-[#e3e3e0] px-4 py-2.5 text-sm font-medium dark:border-[#3E3E3A]"
                >
                    Duplicate this session
                </Link>

                <form onSubmit={saveSession} className="mb-8 space-y-4">
                    <div>
                        <label
                            htmlFor="session-name"
                            className={labelClassName}
                        >
                            Name
                        </label>
                        <input
                            id="session-name"
                            type="text"
                            value={sessionForm.data.name}
                            onChange={(event) =>
                                sessionForm.setData('name', event.target.value)
                            }
                            className={inputClassName}
                            maxLength={255}
                        />
                        {sessionForm.errors.name && (
                            <p className="mt-1 text-xs text-[#b42318]">
                                {sessionForm.errors.name}
                            </p>
                        )}
                    </div>
                    <div>
                        <label
                            htmlFor="session-date"
                            className={labelClassName}
                        >
                            Date
                        </label>
                        <input
                            id="session-date"
                            type="date"
                            value={sessionForm.data.date}
                            onChange={(event) =>
                                sessionForm.setData('date', event.target.value)
                            }
                            className={inputClassName}
                        />
                        {sessionForm.errors.date && (
                            <p className="mt-1 text-xs text-[#b42318]">
                                {sessionForm.errors.date}
                            </p>
                        )}
                    </div>
                    <button
                        type="submit"
                        disabled={sessionForm.processing}
                        className="w-full rounded-md bg-[#1b1b18] px-4 py-3 text-sm font-medium text-white disabled:opacity-50 dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
                    >
                        {sessionForm.processing ? 'Saving...' : 'Save session'}
                    </button>
                </form>

                <section className="mb-8">
                    <h2 className="mb-1 text-base font-semibold">Menus</h2>
                    <p className="mb-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        Served in this order.
                    </p>
                    <form onSubmit={addMenu} className="mb-4 space-y-2">
                        <label
                            htmlFor="new-menu-name"
                            className={labelClassName}
                        >
                            Add menu
                        </label>
                        <input
                            id="new-menu-name"
                            type="text"
                            value={addForm.data.menu_name}
                            onChange={(event) =>
                                addForm.setData('menu_name', event.target.value)
                            }
                            className={inputClassName}
                            placeholder="OTORO BLUE FIN"
                            maxLength={255}
                        />
                        {addForm.errors.menu_name && (
                            <p className="text-xs text-[#b42318]">
                                {addForm.errors.menu_name}
                            </p>
                        )}
                        <button
                            type="submit"
                            disabled={addForm.processing}
                            className="w-full rounded-md border border-[#e3e3e0] px-4 py-2.5 text-sm font-medium disabled:opacity-50 dark:border-[#3E3E3A]"
                        >
                            {addForm.processing ? 'Adding...' : 'Add menu'}
                        </button>
                    </form>
                    {session.menus.length === 0 ? (
                        <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            No menus yet. Add one, or import from another
                            session below.
                        </p>
                    ) : (
                        <ul className="space-y-2">
                            {session.menus.map((menu, index) => (
                                <OmakaseMenuRow
                                    key={menu.id}
                                    sessionId={session.id}
                                    index={index}
                                    menu={menu}
                                    onDelete={setMenuToDelete}
                                />
                            ))}
                        </ul>
                    )}
                </section>

                <section className="mb-8">
                    <h2 className="mb-1 text-base font-semibold">
                        Import menus
                    </h2>
                    <p className="mb-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        Copy menus from another session. Names already on this
                        session are skipped.
                    </p>
                    {importSources.length === 0 ? (
                        <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            No other sessions to import from.
                        </p>
                    ) : (
                        <form onSubmit={importMenus} className="space-y-3">
                            <div>
                                <label
                                    htmlFor="import-source"
                                    className={labelClassName}
                                >
                                    Session
                                </label>
                                <select
                                    id="import-source"
                                    value={importForm.data.source_session_id}
                                    onChange={(event) =>
                                        importForm.setData(
                                            'source_session_id',
                                            event.target.value,
                                        )
                                    }
                                    className={inputClassName}
                                >
                                    <option value="">Choose a session</option>
                                    {importSources.map((source) => (
                                        <option
                                            key={source.id}
                                            value={String(source.id)}
                                        >
                                            {omakaseSessionOptionLabel(source)}
                                        </option>
                                    ))}
                                </select>
                                {selectedSource && (
                                    <p className="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                        {selectedSource.menus_count === 0
                                            ? 'That session has no menus.'
                                            : `${selectedSource.menus_count} ${selectedSource.menus_count === 1 ? 'menu' : 'menus'} available. Matching names are skipped.`}
                                    </p>
                                )}
                                {importForm.errors.source_session_id && (
                                    <p className="mt-1 text-xs text-[#b42318]">
                                        {importForm.errors.source_session_id}
                                    </p>
                                )}
                            </div>
                            <button
                                type="submit"
                                disabled={
                                    importForm.processing ||
                                    importForm.data.source_session_id === ''
                                }
                                className="w-full rounded-md bg-[#1b1b18] px-4 py-3 text-sm font-medium text-white disabled:opacity-50 dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
                            >
                                {importForm.processing
                                    ? 'Importing...'
                                    : 'Import menus'}
                            </button>
                        </form>
                    )}
                </section>

                <button
                    type="button"
                    onClick={() => setDeleteSessionOpen(true)}
                    className="w-full rounded-md border border-[#fda29b] px-4 py-3 text-sm font-medium text-[#b42318] dark:border-[#912018] dark:text-[#fda29b]"
                >
                    Delete session
                </button>
            </div>
        </>
    );
}

export default function AdminOmakaseShow(props: Props) {
    return <AdminOmakaseShowBody key={props.session.id} {...props} />;
}
