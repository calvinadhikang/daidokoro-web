export type OperatingHourDay = {
    day_of_week: number;
    day_name: string;
    is_closed: boolean;
    session_1_starts_at: string | null;
    session_1_ends_at: string | null;
    session_2_starts_at: string | null;
    session_2_ends_at: string | null;
};

export type OperatingHourDayForm = {
    day_of_week: number;
    is_closed: boolean;
    session_1_starts_at: string;
    session_1_ends_at: string;
    session_2_starts_at: string;
    session_2_ends_at: string;
    has_session_2: boolean;
};

export type OperatingHoursForm = {
    days: OperatingHourDayForm[];
};

export type OperatingClosure = {
    id: number;
    starts_at: string;
    ends_at: string;
    label: string | null;
};

export type OperatingClosureForm = {
    starts_at: string;
    ends_at: string;
    label: string;
};

export type StoreStatusReason =
    | 'open'
    | 'closed_period'
    | 'weekly_closed'
    | 'outside_hours';

export type StoreStatus = {
    is_open: boolean;
    reason: StoreStatusReason;
    message: string;
    checked_at: string;
    checked_at_formatted: string;
    timezone: string;
    timezone_label: string;
};

export type NextSession = {
    context: 'current' | 'upcoming';
    session_number: number;
    time_range_formatted: string;
    starts_at_formatted: string;
    ends_at_formatted: string;
};

export const DAY_NAMES = [
    'Sunday',
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
] as const;
