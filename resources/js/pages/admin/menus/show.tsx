import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

import {
    destroy,
    index as menusIndex,
    update,
} from '@/actions/App/Http/Controllers/MenuController';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
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
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [deleteLoading, setDeleteLoading] = useState(false);

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

    function handleConfirmDelete() {
        setDeleteLoading(true);
        router.delete(destroy.url(menu.id), {
            onFinish: () => {
                setDeleteLoading(false);
                setDeleteOpen(false);
            },
        });
    }

    return (
        <>
            <Head title={menu.name} />

            <ConfirmDialog
                open={deleteOpen}
                title="Delete menu?"
                description={`Delete "${menu.name}"? This cannot be undone.`}
                confirmLabel="Delete"
                variant="danger"
                loading={deleteLoading}
                onConfirm={handleConfirmDelete}
                onCancel={() => setDeleteOpen(false)}
            />

            <MenuFormFields
                form={form}
                formId="menu-edit-form"
                title={menu.name}
                backHref={menusIndex.url()}
                submitLabel="Update menu"
                imageSrc={menu.image}
                channels={channels}
                onSubmit={handleSubmit}
                deleteAction={
                    <button
                        type="button"
                        onClick={() => setDeleteOpen(true)}
                        className="w-full rounded-md border border-[#fda29b] px-4 py-3 text-sm font-medium text-[#b42318] dark:border-[#912018] dark:text-[#fda29b]"
                    >
                        Delete menu
                    </button>
                }
            />
        </>
    );
}
