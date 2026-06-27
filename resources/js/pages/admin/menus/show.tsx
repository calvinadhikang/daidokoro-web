import { Head, useForm } from '@inertiajs/react';

import {
    index as menusIndex,
    update,
} from '@/actions/App/Http/Controllers/MenuController';
import {
    MenuFormFields,
    menuToForm,
    normalizeMenuFormForSubmit,
} from '@/components/admin/menu-form';
import type { Menu, MenuForm } from '@/types/menu';

type Props = {
    menu: Menu;
};

export default function AdminMenusShow({ menu }: Props) {
    const form = useForm<MenuForm>(menuToForm(menu));

    function handleSubmit(event: React.FormEvent) {
        event.preventDefault();
        form.transform((data) => normalizeMenuFormForSubmit(data));
        form.put(update.url(menu.id));
    }

    return (
        <>
            <Head title={menu.name} />
            <MenuFormFields
                form={form}
                formId="menu-edit-form"
                title={menu.name}
                backHref={menusIndex.url()}
                submitLabel="Update menu"
                imageSrc={menu.image}
                onSubmit={handleSubmit}
            />
        </>
    );
}
