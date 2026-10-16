import type { OmakaseSessionOption } from '@/types/omakase';

export function formatOmakaseDate(value: string): string {
    const [year, month, day] = value.slice(0, 10).split('-').map(Number);

    if (!year || !month || !day) {
        return value;
    }

    return new Date(year, month - 1, day).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

export function omakaseSessionOptionLabel(
    session: OmakaseSessionOption,
): string {
    const menus =
        session.menus_count === 1 ? '1 menu' : `${session.menus_count} menus`;

    return `${session.name} · ${formatOmakaseDate(session.date)} · ${menus}`;
}
