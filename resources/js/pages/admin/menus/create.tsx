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
import type { SalesChannelOption } from '@/types/sales-channel';

type Props = {
    channels: SalesChannelOption[];
    defaultChannelIds: number[];
};

export default function AdminMenusCreate({
    channels,
    defaultChannelIds,
}: Props) {
    const form = useForm<MenuForm>({
        name: '',
        price: '',
        pricing_type: 'standard',
        is_available: true,
        is_recommended: false,
        sales_channel_ids: defaultChannelIds,
        addon_groups: [],
        image: null,
        remove_image: false,
    });

    function handleSubmit(event: React.FormEvent) {
        event.preventDefault();
        form.transform((data) => normalizeMenuFormForSubmit(data));
        form.post(store.url(), { forceFormData: true });
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
                channels={channels}
                onSubmit={handleSubmit}
            />
        </>
    );
}
