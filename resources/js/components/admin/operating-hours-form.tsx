import type { useForm } from '@inertiajs/react';

import {
    inputClassName,
    labelClassName,
} from '@/components/admin/menu-form';
import { cn } from '@/lib/utils';
import {
    DAY_NAMES,
    type OperatingHourDayForm,
    type OperatingHoursForm,
    type StoreStatus,
} from '@/types/operating-hours';

type InertiaOperatingHoursForm = ReturnType<typeof useForm<OperatingHoursForm>>;

function FieldError({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return <p className="mt-1 text-xs text-[#b42318]">{message}</p>;
}

type OperatingHoursFormFieldsProps = {
    form: InertiaOperatingHoursForm;
    formId: string;
    onSubmit: (event: React.FormEvent) => void;
};

function updateDay(
    form: InertiaOperatingHoursForm,
    dayIndex: number,
    updates: Partial<OperatingHourDayForm>,
) {
    const days = [...form.data.days];
    days[dayIndex] = { ...days[dayIndex], ...updates };
    form.setData('days', days);
}

function DayRow({
    day,
    dayIndex,
    form,
}: {
    day: OperatingHourDayForm;
    dayIndex: number;
    form: InertiaOperatingHoursForm;
}) {
    const dayName = DAY_NAMES[day.day_of_week];

    return (
        <section className="rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
            <div className="flex items-center justify-between gap-3">
                <h3 className="font-medium">{dayName}</h3>
                <label className="flex items-center gap-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    <input
                        type="checkbox"
                        checked={day.is_closed}
                        onChange={(event) =>
                            updateDay(form, dayIndex, {
                                is_closed: event.target.checked,
                            })
                        }
                        className="size-4 rounded border-[#e3e3e0] dark:border-[#3E3E3A]"
                    />
                    Closed
                </label>
            </div>

            {!day.is_closed && (
                <div className="mt-4 space-y-4">
                    <div>
                        <p className="mb-2 text-xs font-medium tracking-wide text-[#706f6c] uppercase dark:text-[#A1A09A]">
                            Session 1
                        </p>
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label
                                    htmlFor={`day-${day.day_of_week}-s1-start`}
                                    className={labelClassName}
                                >
                                    Opens
                                </label>
                                <input
                                    id={`day-${day.day_of_week}-s1-start`}
                                    type="time"
                                    value={day.session_1_starts_at}
                                    onChange={(event) =>
                                        updateDay(form, dayIndex, {
                                            session_1_starts_at:
                                                event.target.value,
                                        })
                                    }
                                    className={inputClassName}
                                />
                                <FieldError
                                    message={
                                        form.errors[
                                            `days.${dayIndex}.session_1_starts_at`
                                        ]
                                    }
                                />
                            </div>
                            <div>
                                <label
                                    htmlFor={`day-${day.day_of_week}-s1-end`}
                                    className={labelClassName}
                                >
                                    Closes
                                </label>
                                <input
                                    id={`day-${day.day_of_week}-s1-end`}
                                    type="time"
                                    value={day.session_1_ends_at}
                                    onChange={(event) =>
                                        updateDay(form, dayIndex, {
                                            session_1_ends_at:
                                                event.target.value,
                                        })
                                    }
                                    className={inputClassName}
                                />
                                <FieldError
                                    message={
                                        form.errors[
                                            `days.${dayIndex}.session_1_ends_at`
                                        ]
                                    }
                                />
                            </div>
                        </div>
                    </div>

                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={day.has_session_2}
                            onChange={(event) =>
                                updateDay(form, dayIndex, {
                                    has_session_2: event.target.checked,
                                    session_2_starts_at: event.target.checked
                                        ? day.session_2_starts_at
                                        : '',
                                    session_2_ends_at: event.target.checked
                                        ? day.session_2_ends_at
                                        : '',
                                })
                            }
                            className="size-4 rounded border-[#e3e3e0] dark:border-[#3E3E3A]"
                        />
                        Add second session
                    </label>

                    {day.has_session_2 && (
                        <div>
                            <p className="mb-2 text-xs font-medium tracking-wide text-[#706f6c] uppercase dark:text-[#A1A09A]">
                                Session 2
                            </p>
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label
                                        htmlFor={`day-${day.day_of_week}-s2-start`}
                                        className={labelClassName}
                                    >
                                        Opens
                                    </label>
                                    <input
                                        id={`day-${day.day_of_week}-s2-start`}
                                        type="time"
                                        value={day.session_2_starts_at}
                                        onChange={(event) =>
                                            updateDay(form, dayIndex, {
                                                session_2_starts_at:
                                                    event.target.value,
                                            })
                                        }
                                        className={inputClassName}
                                    />
                                    <FieldError
                                        message={
                                            form.errors[
                                                `days.${dayIndex}.session_2_starts_at`
                                            ]
                                        }
                                    />
                                </div>
                                <div>
                                    <label
                                        htmlFor={`day-${day.day_of_week}-s2-end`}
                                        className={labelClassName}
                                    >
                                        Closes
                                    </label>
                                    <input
                                        id={`day-${day.day_of_week}-s2-end`}
                                        type="time"
                                        value={day.session_2_ends_at}
                                        onChange={(event) =>
                                            updateDay(form, dayIndex, {
                                                session_2_ends_at:
                                                    event.target.value,
                                            })
                                        }
                                        className={inputClassName}
                                    />
                                    <FieldError
                                        message={
                                            form.errors[
                                                `days.${dayIndex}.session_2_ends_at`
                                            ]
                                        }
                                    />
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            )}
        </section>
    );
}

export function OperatingHoursFormFields({
    form,
    formId,
    onSubmit,
}: OperatingHoursFormFieldsProps) {
    return (
        <>
            <form id={formId} onSubmit={onSubmit} className="space-y-3">
                {form.data.days.map((day, dayIndex) => (
                    <DayRow
                        key={day.day_of_week}
                        day={day}
                        dayIndex={dayIndex}
                        form={form}
                    />
                ))}
            </form>

            <div className="fixed inset-x-0 bottom-16 z-20 border-t border-[#e3e3e0] bg-[#FDFDFC]/95 px-4 py-3 backdrop-blur dark:border-[#3E3E3A] dark:bg-[#0a0a0a]/95">
                <div className="mx-auto max-w-lg">
                    <button
                        type="submit"
                        form={formId}
                        disabled={form.processing}
                        className="flex w-full items-center justify-center rounded-md bg-[#1b1b18] px-4 py-3 text-sm font-medium text-white disabled:opacity-50 dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
                    >
                        {form.processing ? 'Saving...' : 'Save hours'}
                    </button>
                </div>
            </div>
        </>
    );
}

export function daysToForm(
    days: Array<{
        day_of_week: number;
        is_closed: boolean;
        session_1_starts_at: string | null;
        session_1_ends_at: string | null;
        session_2_starts_at: string | null;
        session_2_ends_at: string | null;
    }>,
): OperatingHoursForm {
    return {
        days: days.map((day) => ({
            day_of_week: day.day_of_week,
            is_closed: day.is_closed,
            session_1_starts_at: day.session_1_starts_at ?? '09:00',
            session_1_ends_at: day.session_1_ends_at ?? '12:00',
            session_2_starts_at: day.session_2_starts_at ?? '15:00',
            session_2_ends_at: day.session_2_ends_at ?? '18:00',
            has_session_2:
                day.session_2_starts_at !== null &&
                day.session_2_ends_at !== null,
        })),
    };
}

export function normalizeOperatingHoursForSubmit(data: OperatingHoursForm) {
    return {
        days: data.days.map((day) => ({
            day_of_week: day.day_of_week,
            is_closed: day.is_closed,
            session_1_starts_at: day.is_closed ? null : day.session_1_starts_at,
            session_1_ends_at: day.is_closed ? null : day.session_1_ends_at,
            session_2_starts_at:
                day.is_closed || !day.has_session_2
                    ? null
                    : day.session_2_starts_at || null,
            session_2_ends_at:
                day.is_closed || !day.has_session_2
                    ? null
                    : day.session_2_ends_at || null,
        })),
    };
}

export function formatClosureDate(date: string): string {
    return new Date(`${date}T00:00:00`).toLocaleDateString(undefined, {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

export function formatClosureDateRange(
    startsAt: string,
    endsAt: string,
): string {
    if (startsAt === endsAt) {
        return formatClosureDate(startsAt);
    }

    return `${formatClosureDate(startsAt)} – ${formatClosureDate(endsAt)}`;
}

export function ClosureListItem({
    startsAt,
    endsAt,
    label,
    onRemove,
    removing,
}: {
    startsAt: string;
    endsAt: string;
    label: string | null;
    onRemove: () => void;
    removing: boolean;
}) {
    return (
        <div className="flex items-center justify-between gap-3 rounded-md border border-[#e3e3e0] px-3 py-2.5 dark:border-[#3E3E3A]">
            <div className="min-w-0">
                <p className="text-sm font-medium">
                    {formatClosureDateRange(startsAt, endsAt)}
                </p>
                {label && (
                    <p className="mt-0.5 truncate text-xs text-[#706f6c] dark:text-[#A1A09A]">
                        {label}
                    </p>
                )}
            </div>
            <button
                type="button"
                onClick={onRemove}
                disabled={removing}
                className={cn(
                    'shrink-0 text-sm font-medium text-[#b42318] disabled:opacity-50',
                )}
            >
                Remove
            </button>
        </div>
    );
}

export function ClosureAddForm({
    form,
    today,
    onSubmit,
}: {
    form: ReturnType<
        typeof useForm<{
            starts_at: string;
            ends_at: string;
            label: string;
        }>
    >;
    today: string;
    onSubmit: (event: React.FormEvent) => void;
}) {
    function handleStartChange(value: string) {
        form.setData((data) => ({
            ...data,
            starts_at: value,
            ends_at:
                data.ends_at === '' || data.ends_at < value ? value : data.ends_at,
        }));
    }

    return (
        <form onSubmit={onSubmit} className="space-y-3">
            <div className="grid grid-cols-2 gap-3">
                <div>
                    <label htmlFor="closure-starts-at" className={labelClassName}>
                        From
                    </label>
                    <input
                        id="closure-starts-at"
                        type="date"
                        value={form.data.starts_at}
                        min={today}
                        onChange={(event) =>
                            handleStartChange(event.target.value)
                        }
                        className={inputClassName}
                    />
                    <FieldError message={form.errors.starts_at} />
                </div>

                <div>
                    <label htmlFor="closure-ends-at" className={labelClassName}>
                        To
                    </label>
                    <input
                        id="closure-ends-at"
                        type="date"
                        value={form.data.ends_at}
                        min={form.data.starts_at || today}
                        onChange={(event) =>
                            form.setData('ends_at', event.target.value)
                        }
                        className={inputClassName}
                    />
                    <FieldError message={form.errors.ends_at} />
                </div>
            </div>

            <div>
                <label htmlFor="closure-label" className={labelClassName}>
                    Label (optional)
                </label>
                <input
                    id="closure-label"
                    type="text"
                    value={form.data.label}
                    onChange={(event) => form.setData('label', event.target.value)}
                    placeholder="e.g. Year-end holiday"
                    className={inputClassName}
                />
                <FieldError message={form.errors.label} />
            </div>

            <button
                type="submit"
                disabled={form.processing}
                className="w-full rounded-md border border-[#e3e3e0] px-4 py-3 text-sm font-medium disabled:opacity-50 dark:border-[#3E3E3A]"
            >
                {form.processing ? 'Adding...' : 'Add closed period'}
            </button>
        </form>
    );
}

export function StoreStatusBanner({ status }: { status: StoreStatus }) {
    return (
        <div
            className={cn(
                'mb-4 rounded-lg border p-4',
                status.is_open
                    ? 'border-[#abefc6] bg-[#ecfdf3] dark:border-[#085d3a] dark:bg-[#053321]'
                    : 'border-[#fecdca] bg-[#fef3f2] dark:border-[#912018] dark:bg-[#55160c]',
            )}
        >
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p
                        className={cn(
                            'text-sm font-semibold',
                            status.is_open
                                ? 'text-[#027a48] dark:text-[#75e0a7]'
                                : 'text-[#b42318] dark:text-[#fda29b]',
                        )}
                    >
                        {status.is_open ? 'Open' : 'Closed'}
                    </p>
                    <p
                        className={cn(
                            'mt-1 text-sm',
                            status.is_open
                                ? 'text-[#027a48]/80 dark:text-[#75e0a7]/80'
                                : 'text-[#b42318]/80 dark:text-[#fda29b]/80',
                        )}
                    >
                        {status.message}
                    </p>
                </div>
                <span
                    className={cn(
                        'shrink-0 rounded-full px-2.5 py-1 text-xs font-medium',
                        status.is_open
                            ? 'bg-[#dcfae6] text-[#027a48] dark:bg-[#085d3a] dark:text-[#75e0a7]'
                            : 'bg-[#fee4e2] text-[#b42318] dark:bg-[#912018] dark:text-[#fda29b]',
                    )}
                >
                    Customer view
                </span>
            </div>
            <p className="mt-3 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                {status.checked_at_formatted}
            </p>
        </div>
    );
}

export function HoursPageHeader() {
    return (
        <header className="mb-4">
            <h1 className="text-2xl font-semibold">Operating Hours</h1>
            <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                All times use Jakarta time (WIB). These apply to customer
                ordering only — admin is always available.
            </p>
        </header>
    );
}

export function HoursSectionTitle({
    title,
    description,
}: {
    title: string;
    description?: string;
}) {
    return (
        <div className="mb-3">
            <h2 className="text-base font-semibold">{title}</h2>
            {description && (
                <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    {description}
                </p>
            )}
        </div>
    );
}
