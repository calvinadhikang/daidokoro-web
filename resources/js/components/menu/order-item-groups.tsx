import type { TransactionAddon, TransactionItem } from '@/types/transaction';

function formatPrice(price: number): string {
    return price.toLocaleString();
}

function formatOrderedAt(iso: string): string {
    return new Date(iso).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

function ItemAddons({ item }: { item: TransactionItem }) {
    if (item.addons === null || item.addons.length === 0) {
        return null;
    }

    return (
        <ul className="mt-2 space-y-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">
            {item.addons.map((addon: TransactionAddon) => (
                <li key={addon.menu_addon_option_id}>
                    {addon.group_name}: {addon.name}
                    {addon.price > 0 && (
                        <span className="tabular-nums">
                            {' '}
                            (+{formatPrice(addon.price)})
                        </span>
                    )}
                </li>
            ))}
        </ul>
    );
}

type OrderItemRowProps = {
    item: TransactionItem;
    compact?: boolean;
};

export function OrderItemRow({ item, compact = false }: OrderItemRowProps) {
    return (
        <div className="flex items-start justify-between gap-3">
            <div className="min-w-0">
                <p className={compact ? 'text-sm font-medium' : 'font-medium'}>
                    {item.menu_name}
                </p>
                <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    Qty {item.quantity} · {formatPrice(item.unit_price)} each
                </p>
                <ItemAddons item={item} />
            </div>
            <p className="shrink-0 font-medium tabular-nums">
                {formatPrice(item.line_total)}
            </p>
        </div>
    );
}

type OrderItemGroupsProps = {
    groups: Array<{
        ordered_at: string;
        items: TransactionItem[];
    }>;
    compact?: boolean;
    emptyMessage?: string;
};

export function OrderItemGroups({
    groups,
    compact = false,
    emptyMessage = 'No items ordered yet.',
}: OrderItemGroupsProps) {
    if (groups.length === 0) {
        return (
            <div className="rounded-lg border border-dashed border-[#e3e3e0] p-6 text-center text-sm text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]">
                {emptyMessage}
            </div>
        );
    }

    return (
        <div className="space-y-4">
            {groups.map((group) => (
                <section key={group.ordered_at}>
                    <p className="mb-2 text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]">
                        Ordered {formatOrderedAt(group.ordered_at)}
                    </p>
                    <ul className="space-y-3">
                        {group.items.map((item) => (
                            <li
                                key={item.id}
                                className={
                                    compact
                                        ? 'rounded-md border border-[#e3e3e0] bg-white p-3 dark:border-[#3E3E3A] dark:bg-[#161615]'
                                        : 'rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]'
                                }
                            >
                                <OrderItemRow item={item} compact={compact} />
                            </li>
                        ))}
                    </ul>
                </section>
            ))}
        </div>
    );
}

export { formatOrderedAt, formatPrice };
