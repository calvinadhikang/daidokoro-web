import { Head, useForm } from '@inertiajs/react';

import {
    index as mejasIndex,
    store,
} from '@/actions/App/Http/Controllers/MejaController';
import { MejaFormFields } from '@/components/admin/meja-form';
import type { MejaForm } from '@/types/meja';

export default function AdminMejasCreate() {
    const form = useForm<MejaForm>({
        code: '',
    });

    function handleSubmit(event: React.FormEvent) {
        event.preventDefault();
        form.post(store.url());
    }

    return (
        <>
            <Head title="New Table" />
            <MejaFormFields
                form={form}
                formId="meja-create-form"
                title="New Table"
                backHref={mejasIndex.url()}
                submitLabel="Save table"
                onSubmit={handleSubmit}
            />
        </>
    );
}
