import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

import {
    destroy,
    index as categoriesIndex,
    update,
} from '@/actions/App/Http/Controllers/CategoryController';
import {
    CategoryFormFields,
    categoryToForm,
} from '@/components/admin/category-form';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import type { Category, CategoryForm, CategoryMenu } from '@/types/category';

type Props = {
    category: Category;
    menus: CategoryMenu[];
};

export default function AdminCategoriesShow({ category, menus }: Props) {
    const form = useForm<CategoryForm>(categoryToForm(category));
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [deleteLoading, setDeleteLoading] = useState(false);

    function handleSubmit(event: React.FormEvent) {
        event.preventDefault();
        form.put(update.url(category.id));
    }

    function handleConfirmDelete() {
        setDeleteLoading(true);
        router.delete(destroy.url(category.id), {
            onFinish: () => {
                setDeleteLoading(false);
                setDeleteOpen(false);
            },
        });
    }

    return (
        <>
            <Head title={category.name} />

            <ConfirmDialog
                open={deleteOpen}
                title="Delete category?"
                description={`Delete "${category.name}"? Menus in this category will not be deleted.`}
                confirmLabel="Delete"
                variant="danger"
                loading={deleteLoading}
                onConfirm={handleConfirmDelete}
                onCancel={() => setDeleteOpen(false)}
            />

            <CategoryFormFields
                form={form}
                formId="category-edit-form"
                title={category.name}
                backHref={categoriesIndex.url()}
                submitLabel="Update category"
                menus={menus}
                onSubmit={handleSubmit}
                deleteAction={
                    <button
                        type="button"
                        onClick={() => setDeleteOpen(true)}
                        className="w-full rounded-md border border-[#fda29b] px-4 py-3 text-sm font-medium text-[#b42318] dark:border-[#912018] dark:text-[#fda29b]"
                    >
                        Delete category
                    </button>
                }
            />
        </>
    );
}
