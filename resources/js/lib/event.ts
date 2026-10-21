import type { EventCopySource } from '@/types/sales-channel';

export function eventCopySourceLabel(event: EventCopySource): string {
    const dates =
        !event.starts_at || !event.ends_at
            ? 'No dates'
            : event.starts_at === event.ends_at
              ? event.starts_at
              : `${event.starts_at} – ${event.ends_at}`;
    const menus =
        event.menus_count === 1 ? '1 menu' : `${event.menus_count} menus`;
    const archived = event.is_archived ? ' · Archived' : '';

    return `${event.name} · ${dates} · ${menus}${archived}`;
}
