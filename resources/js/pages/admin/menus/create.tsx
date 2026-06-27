import { Head, useForm } from '@inertiajs/react';

import {
    index as menusIndex,
    store,
} from '@/actions/App/Http/Controllers/MenuController';
import {
    MenuFormFields,
    normalizeMenuFormForSubmit,
} from '@/components/admin/menu-form';
import type { MenuForm } from '@/types/menu';

export default function AdminMenusCreate() {
    const form = useForm<MenuForm>({
        name: '',
        price: '',
        is_available: true,
        is_recommended: false,
        addon_groups: [],
    });

    function handleSubmit(event: React.FormEvent) {
        event.preventDefault();
        form.transform((data) => normalizeMenuFormForSubmit(data));
        form.post(store.url());
    }

    return (
        <>
            <Head title="New Menu" />
            <MenuFormFields
                form={form}
                formId="menu-create-form"
                title="New Menu"
                backHref={menusIndex.url()}
                submitLabel="Save menu"
                onSubmit={handleSubmit}
            />
        </>
    );
}
