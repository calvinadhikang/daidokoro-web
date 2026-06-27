import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import {
    create as createCategory,
    show as showCategory,
} from '@/actions/App/Http/Controllers/CategoryController';
import { inputClassName } from '@/components/admin/menu-form';
import type { Category } from '@/types/category';

type Props = {
    categories: Category[];
};

function CategoryListItem({ category }: { category: Category }) {
    const menuPreview = category.menus
        .slice(0, 3)
        .map((menu) => menu.name)
        .join(', ');

    return (
        <Link
            href={showCategory.url(category.id)}
            className="block rounded-lg border border-[#e3e3e0] bg-white p-4 active:bg-[#FDFDFC] dark:border-[#3E3E3A] dark:bg-[#161615] dark:active:bg-[#0a0a0a]"
        >
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0 flex-1">
                    <p className="truncate font-medium">{category.name}</p>
                    {category.menus_count === 0 ? (
                        <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            No menus assigned
                        </p>
                    ) : (
                        <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            {menuPreview}
                            {(category.menus_count ?? category.menus.length) >
                                3 && '…'}
                        </p>
                    )}
                </div>
                <span className="shrink-0 rounded-full bg-[#eff8ff] px-2.5 py-1 text-xs font-medium text-[#175cd3] dark:bg-[#102a56] dark:text-[#84caff]">
                    {category.menus_count ?? category.menus.length}{' '}
                    {(category.menus_count ?? category.menus.length) === 1
                        ? 'menu'
                        : 'menus'}
                </span>
            </div>
        </Link>
    );
}

export default function AdminCategoriesIndex({ categories }: Props) {
    const [search, setSearch] = useState('');

    const filteredCategories = useMemo(() => {
        const query = search.trim().toLowerCase();

        if (query === '') {
            return categories;
        }

        return categories.filter((category) =>
            category.name.toLowerCase().includes(query),
        );
    }, [categories, search]);

    return (
        <>
            <Head title="Categories" />
            <div className="flex h-[calc(100dvh-7.5rem)] flex-col px-4 py-4">
                <div className="mx-auto flex w-full max-w-lg shrink-0 flex-col">
                    <header className="mb-4">
                        <h1 className="text-2xl font-semibold">Categories</h1>
                        <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            {search.trim() !== ''
                                ? `${filteredCategories.length} of ${categories.length} categories`
                                : `${categories.length} ${categories.length === 1 ? 'category' : 'categories'}`}
                        </p>
                    </header>

                    <input
                        type="search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search categories..."
                        className={`${inputClassName} mb-4`}
                    />

                    <Link
                        href={createCategory.url()}
                        className="mb-4 flex w-full items-center justify-center rounded-md bg-[#1b1b18] px-4 py-3 text-sm font-medium text-white dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
                    >
                        Add Category
                    </Link>
                </div>

                <div className="mx-auto min-h-0 w-full max-w-lg flex-1 overflow-y-auto overscroll-contain">
                    {categories.length === 0 ? (
                        <div className="rounded-lg border border-[#e3e3e0] bg-white p-10 text-center dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <p className="text-[#706f6c] dark:text-[#A1A09A]">
                                No categories yet.
                            </p>
                        </div>
                    ) : filteredCategories.length === 0 ? (
                        <div className="rounded-lg border border-[#e3e3e0] bg-white p-10 text-center dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <p className="text-[#706f6c] dark:text-[#A1A09A]">
                                No categories match your search.
                            </p>
                        </div>
                    ) : (
                        <ul className="space-y-3 pb-4">
                            {filteredCategories.map((category) => (
                                <li key={category.id}>
                                    <CategoryListItem category={category} />
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </>
    );
}
