import { useMemo, useState } from 'react';

import {
    inputClassName,
    labelClassName,
} from '@/components/admin/menu-form';
import { MenuImage } from '@/components/admin/menu-image';
import { cn } from '@/lib/utils';
import type { Menu } from '@/types/menu';
import type { TransactionAddon } from '@/types/transaction';

export type TransactionMenuPickerItem = {
    menu_id: number;
    menu_name: string;
    quantity: number;
    unit_price: number;
    line_total: number;
    addon_option_ids: number[];
    addons: TransactionAddon[];
};

type Props = {
    menus: Menu[];
    onAdd: (item: TransactionMenuPickerItem) => void;
    submitLabel?: string;
    disabled?: boolean;
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
            if (
                option.is_available &&
                selectedOptionIds.includes(option.id)
            ) {
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

function validateSelections(menu: Menu, selectedOptionIds: number[]): string | null {
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

        if (
            group.selection_type === 'single' &&
            selectedForGroup.length > 1
        ) {
            return `Only one option can be selected for ${group.name}.`;
        }
    }

    return null;
}

export function TransactionMenuPicker({
    menus,
    onAdd,
    submitLabel = 'Add to order',
    disabled = false,
}: Props) {
    const [selectedMenuId, setSelectedMenuId] = useState('');
    const [quantity, setQuantity] = useState(1);
    const [addonOptionIds, setAddonOptionIds] = useState<number[]>([]);
    const [error, setError] = useState<string | null>(null);

    const selectedMenu = useMemo(
        () => menus.find((menu) => String(menu.id) === selectedMenuId) ?? null,
        [menus, selectedMenuId],
    );

    const estimatedUnitPrice = useMemo(() => {
        if (selectedMenu === null) {
            return 0;
        }

        const addonTotal = selectedMenu.addon_groups.reduce((groupSum, group) => {
            const groupOptionIds = availableOptionsForGroup(group).map(
                (option) => option.id,
            );
            const selectedForGroup = addonOptionIds.filter((id) =>
                groupOptionIds.includes(id),
            );

            const optionTotal = selectedForGroup.reduce((sum, optionId) => {
                const option = group.options.find((item) => item.id === optionId);
                return sum + (option?.price ?? 0);
            }, 0);

            return groupSum + optionTotal;
        }, 0);

        return selectedMenu.price + addonTotal;
    }, [selectedMenu, addonOptionIds]);

    function handleMenuChange(menuId: string) {
        setSelectedMenuId(menuId);
        setQuantity(1);
        setAddonOptionIds([]);
        setError(null);
    }

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

    function handleAdd() {
        if (selectedMenu === null) {
            setError('Please select a menu.');
            return;
        }

        const validationError = validateSelections(selectedMenu, addonOptionIds);
        if (validationError !== null) {
            setError(validationError);
            return;
        }

        const addons = buildAddonSnapshots(selectedMenu, addonOptionIds);
        const unitPrice = estimatedUnitPrice;

        onAdd({
            menu_id: selectedMenu.id,
            menu_name: selectedMenu.name,
            quantity,
            unit_price: unitPrice,
            line_total: unitPrice * quantity,
            addon_option_ids: [...addonOptionIds],
            addons,
        });

        setSelectedMenuId('');
        setQuantity(1);
        setAddonOptionIds([]);
        setError(null);
    }

    if (menus.length === 0) {
        return (
            <div className="rounded-lg border border-dashed border-[#e3e3e0] p-6 text-center text-sm text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]">
                No available menus to order.
            </div>
        );
    }

    return (
        <div className="space-y-4">
            <div>
                <label htmlFor="picker-menu_id" className={labelClassName}>
                    Menu
                </label>
                <select
                    id="picker-menu_id"
                    value={selectedMenuId}
                    onChange={(event) => handleMenuChange(event.target.value)}
                    className={inputClassName}
                >
                    <option value="">Select a menu...</option>
                    {menus.map((menu) => (
                        <option key={menu.id} value={menu.id}>
                            {menu.name} ({formatPrice(menu.price)})
                        </option>
                    ))}
                </select>
            </div>

            {selectedMenu !== null && (
                <div className="flex items-center gap-3 rounded-md border border-[#e3e3e0] p-3 dark:border-[#3E3E3A]">
                    <MenuImage
                        src={selectedMenu.image}
                        alt={selectedMenu.name}
                        className="h-14 w-14 rounded-md border border-[#e3e3e0] bg-[#FDFDFC] dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                    />
                    <div>
                        <p className="font-medium">{selectedMenu.name}</p>
                        <p className="text-sm tabular-nums text-[#706f6c] dark:text-[#A1A09A]">
                            {formatPrice(selectedMenu.price)}
                        </p>
                    </div>
                </div>
            )}

            {selectedMenu !== null && selectedMenu.addon_groups.length > 0 && (
                <div className="space-y-4">
                    {selectedMenu.addon_groups.map((group) => {
                        const availableOptions =
                            availableOptionsForGroup(group);
                        const groupOptionIds = group.options.map(
                            (option) => option.id,
                        );

                        return (
                            <div
                                key={group.id}
                                className="rounded-md border border-[#e3e3e0] p-3 dark:border-[#3E3E3A]"
                            >
                                <p className="mb-2 text-sm font-medium">
                                    {group.name}
                                    {group.is_required && (
                                        <span className="ml-1 text-[#b42318]">
                                            *
                                        </span>
                                    )}
                                </p>

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
                                                    'flex items-center justify-between gap-3 rounded-md border px-3 py-2 text-sm',
                                                    isUnavailable
                                                        ? 'cursor-not-allowed border-[#e3e3e0] bg-[#FDFDFC]/60 opacity-60 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]/40'
                                                        : 'cursor-pointer',
                                                    !isUnavailable &&
                                                        isSelected
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
                                                <span className="tabular-nums text-[#706f6c] dark:text-[#A1A09A]">
                                                    +{formatPrice(option.price)}
                                                </span>
                                            </label>
                                        );
                                    })}
                                </div>

                                {group.is_required &&
                                    availableOptions.length === 0 && (
                                        <p className="mt-2 text-xs text-[#b42318]">
                                            No available options for this
                                            group.
                                        </p>
                                    )}
                            </div>
                        );
                    })}
                </div>
            )}

            {selectedMenu !== null && (
                <>
                    <div>
                        <label htmlFor="picker-quantity" className={labelClassName}>
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
                                id="picker-quantity"
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
                                                parseInt(
                                                    event.target.value,
                                                    10,
                                                ) || 1,
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
                    </div>

                    <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        Line total:{' '}
                        <span className="font-medium tabular-nums text-[#1b1b18] dark:text-[#EDEDEC]">
                            {formatPrice(estimatedUnitPrice * quantity)}
                        </span>
                    </p>
                </>
            )}

            <FieldError message={error ?? undefined} />

            <button
                type="button"
                onClick={handleAdd}
                disabled={disabled || selectedMenuId === ''}
                className="flex w-full items-center justify-center rounded-md border border-[#e3e3e0] px-4 py-3 text-sm font-medium disabled:opacity-50 dark:border-[#3E3E3A]"
            >
                {submitLabel}
            </button>
        </div>
    );
}
