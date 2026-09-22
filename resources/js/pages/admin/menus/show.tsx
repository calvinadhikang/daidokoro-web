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
import type { SalesChannelOption } from '@/types/sales-channel';

type Props = {
    menu: Menu;
    channels: SalesChannelOption[];
    assignedChannelIds: number[];
};

export default function AdminMenusShow({
    menu,
    channels,
    assignedChannelIds,
}: Props) {
    const form = useForm<MenuForm>(
        menuToForm({ ...menu, sales_channel_ids: assignedChannelIds }),
    );

    function handleSubmit(event: React.FormEvent) {
        event.preventDefault();
        form.transform((data) => ({
            ...normalizeMenuFormForSubmit(data),
            _method: 'put',
        }));
        form.post(update.url(menu.id), {
            forceFormData: true,
        });
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
                channels={channels}
                onSubmit={handleSubmit}
            />
        </>
    );
}
