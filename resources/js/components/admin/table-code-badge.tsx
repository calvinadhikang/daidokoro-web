export const tableCodeBadgeClassName =
    'shrink-0 rounded-full bg-[#fff6ed] px-2.5 py-1 text-xs font-medium text-[#c4320a] dark:bg-[#511c10] dark:text-[#fdba8c]';

export function TableCodeBadge({
    code,
}: {
    code: string | null | undefined;
}) {
    const tableCode = code?.trim();

    if (!tableCode) {
        return null;
    }

    return <span className={tableCodeBadgeClassName}>Meja {tableCode}</span>;
}
