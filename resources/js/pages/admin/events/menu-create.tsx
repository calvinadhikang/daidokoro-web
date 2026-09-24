import { Head, useForm } from '@inertiajs/react';

import {
    MenuFormFields,
    normalizeMenuFormForSubmit,
} from '@/components/admin/menu-form';
import type { MenuForm } from '@/types/menu';
import type { SalesChannel } from '@/types/sales-channel';

type Props = {
    channel: SalesChannel;
};

export default function AdminEventMenuCreate({ channel }: Props) {
    const form = useForm<MenuForm>({
        name: '',
        price: '',
        pricing_type: 'standard',
        is_available: true,
        is_recommended: false,
        sales_channel_ids: [channel.id],
        addon_groups: [],
        image: null,
        remove_image: false,
    });

    function handleSubmit(event: React.FormEvent) {
        event.preventDefault();
        form.transform((data) => normalizeMenuFormForSubmit(data));
        form.post(`/admin/events/${channel.id}/menus`, { forceFormData: true });
    }

    return (
        <>
            <Head title={`New menu · ${channel.name}`} />
            <MenuFormFields
                form={form}
                formId="event-menu-create-form"
                title="New event menu"
                description={`Sold only at ${channel.name}`}
                backHref={`/admin/events/${channel.id}`}
                submitLabel="Save event menu"
                onSubmit={handleSubmit}
            />
        </>
    );
}
