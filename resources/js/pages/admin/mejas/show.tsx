import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

import {
    destroy,
    index as mejasIndex,
    update,
} from '@/actions/App/Http/Controllers/MejaController';
import {
    MejaFormFields,
    mejaToForm,
} from '@/components/admin/meja-form';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { MejaQrCard } from '@/components/admin/meja-qr-card';
import type { Meja, MejaForm } from '@/types/meja';

type Props = {
    meja: Meja;
    qrUrl: string;
};

export default function AdminMejasShow({ meja, qrUrl }: Props) {
    const form = useForm<MejaForm>(mejaToForm(meja));
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [deleteLoading, setDeleteLoading] = useState(false);

    function handleSubmit(event: React.FormEvent) {
        event.preventDefault();
        form.put(update.url(meja.id));
    }

    function handleConfirmDelete() {
        setDeleteLoading(true);
        router.delete(destroy.url(meja.id), {
            onFinish: () => {
                setDeleteLoading(false);
                setDeleteOpen(false);
            },
        });
    }

    return (
        <>
            <Head title={`Table ${meja.code}`} />

            <ConfirmDialog
                open={deleteOpen}
                title="Delete table?"
                description={`Delete table "${meja.code}"? Past orders that used this table code will keep their recorded code.`}
                confirmLabel="Delete"
                variant="danger"
                loading={deleteLoading}
                onConfirm={handleConfirmDelete}
                onCancel={() => setDeleteOpen(false)}
            />

            <MejaFormFields
                form={form}
                formId="meja-edit-form"
                title={`Table ${meja.code}`}
                backHref={mejasIndex.url()}
                submitLabel="Update table"
                onSubmit={handleSubmit}
                afterFields={
                    <MejaQrCard
                        url={qrUrl}
                        filename={`table-${meja.code}-qr.png`}
                    />
                }
                deleteAction={
                    <button
                        type="button"
                        onClick={() => setDeleteOpen(true)}
                        className="w-full rounded-md border border-[#fda29b] px-4 py-3 text-sm font-medium text-[#b42318] dark:border-[#912018] dark:text-[#fda29b]"
                    >
                        Delete table
                    </button>
                }
            />
        </>
    );
}
