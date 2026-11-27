import { useEffect, useId, useRef, useState } from 'react';

import { cn } from '@/lib/utils';

type TransactionDatePickerProps = {
    date: string;
    today: string;
    onChange: (date: string) => void;
};

const WEEKDAYS = ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'];

function parseDate(date: string): Date {
    const [year, month, day] = date.split('-').map(Number);

    return new Date(year, month - 1, day);
}

function toDateString(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function shiftDate(date: string, days: number): string {
    const next = parseDate(date);
    next.setDate(next.getDate() + days);

    return toDateString(next);
}

function startOfMonth(date: Date): Date {
    return new Date(date.getFullYear(), date.getMonth(), 1);
}

function shiftMonth(month: Date, amount: number): Date {
    return new Date(month.getFullYear(), month.getMonth() + amount, 1);
}

function formatDayLabel(date: string, today: string): string {
    const formatted = parseDate(date).toLocaleDateString(undefined, {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
    });

    if (date === today) {
        return `Today · ${formatted}`;
    }

    if (date === shiftDate(today, -1)) {
        return `Yesterday · ${formatted}`;
    }

    return formatted;
}

function monthCells(month: Date): Array<Date | null> {
    const first = startOfMonth(month);
    const leading = (first.getDay() + 6) % 7;
    const daysInMonth = new Date(
        first.getFullYear(),
        first.getMonth() + 1,
        0,
    ).getDate();
    const cells: Array<Date | null> = Array.from(
        { length: leading },
        () => null,
    );

    for (let day = 1; day <= daysInMonth; day++) {
        cells.push(new Date(first.getFullYear(), first.getMonth(), day));
    }

    while (cells.length % 7 !== 0) {
        cells.push(null);
    }

    return cells;
}

function DayChevron({ direction }: { direction: 'left' | 'right' }) {
    return (
        <svg
            viewBox="0 0 20 20"
            fill="none"
            aria-hidden="true"
            className="size-5"
        >
            <path
                d={
                    direction === 'left'
                        ? 'M12.5 4.5 7 10l5.5 5.5'
                        : 'M7.5 4.5 13 10l-5.5 5.5'
                }
                stroke="currentColor"
                strokeWidth="1.75"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}

function CalendarIcon() {
    return (
        <svg
            viewBox="0 0 20 20"
            fill="none"
            aria-hidden="true"
            className="size-4 shrink-0 text-[#706f6c] dark:text-[#A1A09A]"
        >
            <rect
                x="2.75"
                y="4.25"
                width="14.5"
                height="12"
                rx="1.75"
                stroke="currentColor"
                strokeWidth="1.5"
            />
            <path
                d="M2.75 8.25h14.5M6.5 2.75v3M13.5 2.75v3"
                stroke="currentColor"
                strokeWidth="1.5"
                strokeLinecap="round"
            />
        </svg>
    );
}

const stepButtonClassName =
    'flex size-11 shrink-0 items-center justify-center rounded-md border border-[#e3e3e0] bg-white text-[#1b1b18] disabled:cursor-not-allowed disabled:opacity-40 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]';

export function TransactionDatePicker({
    date,
    today,
    onChange,
}: TransactionDatePickerProps) {
    const rootRef = useRef<HTMLDivElement>(null);
    const calendarId = useId();
    const [open, setOpen] = useState(false);
    const [trackedDate, setTrackedDate] = useState(date);
    const [visibleMonth, setVisibleMonth] = useState(() =>
        startOfMonth(parseDate(date)),
    );
    const isToday = date === today;
    const todayDate = parseDate(today);
    const nextMonthDisabled =
        visibleMonth.getFullYear() > todayDate.getFullYear() ||
        (visibleMonth.getFullYear() === todayDate.getFullYear() &&
            visibleMonth.getMonth() >= todayDate.getMonth());

    if (date !== trackedDate) {
        setTrackedDate(date);
        setVisibleMonth(startOfMonth(parseDate(date)));
    }

    useEffect(() => {
        if (!open) {
            return;
        }

        function onPointerDown(event: MouseEvent) {
            if (!rootRef.current?.contains(event.target as Node)) {
                setOpen(false);
            }
        }

        function onKeyDown(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        }

        document.addEventListener('mousedown', onPointerDown);
        document.addEventListener('keydown', onKeyDown);

        return () => {
            document.removeEventListener('mousedown', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [open]);

    function selectDate(next: string) {
        setOpen(false);
        onChange(next);
    }

    return (
        <div ref={rootRef} className="relative mb-4">
            <div className="flex items-center gap-2">
                <button
                    type="button"
                    aria-label="Previous day"
                    onClick={() => onChange(shiftDate(date, -1))}
                    className={stepButtonClassName}
                >
                    <DayChevron direction="left" />
                </button>

                <button
                    type="button"
                    aria-expanded={open}
                    aria-controls={calendarId}
                    onClick={() => setOpen((current) => !current)}
                    className="flex h-11 min-w-0 flex-1 items-center justify-center gap-2 rounded-md border border-[#e3e3e0] bg-white px-3 text-sm font-medium dark:border-[#3E3E3A] dark:bg-[#161615]"
                >
                    <CalendarIcon />
                    <span className="truncate">
                        {formatDayLabel(date, today)}
                    </span>
                </button>

                <button
                    type="button"
                    aria-label="Next day"
                    disabled={date >= today}
                    onClick={() => onChange(shiftDate(date, 1))}
                    className={stepButtonClassName}
                >
                    <DayChevron direction="right" />
                </button>
            </div>

            {open && (
                <div
                    id={calendarId}
                    role="dialog"
                    aria-label="Choose date"
                    className="absolute inset-x-0 top-[calc(100%+0.5rem)] z-20 rounded-lg border border-[#e3e3e0] bg-white p-3 shadow-lg dark:border-[#3E3E3A] dark:bg-[#161615]"
                >
                    <div className="mb-3 flex items-center justify-between gap-2">
                        <button
                            type="button"
                            aria-label="Previous month"
                            onClick={() =>
                                setVisibleMonth((month) =>
                                    shiftMonth(month, -1),
                                )
                            }
                            className={stepButtonClassName}
                        >
                            <DayChevron direction="left" />
                        </button>
                        <p className="truncate text-sm font-medium">
                            {visibleMonth.toLocaleDateString(undefined, {
                                month: 'long',
                                year: 'numeric',
                            })}
                        </p>
                        <button
                            type="button"
                            aria-label="Next month"
                            disabled={nextMonthDisabled}
                            onClick={() =>
                                setVisibleMonth((month) => shiftMonth(month, 1))
                            }
                            className={stepButtonClassName}
                        >
                            <DayChevron direction="right" />
                        </button>
                    </div>

                    <div className="grid grid-cols-7 gap-1 text-center">
                        {WEEKDAYS.map((weekday) => (
                            <div
                                key={weekday}
                                className="py-1 text-[11px] font-medium text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                {weekday}
                            </div>
                        ))}
                        {monthCells(visibleMonth).map((day, index) => {
                            if (day === null) {
                                return (
                                    <div
                                        key={`empty-${index}`}
                                        className="size-9"
                                    />
                                );
                            }

                            const iso = toDateString(day);
                            const selected = iso === date;
                            const current = iso === today;
                            const future = iso > today;

                            return (
                                <button
                                    key={iso}
                                    type="button"
                                    disabled={future}
                                    onClick={() => selectDate(iso)}
                                    aria-current={current ? 'date' : undefined}
                                    aria-pressed={selected}
                                    className={cn(
                                        'mx-auto flex size-9 items-center justify-center rounded-md text-sm tabular-nums',
                                        selected
                                            ? 'bg-[#1b1b18] font-medium text-white dark:bg-[#EDEDEC] dark:text-[#1b1b18]'
                                            : 'hover:bg-[#f5f5f4] disabled:cursor-not-allowed disabled:text-[#A1A09A] disabled:opacity-40 disabled:hover:bg-transparent dark:hover:bg-[#262625]',
                                        current &&
                                            !selected &&
                                            'ring-1 ring-[#1b1b18] ring-inset dark:ring-[#EDEDEC]',
                                    )}
                                >
                                    {day.getDate()}
                                </button>
                            );
                        })}
                    </div>

                    {!isToday && (
                        <button
                            type="button"
                            onClick={() => selectDate(today)}
                            className="mt-3 w-full rounded-md border border-[#e3e3e0] px-3 py-2 text-sm font-medium dark:border-[#3E3E3A]"
                        >
                            Today
                        </button>
                    )}
                </div>
            )}

            {!open && !isToday && (
                <button
                    type="button"
                    onClick={() => onChange(today)}
                    className="mt-2 w-full text-center text-sm font-medium text-[#706f6c] underline-offset-2 hover:underline dark:text-[#A1A09A]"
                >
                    Back to today
                </button>
            )}
        </div>
    );
}
