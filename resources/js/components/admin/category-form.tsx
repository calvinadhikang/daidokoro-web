import { Link } from '@inertiajs/react';
import type { useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import {
    inputClassName,
    labelClassName,
} from '@/components/admin/menu-form';
import { cn } from '@/lib/utils';
import type { CategoryForm, CategoryMenu } from '@/types/category';

type InertiaCategoryForm = ReturnType<typeof useForm<CategoryForm>>;

function FieldError({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return <p className="mt-1 text-xs text-[#b42318]">{message}</p>;
}

type CategoryFormProps = {
    form: InertiaCategoryForm;
    formId: string;
    title: string;
    backHref: string;
    submitLabel: string;
    menus: CategoryMenu[];
    onSubmit: (event: React.FormEvent) => void;
    deleteAction?: React.ReactNode;
};

export function CategoryFormFields({
    form,
    formId,
    title,
    backHref,
    submitLabel,
    menus,
    onSubmit,
    deleteAction,
}: CategoryFormProps) {
    const [menuSearch, setMenuSearch] = useState('');

    const filteredMenus = useMemo(() => {
        const query = menuSearch.trim().toLowerCase();

        if (query === '') {
            return menus;
        }

        return menus.filter((menu) =>
            menu.name.toLowerCase().includes(query),
        );
    }, [menus, menuSearch]);

    function toggleMenu(menuId: number) {
        const selected = form.data.menu_ids.includes(menuId);

        form.setData(
            'menu_ids',
            selected
                ? form.data.menu_ids.filter((id) => id !== menuId)
                : [...form.data.menu_ids, menuId],
        );
    }

    return (
        <>
            <header className="sticky top-0 z-10 border-b border-[#e3e3e0] bg-[#FDFDFC]/95 px-4 py-4 backdrop-blur dark:border-[#3E3E3A] dark:bg-[#0a0a0a]/95">
                <div className="mx-auto flex max-w-lg items-center gap-3">
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
                className="mx-auto max-w-lg px-4 py-6 pb-36"
            >
                <section className="rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <label htmlFor="category-name" className={labelClassName}>
                        Category name
                    </label>
                    <input
                        id="category-name"
                        type="text"
                        value={form.data.name}
                        onChange={(event) =>
                            form.setData('name', event.target.value)
                        }
                        className={inputClassName}
                        autoComplete="off"
                    />
                    <FieldError message={form.errors.name} />
                </section>

                <section className="mt-4 rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <div className="mb-3 flex items-center justify-between gap-3">
                        <div>
                            <p className="text-sm font-medium">Menus</p>
                            <p className="mt-0.5 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                {form.data.menu_ids.length} selected
                            </p>
                        </div>
                    </div>

                    <input
                        type="search"
                        value={menuSearch}
                        onChange={(event) => setMenuSearch(event.target.value)}
                        placeholder="Search menus..."
                        className={`${inputClassName} mb-3`}
                    />

                    {menus.length === 0 ? (
                        <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            No menus available yet.
                        </p>
                    ) : filteredMenus.length === 0 ? (
                        <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            No menus match your search.
                        </p>
                    ) : (
                        <ul className="max-h-80 space-y-2 overflow-y-auto">
                            {filteredMenus.map((menu) => {
                                const checked = form.data.menu_ids.includes(
                                    menu.id,
                                );

                                return (
                                    <li key={menu.id}>
                                        <label
                                            className={cn(
                                                'flex cursor-pointer items-center gap-3 rounded-md border px-3 py-2.5',
                                                checked
                                                    ? 'border-[#1b1b18] bg-[#FDFDFC] dark:border-[#EDEDEC] dark:bg-[#0a0a0a]'
                                                    : 'border-[#e3e3e0] dark:border-[#3E3E3A]',
                                            )}
                                        >
                                            <input
                                                type="checkbox"
                                                checked={checked}
                                                onChange={() =>
                                                    toggleMenu(menu.id)
                                                }
                                                className="size-4 rounded border-[#e3e3e0] dark:border-[#3E3E3A]"
                                            />
                                            <span className="min-w-0 flex-1 truncate text-sm">
                                                {menu.name}
                                            </span>
                                            {!menu.is_available && (
                                                <span className="shrink-0 rounded-full bg-[#fef3f2] px-2 py-0.5 text-[10px] font-medium text-[#b42318] dark:bg-[#55160c] dark:text-[#fda29b]">
                                                    Unavailable
                                                </span>
                                            )}
                                        </label>
                                    </li>
                                );
                            })}
                        </ul>
                    )}

                    <FieldError message={form.errors.menu_ids} />
                </section>

                {deleteAction && (
                    <section className="mt-4">{deleteAction}</section>
                )}
            </form>

            <div className="fixed inset-x-0 bottom-16 z-20 border-t border-[#e3e3e0] bg-[#FDFDFC]/95 px-4 py-3 backdrop-blur dark:border-[#3E3E3A] dark:bg-[#0a0a0a]/95">
                <div className="mx-auto flex max-w-lg gap-3">
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

export function categoryToForm(category: {
    name: string;
    menus: Array<{ id: number }>;
}): CategoryForm {
    return {
        name: category.name,
        menu_ids: category.menus.map((menu) => menu.id),
    };
}
