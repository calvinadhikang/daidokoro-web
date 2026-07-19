import { Link } from '@inertiajs/react';
import type { useForm } from '@inertiajs/react';

import {
    inputClassName,
    labelClassName,
} from '@/components/admin/menu-form';
import type { MejaForm } from '@/types/meja';

type InertiaMejaForm = ReturnType<typeof useForm<MejaForm>>;

function FieldError({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return <p className="mt-1 text-xs text-[#b42318]">{message}</p>;
}

type MejaFormProps = {
    form: InertiaMejaForm;
    formId: string;
    title: string;
    backHref: string;
    submitLabel: string;
    onSubmit: (event: React.FormEvent) => void;
    deleteAction?: React.ReactNode;
    afterFields?: React.ReactNode;
};

export function MejaFormFields({
    form,
    formId,
    title,
    backHref,
    submitLabel,
    onSubmit,
    deleteAction,
    afterFields,
}: MejaFormProps) {
    return (
        <>
            <header className="sticky top-0 z-10 border-b border-[#e3e3e0] bg-[#FDFDFC]/95 px-4 py-4 backdrop-blur dark:border-[#3E3E3A] dark:bg-[#0a0a0a]/95">
                <div className="mx-auto flex max-w-lg items-center gap-3">
                    <Link
                        href={backHref}
                        className="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Back
                    </Link>
                    <h1 className="text-lg font-semibold">{title}</h1>
                </div>
            </header>

            <form
                id={formId}
                onSubmit={onSubmit}
                className="mx-auto max-w-lg px-4 py-6 pb-36"
            >
                <section className="rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <label htmlFor="meja-code" className={labelClassName}>
                        Table code
                    </label>
                    <input
                        id="meja-code"
                        type="text"
                        value={form.data.code}
                        onChange={(event) =>
                            form.setData('code', event.target.value)
                        }
                        className={inputClassName}
                        autoComplete="off"
                        placeholder="e.g. A1"
                    />
                    <p className="mt-1.5 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                        Letters, numbers, dots, underscores, and hyphens. Shown
                        on the table QR and recorded on customer orders.
                    </p>
                    <FieldError message={form.errors.code} />
                </section>

                {afterFields}

                {deleteAction && (
                    <section className="mt-4">{deleteAction}</section>
                )}
            </form>

            <div className="fixed inset-x-0 bottom-16 z-20 border-t border-[#e3e3e0] bg-[#FDFDFC]/95 px-4 py-3 backdrop-blur dark:border-[#3E3E3A] dark:bg-[#0a0a0a]/95">
                <div className="mx-auto flex max-w-lg gap-3">
                    <Link
                        href={backHref}
                        className="flex flex-1 items-center justify-center rounded-md border border-[#e3e3e0] px-4 py-3 text-sm font-medium dark:border-[#3E3E3A]"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        form={formId}
                        disabled={form.processing}
                        className="flex flex-1 items-center justify-center rounded-md bg-[#1b1b18] px-4 py-3 text-sm font-medium text-white disabled:opacity-50 dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
                    >
                        {form.processing ? 'Saving...' : submitLabel}
                    </button>
                </div>
            </div>
        </>
    );
}

export function mejaToForm(meja: { code: string }): MejaForm {
    return {
        code: meja.code,
    };
}
