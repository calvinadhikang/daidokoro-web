import { Link } from '@inertiajs/react';
import type { useForm } from '@inertiajs/react';

import { MenuImage } from '@/components/admin/menu-image';
import type {
    MenuAddonGroupForm,
    MenuAddonOptionForm,
    MenuForm,
} from '@/types/menu';

type InertiaMenuForm = ReturnType<typeof useForm<MenuForm>>;

export const inputClassName =
    'w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2.5 text-sm outline-none focus:border-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:focus:border-[#EDEDEC]';

export const labelClassName =
    'mb-1.5 block text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]';

export function emptyOption(): MenuAddonOptionForm {
    return { name: '', price: '', is_available: true };
}

export function emptyGroup(): MenuAddonGroupForm {
    return {
        name: '',
        selection_type: 'single',
        is_required: false,
        options: [emptyOption()],
    };
}

function sanitizePriceInput(value: string): string {
    return value.replace(/\D/g, '');
}

export function normalizeMenuFormForSubmit(data: MenuForm) {
    return {
        ...data,
        price: data.price === '' ? 0 : parseInt(data.price, 10),
        addon_groups: data.addon_groups.map((group) => ({
            ...group,
            options: group.options.map((option) => ({
                ...option,
                price: option.price === '' ? 0 : parseInt(option.price, 10),
            })),
        })),
    };
}

function FieldError({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return <p className="mt-1 text-xs text-[#b42318]">{message}</p>;
}

type MenuFormProps = {
    form: InertiaMenuForm;
    formId: string;
    title: string;
    backHref: string;
    submitLabel: string;
    imageSrc?: string | null;
    onSubmit: (event: React.FormEvent) => void;
};

export function MenuFormFields({
    form,
    formId,
    title,
    backHref,
    submitLabel,
    imageSrc = null,
    onSubmit,
}: MenuFormProps) {
    function addGroup() {
        form.setData('addon_groups', [...form.data.addon_groups, emptyGroup()]);
    }

    function removeGroup(groupIndex: number) {
        form.setData(
            'addon_groups',
            form.data.addon_groups.filter((_, index) => index !== groupIndex),
        );
    }

    function updateGroup<K extends keyof MenuAddonGroupForm>(
        groupIndex: number,
        key: K,
        value: MenuAddonGroupForm[K],
    ) {
        const groups = [...form.data.addon_groups];
        groups[groupIndex] = { ...groups[groupIndex], [key]: value };
        form.setData('addon_groups', groups);
    }

    function addOption(groupIndex: number) {
        const groups = [...form.data.addon_groups];
        groups[groupIndex] = {
            ...groups[groupIndex],
            options: [...groups[groupIndex].options, emptyOption()],
        };
        form.setData('addon_groups', groups);
    }

    function removeOption(groupIndex: number, optionIndex: number) {
        const groups = [...form.data.addon_groups];
        groups[groupIndex] = {
            ...groups[groupIndex],
            options: groups[groupIndex].options.filter(
                (_, index) => index !== optionIndex,
            ),
        };
        form.setData('addon_groups', groups);
    }

    function updateOption(
        groupIndex: number,
        optionIndex: number,
        key: keyof MenuAddonOptionForm,
        value: string | number | boolean,
    ) {
        const groups = [...form.data.addon_groups];
        const options = [...groups[groupIndex].options];
        options[optionIndex] = { ...options[optionIndex], [key]: value };
        groups[groupIndex] = { ...groups[groupIndex], options };
        form.setData('addon_groups', groups);
    }

    return (
        <>
            <header className="sticky top-0 z-10 border-b border-[#e3e3e0] bg-[#FDFDFC]/95 px-4 py-4 backdrop-blur dark:border-[#3E3E3A] dark:bg-[#0a0a0a]/95">
                <div className="mx-auto flex max-w-2xl items-center gap-3">
                    <Link
                        href={backHref}
                        className="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Back
                    </Link>
                    <h1 className="text-lg font-semibold">{title}</h1>
                </div>
            </header>

            <form
                id={formId}
                onSubmit={onSubmit}
                className="mx-auto w-full max-w-2xl px-4 py-6 pb-36"
            >
                <section className="mb-8 rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <h2 className="mb-4 text-base font-semibold">Menu details</h2>

                    <MenuImage
                        src={imageSrc}
                        alt={form.data.name || 'Menu preview'}
                        className="mb-4 h-40 w-full rounded-md border border-[#e3e3e0] bg-[#FDFDFC] dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                    />

                    <div className="space-y-4">
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
                                placeholder="e.g. Sundae"
                            />
                            <FieldError message={form.errors.name} />
                        </div>

                        <div>
                            <label htmlFor="price" className={labelClassName}>
                                Price
                            </label>
                            <input
                                id="price"
                                type="text"
                                inputMode="numeric"
                                value={form.data.price}
                                onChange={(event) =>
                                    form.setData(
                                        'price',
                                        sanitizePriceInput(event.target.value),
                                    )
                                }
                                className={inputClassName}
                                placeholder="0"
                            />
                            <FieldError message={form.errors.price} />
                        </div>

                        <label className="flex items-center gap-3">
                            <input
                                type="checkbox"
                                checked={form.data.is_available}
                                onChange={(event) =>
                                    form.setData(
                                        'is_available',
                                        event.target.checked,
                                    )
                                }
                                className="h-4 w-4 rounded border-[#e3e3e0] dark:border-[#3E3E3A]"
                            />
                            <span className="text-sm">Available</span>
                        </label>

                        <label className="flex items-center gap-3">
                            <input
                                type="checkbox"
                                checked={form.data.is_recommended}
                                onChange={(event) =>
                                    form.setData(
                                        'is_recommended',
                                        event.target.checked,
                                    )
                                }
                                className="h-4 w-4 rounded border-[#e3e3e0] dark:border-[#3E3E3A]"
                            />
                            <span className="text-sm">Recommended</span>
                        </label>
                    </div>
                </section>

                <section>
                    <div className="mb-4 flex items-center justify-between">
                        <h2 className="text-base font-semibold">Add-on groups</h2>
                        <button
                            type="button"
                            onClick={addGroup}
                            className="rounded-md border border-[#e3e3e0] px-3 py-1.5 text-sm font-medium dark:border-[#3E3E3A]"
                        >
                            Add group
                        </button>
                    </div>

                    {form.data.addon_groups.length === 0 ? (
                        <div className="rounded-lg border border-dashed border-[#e3e3e0] p-6 text-center text-sm text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]">
                            No add-on groups yet. Tap &quot;Add group&quot; to add
                            size, toppings, etc.
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {form.data.addon_groups.map((group, groupIndex) => (
                                <div
                                    key={groupIndex}
                                    className="rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]"
                                >
                                    <div className="mb-4 flex items-start justify-between gap-3">
                                        <p className="text-sm font-medium">
                                            Group {groupIndex + 1}
                                        </p>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                removeGroup(groupIndex)
                                            }
                                            className="text-xs text-[#b42318]"
                                        >
                                            Remove
                                        </button>
                                    </div>

                                    <div className="space-y-4">
                                        <div>
                                            <label className={labelClassName}>
                                                Group name
                                            </label>
                                            <input
                                                type="text"
                                                value={group.name}
                                                onChange={(event) =>
                                                    updateGroup(
                                                        groupIndex,
                                                        'name',
                                                        event.target.value,
                                                    )
                                                }
                                                className={inputClassName}
                                                placeholder="e.g. Size"
                                            />
                                            <FieldError
                                                message={
                                                    form.errors[
                                                        `addon_groups.${groupIndex}.name`
                                                    ]
                                                }
                                            />
                                        </div>

                                        <div>
                                            <p className={labelClassName}>
                                                Selection type
                                            </p>
                                            <div className="grid grid-cols-2 gap-2">
                                                {(
                                                    ['single', 'multiple'] as const
                                                ).map((type) => (
                                                    <button
                                                        key={type}
                                                        type="button"
                                                        onClick={() =>
                                                            updateGroup(
                                                                groupIndex,
                                                                'selection_type',
                                                                type,
                                                            )
                                                        }
                                                        className={
                                                            group.selection_type ===
                                                            type
                                                                ? 'rounded-md border border-[#1b1b18] bg-[#1b1b18] px-3 py-2 text-sm text-white dark:border-[#EDEDEC] dark:bg-[#EDEDEC] dark:text-[#1b1b18]'
                                                                : 'rounded-md border border-[#e3e3e0] px-3 py-2 text-sm dark:border-[#3E3E3A]'
                                                        }
                                                    >
                                                        {type === 'single'
                                                            ? 'Single'
                                                            : 'Multiple'}
                                                    </button>
                                                ))}
                                            </div>
                                            <FieldError
                                                message={
                                                    form.errors[
                                                        `addon_groups.${groupIndex}.selection_type`
                                                    ]
                                                }
                                            />
                                        </div>

                                        <label className="flex items-center gap-3">
                                            <input
                                                type="checkbox"
                                                checked={group.is_required}
                                                onChange={(event) =>
                                                    updateGroup(
                                                        groupIndex,
                                                        'is_required',
                                                        event.target.checked,
                                                    )
                                                }
                                                className="h-4 w-4 rounded border-[#e3e3e0] dark:border-[#3E3E3A]"
                                            />
                                            <span className="text-sm">
                                                Required
                                            </span>
                                        </label>

                                        <div>
                                            <div className="mb-2 flex items-center justify-between">
                                                <p className="text-sm font-medium">
                                                    Options
                                                </p>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        addOption(groupIndex)
                                                    }
                                                    className="text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]"
                                                >
                                                    Add option
                                                </button>
                                            </div>

                                            <div className="space-y-3">
                                                {group.options.map(
                                                    (option, optionIndex) => (
                                                        <div
                                                            key={optionIndex}
                                                            className="rounded-md border border-[#e3e3e0] p-3 dark:border-[#3E3E3A]"
                                                        >
                                                            <div className="mb-2 flex items-center justify-between">
                                                                <span className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                                                    Option{' '}
                                                                    {optionIndex +
                                                                        1}
                                                                </span>
                                                                {group.options
                                                                    .length >
                                                                    1 && (
                                                                    <button
                                                                        type="button"
                                                                        onClick={() =>
                                                                            removeOption(
                                                                                groupIndex,
                                                                                optionIndex,
                                                                            )
                                                                        }
                                                                        className="text-xs text-[#b42318]"
                                                                    >
                                                                        Remove
                                                                    </button>
                                                                )}
                                                            </div>
                                                            <div className="grid gap-3 sm:grid-cols-2">
                                                                <div>
                                                                    <input
                                                                        type="text"
                                                                        value={
                                                                            option.name
                                                                        }
                                                                        onChange={(
                                                                            event,
                                                                        ) =>
                                                                            updateOption(
                                                                                groupIndex,
                                                                                optionIndex,
                                                                                'name',
                                                                                event
                                                                                    .target
                                                                                    .value,
                                                                            )
                                                                        }
                                                                        className={
                                                                            inputClassName
                                                                        }
                                                                        placeholder="Option name"
                                                                    />
                                                                    <FieldError
                                                                        message={
                                                                            form
                                                                                .errors[
                                                                                `addon_groups.${groupIndex}.options.${optionIndex}.name`
                                                                            ]
                                                                        }
                                                                    />
                                                                </div>
                                                                <div>
                                                                    <input
                                                                        type="text"
                                                                        inputMode="numeric"
                                                                        value={
                                                                            option.price
                                                                        }
                                                                        onChange={(
                                                                            event,
                                                                        ) =>
                                                                            updateOption(
                                                                                groupIndex,
                                                                                optionIndex,
                                                                                'price',
                                                                                sanitizePriceInput(
                                                                                    event
                                                                                        .target
                                                                                        .value,
                                                                                ),
                                                                            )
                                                                        }
                                                                        className={
                                                                            inputClassName
                                                                        }
                                                                        placeholder="0"
                                                                    />
                                                                    <FieldError
                                                                        message={
                                                                            form
                                                                                .errors[
                                                                                `addon_groups.${groupIndex}.options.${optionIndex}.price`
                                                                            ]
                                                                        }
                                                                    />
                                                                </div>
                                                            </div>
                                                            <label className="mt-3 flex items-center gap-3">
                                                                <input
                                                                    type="checkbox"
                                                                    checked={
                                                                        option.is_available
                                                                    }
                                                                    onChange={(
                                                                        event,
                                                                    ) =>
                                                                        updateOption(
                                                                            groupIndex,
                                                                            optionIndex,
                                                                            'is_available',
                                                                            event
                                                                                .target
                                                                                .checked,
                                                                        )
                                                                    }
                                                                    className="h-4 w-4 rounded border-[#e3e3e0] dark:border-[#3E3E3A]"
                                                                />
                                                                <span className="text-sm">
                                                                    Available
                                                                </span>
                                                            </label>
                                                        </div>
                                                    ),
                                                )}
                                            </div>
                                            <FieldError
                                                message={
                                                    form.errors[
                                                        `addon_groups.${groupIndex}.options`
                                                    ]
                                                }
                                            />
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </section>
            </form>

            <div className="fixed inset-x-0 bottom-16 z-20 border-t border-[#e3e3e0] bg-[#FDFDFC]/95 px-4 py-4 backdrop-blur dark:border-[#3E3E3A] dark:bg-[#0a0a0a]/95">
                <div className="mx-auto flex max-w-2xl gap-3">
                    <Link
                        href={backHref}
                        className="flex flex-1 items-center justify-center rounded-md border border-[#e3e3e0] px-4 py-3 text-sm font-medium dark:border-[#3E3E3A]"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        form={formId}
                        disabled={form.processing}
                        className="flex flex-1 items-center justify-center rounded-md bg-[#1b1b18] px-4 py-3 text-sm font-medium text-white disabled:opacity-50 dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
                    >
                        {form.processing ? 'Saving...' : submitLabel}
                    </button>
                </div>
            </div>
        </>
    );
}

export function menuToForm(menu: {
    name: string;
    price: number;
    is_available: boolean;
    is_recommended: boolean;
    addon_groups: Array<{
        name: string;
        selection_type: 'single' | 'multiple';
        is_required: boolean;
        options: Array<{
            name: string;
            price: number;
            is_available: boolean;
        }>;
    }>;
}): MenuForm {
    return {
        name: menu.name,
        price: String(menu.price),
        is_available: menu.is_available,
        is_recommended: menu.is_recommended,
        addon_groups: menu.addon_groups.map((group) => ({
            name: group.name,
            selection_type: group.selection_type,
            is_required: group.is_required,
            options: group.options.map((option) => ({
                name: option.name,
                price: String(option.price),
                is_available: option.is_available,
            })),
        })),
    };
}
