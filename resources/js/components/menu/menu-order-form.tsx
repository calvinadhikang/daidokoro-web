import { useMemo, useState } from 'react';

import { inputClassName, labelClassName } from '@/components/admin/menu-form';
import { MenuImage } from '@/components/admin/menu-image';
import { cn } from '@/lib/utils';
import type { Menu } from '@/types/menu';
import type { TransactionAddon } from '@/types/transaction';

export type MenuOrderLineItem = {
    menu_id: number;
    menu_name: string;
    quantity: number;
    unit_price: number;
    line_total: number;
    addon_option_ids: number[];
    addons: TransactionAddon[];
    note: string | null;
};

type Props = {
    menu: Menu;
    onSubmit: (item: MenuOrderLineItem) => void;
    submitLabel?: string;
    disabled?: boolean;
    variant?: 'default' | 'customer';
    initialQuantity?: number;
    initialAddonOptionIds?: number[];
    initialNote?: string | null;
    errors?: {
        quantity?: string;
        addon_option_ids?: string;
        note?: string;
    };
};

function formatPrice(price: number): string {
    return price.toLocaleString();
}

function FieldError({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return <p className="mt-1 text-xs text-[#b42318]">{message}</p>;
}

function buildAddonSnapshots(
    menu: Menu,
    selectedOptionIds: number[],
): TransactionAddon[] {
    const snapshots: TransactionAddon[] = [];

    for (const group of menu.addon_groups) {
        for (const option of group.options) {
            if (option.is_available && selectedOptionIds.includes(option.id)) {
                snapshots.push({
                    menu_addon_option_id: option.id,
                    group_name: group.name,
                    name: option.name,
                    price: option.price,
                });
            }
        }
    }

    return snapshots;
}

function availableOptionsForGroup(group: Menu['addon_groups'][number]) {
    return group.options.filter((option) => option.is_available);
}

function validateSelections(
    menu: Menu,
    selectedOptionIds: number[],
): string | null {
    for (const group of menu.addon_groups) {
        const availableOptions = availableOptionsForGroup(group);
        const groupOptionIds = availableOptions.map((option) => option.id);
        const selectedForGroup = selectedOptionIds.filter((id) =>
            groupOptionIds.includes(id),
        );

        if (group.is_required && availableOptions.length === 0) {
            return `No available options for ${group.name}.`;
        }

        if (
            group.is_required &&
            availableOptions.length > 0 &&
            selectedForGroup.length === 0
        ) {
            return `Please select an option for ${group.name}.`;
        }

        if (group.selection_type === 'single' && selectedForGroup.length > 1) {
            return `Only one option can be selected for ${group.name}.`;
        }
    }

    return null;
}

export function MenuOrderForm({
    menu,
    onSubmit,
    submitLabel = 'Add to order',
    disabled = false,
    variant = 'default',
    initialQuantity = 1,
    initialAddonOptionIds = [],
    initialNote = null,
    errors,
}: Props) {
    const isCustomer = variant === 'customer';
    const [quantity, setQuantity] = useState(initialQuantity);
    const [addonOptionIds, setAddonOptionIds] = useState<number[]>(
        initialAddonOptionIds,
    );
    const [note, setNote] = useState(initialNote ?? '');
    const [error, setError] = useState<string | null>(null);

    const estimatedUnitPrice = useMemo(() => {
        const addonTotal = menu.addon_groups.reduce((groupSum, group) => {
            const groupOptionIds = availableOptionsForGroup(group).map(
                (option) => option.id,
            );
            const selectedForGroup = addonOptionIds.filter((id) =>
                groupOptionIds.includes(id),
            );

            const optionTotal = selectedForGroup.reduce((sum, optionId) => {
                const option = group.options.find(
                    (item) => item.id === optionId,
                );

                return sum + (option?.price ?? 0);
            }, 0);

            return groupSum + optionTotal;
        }, 0);

        return menu.price + addonTotal;
    }, [menu, addonOptionIds]);

    function toggleSingleOption(groupOptionIds: number[], optionId: number) {
        const withoutGroup = addonOptionIds.filter(
            (id) => !groupOptionIds.includes(id),
        );
        setAddonOptionIds([...withoutGroup, optionId]);
        setError(null);
    }

    function toggleMultipleOption(optionId: number) {
        if (addonOptionIds.includes(optionId)) {
            setAddonOptionIds(addonOptionIds.filter((id) => id !== optionId));
        } else {
            setAddonOptionIds([...addonOptionIds, optionId]);
        }

        setError(null);
    }

    function handleSubmit(event: React.FormEvent) {
        event.preventDefault();

        const validationError = validateSelections(menu, addonOptionIds);

        if (validationError !== null) {
            setError(validationError);

            return;
        }

        const addons = buildAddonSnapshots(menu, addonOptionIds);
        const unitPrice = estimatedUnitPrice;

        onSubmit({
            menu_id: menu.id,
            menu_name: menu.name,
            quantity,
            unit_price: unitPrice,
            line_total: unitPrice * quantity,
            addon_option_ids: [...addonOptionIds],
            addons,
            note: note.trim() === '' ? null : note.trim(),
        });
    }

    const lineTotal = estimatedUnitPrice * quantity;

    return (
        <form
            onSubmit={handleSubmit}
            className={cn('space-y-4', isCustomer && 'space-y-5')}
        >
            {!isCustomer && (
                <div className="flex items-center gap-3 rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <MenuImage
                        src={menu.image}
                        alt={menu.name}
                        className="h-20 w-20 rounded-md border border-[#e3e3e0] bg-[#FDFDFC] dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                    />
                    <div>
                        <h2 className="font-medium">{menu.name}</h2>
                        <p className="mt-1 text-sm text-[#706f6c] tabular-nums dark:text-[#A1A09A]">
                            Base price {formatPrice(menu.price)}
                        </p>
                    </div>
                </div>
            )}

            {menu.addon_groups.length > 0 && (
                <div className="space-y-4">
                    {menu.addon_groups.map((group) => {
                        const availableOptions =
                            availableOptionsForGroup(group);
                        const groupOptionIds = group.options.map(
                            (option) => option.id,
                        );

                        return (
                            <div
                                key={group.id}
                                className={cn(
                                    'border border-[#e3e3e0] bg-white dark:border-[#3E3E3A] dark:bg-[#161615]',
                                    isCustomer
                                        ? 'rounded-2xl p-4'
                                        : 'rounded-md p-3',
                                )}
                            >
                                <div
                                    className={cn(
                                        'mb-2 flex items-start justify-between gap-3',
                                        isCustomer && 'mb-3',
                                    )}
                                >
                                    <p className="text-sm font-medium">
                                        {group.name}
                                        {group.is_required && (
                                            <span className="ml-1 text-[#b42318]">
                                                *
                                            </span>
                                        )}
                                    </p>
                                    {isCustomer && (
                                        <span className="shrink-0 text-[11px] font-medium text-[#706f6c] dark:text-[#A1A09A]">
                                            {group.is_required
                                                ? 'Required'
                                                : 'Optional'}
                                            {' · '}
                                            {group.selection_type === 'single'
                                                ? 'Choose 1'
                                                : 'Choose any'}
                                        </span>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    {group.options.map((option) => {
                                        const isUnavailable =
                                            !option.is_available;
                                        const isSelected =
                                            addonOptionIds.includes(option.id);
                                        const inputType =
                                            group.selection_type === 'single'
                                                ? 'radio'
                                                : 'checkbox';

                                        return (
                                            <label
                                                key={option.id}
                                                className={cn(
                                                    'flex items-center justify-between gap-3 border text-sm',
                                                    isCustomer
                                                        ? 'rounded-xl px-3.5 py-3'
                                                        : 'rounded-md px-3 py-2',
                                                    isUnavailable
                                                        ? 'cursor-not-allowed border-[#e3e3e0] bg-[#FDFDFC]/60 opacity-60 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]/40'
                                                        : 'cursor-pointer',
                                                    !isUnavailable && isSelected
                                                        ? 'border-[#1b1b18] bg-[#FDFDFC] dark:border-[#EDEDEC] dark:bg-[#0a0a0a]'
                                                        : !isUnavailable &&
                                                              'border-[#e3e3e0] dark:border-[#3E3E3A]',
                                                )}
                                            >
                                                <span className="flex items-center gap-2">
                                                    <input
                                                        type={inputType}
                                                        name={
                                                            group.selection_type ===
                                                            'single'
                                                                ? `addon-group-${group.id}`
                                                                : undefined
                                                        }
                                                        checked={isSelected}
                                                        disabled={isUnavailable}
                                                        onChange={() => {
                                                            if (isUnavailable) {
                                                                return;
                                                            }

                                                            if (
                                                                group.selection_type ===
                                                                'single'
                                                            ) {
                                                                toggleSingleOption(
                                                                    groupOptionIds,
                                                                    option.id,
                                                                );

                                                                return;
                                                            }

                                                            toggleMultipleOption(
                                                                option.id,
                                                            );
                                                        }}
                                                        className="h-4 w-4 border-[#e3e3e0] disabled:cursor-not-allowed dark:border-[#3E3E3A]"
                                                    />
                                                    <span>
                                                        {option.name}
                                                        {isUnavailable && (
                                                            <span className="ml-1.5 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                                                Unavailable
                                                            </span>
                                                        )}
                                                    </span>
                                                </span>
                                                <span className="text-[#706f6c] tabular-nums dark:text-[#A1A09A]">
                                                    {option.price === 0 &&
                                                    isCustomer
                                                        ? 'Free'
                                                        : `+${formatPrice(option.price)}`}
                                                </span>
                                            </label>
                                        );
                                    })}
                                </div>

                                {group.is_required &&
                                    availableOptions.length === 0 && (
                                        <p className="mt-2 text-xs text-[#b42318]">
                                            No available options for this group.
                                        </p>
                                    )}
                            </div>
                        );
                    })}
                </div>
            )}

            {!isCustomer && (
                <div>
                    <label htmlFor="order-quantity" className={labelClassName}>
                        Quantity
                    </label>
                    <div className="flex items-center gap-3">
                        <button
                            type="button"
                            onClick={() =>
                                setQuantity(Math.max(1, quantity - 1))
                            }
                            className="flex h-10 w-10 items-center justify-center rounded-md border border-[#e3e3e0] text-lg dark:border-[#3E3E3A]"
                        >
                            −
                        </button>
                        <input
                            id="order-quantity"
                            type="number"
                            min={1}
                            max={99}
                            value={quantity}
                            onChange={(event) =>
                                setQuantity(
                                    Math.max(
                                        1,
                                        Math.min(
                                            99,
                                            parseInt(event.target.value, 10) ||
                                                1,
                                        ),
                                    ),
                                )
                            }
                            className={`${inputClassName} text-center`}
                        />
                        <button
                            type="button"
                            onClick={() =>
                                setQuantity(Math.min(99, quantity + 1))
                            }
                            className="flex h-10 w-10 items-center justify-center rounded-md border border-[#e3e3e0] text-lg dark:border-[#3E3E3A]"
                        >
                            +
                        </button>
                    </div>
                    <FieldError message={errors?.quantity} />
                </div>
            )}

            <div>
                <label htmlFor="order-note" className={labelClassName}>
                    Note <span className="font-normal">(optional)</span>
                </label>
                <textarea
                    id="order-note"
                    rows={isCustomer ? 2 : 3}
                    maxLength={500}
                    value={note}
                    onChange={(event) => setNote(event.target.value)}
                    placeholder="e.g. no onions, extra spicy"
                    className={cn(inputClassName, isCustomer && 'rounded-xl')}
                />
                <FieldError message={errors?.note} />
            </div>

            {!isCustomer && (
                <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    Total:{' '}
                    <span className="font-medium text-[#1b1b18] tabular-nums dark:text-[#EDEDEC]">
                        {formatPrice(lineTotal)}
                    </span>
                </p>
            )}

            {!isCustomer && (
                <FieldError message={error ?? errors?.addon_option_ids} />
            )}

            {isCustomer ? (
                <div className="pointer-events-none fixed inset-x-0 bottom-[calc(4.5rem+env(safe-area-inset-bottom))] z-20 px-4">
                    <div className="mx-auto max-w-md">
                        {(error ??
                            errors?.addon_option_ids ??
                            errors?.quantity) && (
                            <p className="pointer-events-auto mb-2 rounded-xl border border-[#fda29b] bg-[#fef3f2] px-3 py-2 text-xs text-[#b42318] dark:border-[#912018] dark:bg-[#55160c] dark:text-[#fda29b]">
                                {error ??
                                    errors?.addon_option_ids ??
                                    errors?.quantity}
                            </p>
                        )}
                        <div className="pointer-events-auto flex items-center gap-3 rounded-2xl border border-[#e3e3e0] bg-white/95 p-2.5 shadow-lg backdrop-blur dark:border-[#3E3E3A] dark:bg-[#161615]/95">
                            <div className="flex items-center rounded-full border border-[#e3e3e0] dark:border-[#3E3E3A]">
                                <button
                                    type="button"
                                    onClick={() =>
                                        setQuantity(Math.max(1, quantity - 1))
                                    }
                                    aria-label="Decrease quantity"
                                    className="flex h-10 w-10 items-center justify-center text-lg"
                                >
                                    −
                                </button>
                                <span className="min-w-6 text-center text-sm font-semibold tabular-nums">
                                    {quantity}
                                </span>
                                <button
                                    type="button"
                                    onClick={() =>
                                        setQuantity(Math.min(99, quantity + 1))
                                    }
                                    aria-label="Increase quantity"
                                    className="flex h-10 w-10 items-center justify-center text-lg"
                                >
                                    +
                                </button>
                            </div>
                            <button
                                type="submit"
                                disabled={disabled}
                                className="flex min-w-0 flex-1 items-center justify-center rounded-xl border border-[#1b1b18] bg-[#1b1b18] px-4 py-3 text-sm font-medium text-white disabled:opacity-50 dark:border-[#EDEDEC] dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
                            >
                                <span className="truncate">{submitLabel}</span>
                                <span className="ml-2 shrink-0 tabular-nums">
                                    {formatPrice(lineTotal)}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            ) : (
                <button
                    type="submit"
                    disabled={disabled}
                    className="flex w-full items-center justify-center rounded-md border border-[#1b1b18] bg-[#1b1b18] px-4 py-3 text-sm font-medium text-white disabled:opacity-50 dark:border-[#EDEDEC] dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
                >
                    {submitLabel}
                </button>
            )}
        </form>
    );
}
