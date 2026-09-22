import { Head, Link, useForm } from '@inertiajs/react';

import {
    inputClassName,
    labelClassName,
} from '@/components/admin/menu-form';

export default function AdminEventsCreate() {
    const form = useForm({
        name: '',
        starts_at: '',
        ends_at: '',
    });

    function handleSubmit(event: React.FormEvent) {
        event.preventDefault();
        form.post('/admin/events');
    }

    return (
        <>
            <Head title="New Event" />
            <div className="mx-auto max-w-lg px-4 py-4 pb-28">
                <Link
                    href="/admin/events"
                    className="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                >
                    ← Events
                </Link>
                <h1 className="mt-2 text-2xl font-semibold">New event</h1>
                <p className="mt-1 mb-6 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    Creating an event closes the store for those dates.
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
                            placeholder="Bazaar Senayan"
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
                                onChange={(event) => {
                                    const value = event.target.value;
                                    form.setData((data) => ({
                                        ...data,
                                        starts_at: value,
                                        ends_at:
                                            data.ends_at === '' ||
                                            data.ends_at < value
                                                ? value
                                                : data.ends_at,
                                    }));
                                }}
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
                        {form.processing ? 'Saving...' : 'Create event'}
                    </button>
                </form>
            </div>
        </>
    );
}
