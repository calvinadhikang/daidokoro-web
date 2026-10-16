import { Head, Link, useForm } from '@inertiajs/react';

import { inputClassName, labelClassName } from '@/components/admin/menu-form';
import { omakaseSessionOptionLabel } from '@/lib/omakase';
import type { OmakaseSessionOption } from '@/types/omakase';

type Props = {
    sessions: OmakaseSessionOption[];
    sourceSessionId: number | null;
    defaults: {
        name: string;
        date: string;
    };
};

export default function AdminOmakaseCreate({
    sessions,
    sourceSessionId,
    defaults,
}: Props) {
    const form = useForm({
        name: defaults.name,
        date: defaults.date,
        source_session_id: sourceSessionId ? String(sourceSessionId) : '',
    });

    const selected = sessions.find(
        (session) => String(session.id) === form.data.source_session_id,
    );
    const copying = selected !== undefined;

    function handleSubmit(event: React.FormEvent) {
        event.preventDefault();
        form.post('/admin/omakase');
    }

    return (
        <>
            <Head title={copying ? 'Duplicate session' : 'New session'} />
            <div className="mx-auto max-w-lg px-4 py-4 pb-28">
                <Link
                    href="/admin/omakase"
                    className="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                >
                    ← Omakase
                </Link>
                <h1 className="mt-2 text-2xl font-semibold">
                    {copying ? 'Duplicate session' : 'New session'}
                </h1>
                <p className="mt-1 mb-6 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    {copying
                        ? 'Creates a new session and copies menus from the one you select. The original session stays unchanged.'
                        : 'Add a service date. You can copy menus from an earlier session, or add them afterwards.'}
                </p>

                <form onSubmit={handleSubmit} className="space-y-4">
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
                            placeholder="Summer Omakase"
                            maxLength={255}
                        />
                        {form.errors.name && (
                            <p className="mt-1 text-xs text-[#b42318]">
                                {form.errors.name}
                            </p>
                        )}
                    </div>

                    <div>
                        <label htmlFor="date" className={labelClassName}>
                            Date
                        </label>
                        <input
                            id="date"
                            type="date"
                            value={form.data.date}
                            onChange={(event) =>
                                form.setData('date', event.target.value)
                            }
                            className={inputClassName}
                        />
                        {form.errors.date && (
                            <p className="mt-1 text-xs text-[#b42318]">
                                {form.errors.date}
                            </p>
                        )}
                    </div>

                    {sessions.length > 0 && (
                        <div>
                            <label
                                htmlFor="source_session_id"
                                className={labelClassName}
                            >
                                Copy menus from
                            </label>
                            <select
                                id="source_session_id"
                                value={form.data.source_session_id}
                                onChange={(event) =>
                                    form.setData(
                                        'source_session_id',
                                        event.target.value,
                                    )
                                }
                                className={inputClassName}
                            >
                                <option value="">Do not copy menus</option>
                                {sessions.map((session) => (
                                    <option
                                        key={session.id}
                                        value={String(session.id)}
                                    >
                                        {omakaseSessionOptionLabel(session)}
                                    </option>
                                ))}
                            </select>
                            {selected && (
                                <p className="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                    {selected.menus_count === 0
                                        ? 'That session has no menus.'
                                        : `${selected.menus_count} ${selected.menus_count === 1 ? 'menu' : 'menus'} will be copied.`}
                                </p>
                            )}
                            {form.errors.source_session_id && (
                                <p className="mt-1 text-xs text-[#b42318]">
                                    {form.errors.source_session_id}
                                </p>
                            )}
                        </div>
                    )}

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="w-full rounded-md bg-[#1b1b18] px-4 py-3 text-sm font-medium text-white disabled:opacity-50 dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
                    >
                        {form.processing
                            ? 'Saving...'
                            : copying
                              ? 'Duplicate session'
                              : 'Create session'}
                    </button>
                </form>
            </div>
        </>
    );
}
