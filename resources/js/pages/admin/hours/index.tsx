import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

import {
    destroyClosure,
    storeClosure,
    update,
} from '@/actions/App/Http/Controllers/OperationalHoursController';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import {
    ClosureAddForm,
    ClosureListItem,
    daysToForm,
    HoursPageHeader,
    HoursSectionTitle,
    normalizeOperatingHoursForSubmit,
    OperatingHoursFormFields,
    StoreStatusBanner,
} from '@/components/admin/operating-hours-form';
import type {
    OperatingClosure,
    OperatingHourDay,
    StoreStatus,
} from '@/types/operating-hours';

type Props = {
    days: OperatingHourDay[];
    closures: OperatingClosure[];
    storeStatus: StoreStatus;
    today: string;
};

export default function AdminHoursIndex({
    days,
    closures,
    storeStatus,
    today,
}: Props) {
    const hoursForm = useForm(daysToForm(days));
    const closureForm = useForm({
        starts_at: '',
        ends_at: '',
        label: '',
    });
    const [closureToRemove, setClosureToRemove] =
        useState<OperatingClosure | null>(null);
    const [removeLoading, setRemoveLoading] = useState(false);

    function handleHoursSubmit(event: React.FormEvent) {
        event.preventDefault();
        hoursForm.transform((data) => normalizeOperatingHoursForSubmit(data));
        hoursForm.put(update.url());
    }

    function handleClosureSubmit(event: React.FormEvent) {
        event.preventDefault();
        closureForm.post(storeClosure.url(), {
            preserveScroll: true,
            onSuccess: () => {
                closureForm.reset();
            },
        });
    }

    function handleConfirmRemoveClosure() {
        if (!closureToRemove) {
            return;
        }

        setRemoveLoading(true);
        router.delete(destroyClosure.url(closureToRemove.id), {
            preserveScroll: true,
            onFinish: () => {
                setRemoveLoading(false);
                setClosureToRemove(null);
            },
        });
    }

    return (
        <>
            <Head title="Operating Hours" />

            <ConfirmDialog
                open={closureToRemove !== null}
                title="Remove closed period?"
                description={
                    closureToRemove
                        ? `Remove this closed period from your schedule?`
                        : ''
                }
                confirmLabel="Remove"
                variant="danger"
                loading={removeLoading}
                onConfirm={handleConfirmRemoveClosure}
                onCancel={() => setClosureToRemove(null)}
            />

            <div className="mx-auto max-w-lg px-4 py-4 pb-36">
                <HoursPageHeader />
                <StoreStatusBanner status={storeStatus} />

                <HoursSectionTitle
                    title="Weekly schedule"
                    description="Each day can have one or two sessions. Toggle closed for regular days off."
                />

                <OperatingHoursFormFields
                    form={hoursForm}
                    formId="operating-hours-form"
                    onSubmit={handleHoursSubmit}
                />

                <section className="mt-10">
                    <HoursSectionTitle
                        title="Closed periods & holidays"
                        description="Date ranges when you will not accept orders. Use the same date for a single day."
                    />

                    {closures.length === 0 ? (
                        <p className="mb-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            No upcoming closed periods scheduled.
                        </p>
                    ) : (
                        <ul className="mb-4 space-y-2">
                            {closures.map((closure) => (
                                <li key={closure.id}>
                                    <ClosureListItem
                                        startsAt={closure.starts_at}
                                        endsAt={closure.ends_at}
                                        label={closure.label}
                                        removing={removeLoading}
                                        onRemove={() =>
                                            setClosureToRemove(closure)
                                        }
                                    />
                                </li>
                            ))}
                        </ul>
                    )}

                    <div className="rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <ClosureAddForm
                            form={closureForm}
                            today={today}
                            onSubmit={handleClosureSubmit}
                        />
                    </div>
                </section>
            </div>
        </>
    );
}
