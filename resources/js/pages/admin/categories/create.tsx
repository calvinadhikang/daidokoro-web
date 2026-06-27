import { Head, useForm } from '@inertiajs/react';

import {
    index as categoriesIndex,
    store,
} from '@/actions/App/Http/Controllers/CategoryController';
import { CategoryFormFields } from '@/components/admin/category-form';
import type { CategoryForm, CategoryMenu } from '@/types/category';

type Props = {
    menus: CategoryMenu[];
};

export default function AdminCategoriesCreate({ menus }: Props) {
    const form = useForm<CategoryForm>({
        name: '',
        menu_ids: [],
    });

    function handleSubmit(event: React.FormEvent) {
        event.preventDefault();
        form.post(store.url());
    }

    return (
        <>
            <Head title="New Category" />
            <CategoryFormFields
                form={form}
                formId="category-create-form"
                title="New Category"
                backHref={categoriesIndex.url()}
                submitLabel="Save category"
                menus={menus}
                onSubmit={handleSubmit}
            />
        </>
    );
}
