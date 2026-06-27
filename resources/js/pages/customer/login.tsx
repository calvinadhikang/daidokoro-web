import { Head, Link, useForm } from '@inertiajs/react';

import {
    inputClassName,
    labelClassName,
} from '@/components/admin/menu-form';
import { cn } from '@/lib/utils';
import type { Customer, CustomerLoginForm } from '@/types/customer';
import { serviceTypeLabel } from '@/types/transaction';

type Props = {
    phonePrefix: string;
    serviceType: 'dine_in' | 'takeaway' | null;
    customer: Customer | null;
};

function FieldError({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return <p className="mt-1 text-xs text-[#b42318]">{message}</p>;
}

export default function CustomerLogin({
    phonePrefix,
    serviceType,
    customer,
}: Props) {
    const form = useForm<CustomerLoginForm>({
        name: customer?.name ?? '',
        phone: customer?.phone_local ?? '',
        service_type: serviceType ?? '',
    });

    function handleSubmit(event: React.FormEvent) {
        event.preventDefault();
        form.post('/customer/login');
    }

    const orderLabel =
        serviceType !== null ? serviceTypeLabel(serviceType) : null;

    return (
        <>
            <Head title="Your details" />

            <div className="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <main className="mx-auto flex w-full max-w-md flex-1 flex-col px-4 py-8">
                    <header className="mb-8">
                        <Link
                            href="/"
                            className="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            ← Back
                        </Link>
                        <h1 className="mt-4 text-2xl font-semibold">
                            Your details
                        </h1>
                        <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            {orderLabel !== null
                                ? `Continue your ${orderLabel.toLowerCase()} order`
                                : 'Enter your name and phone number to continue'}
                        </p>
                    </header>

                    <form
                        onSubmit={handleSubmit}
                        className="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]"
                    >
                        <div className="mb-4">
                            <label htmlFor="name" className={labelClassName}>
                                Name
                            </label>
                            <input
                                id="name"
                                type="text"
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                                className={inputClassName}
                                placeholder="Your name"
                                autoComplete="name"
                                required
                            />
                            <FieldError message={form.errors.name} />
                        </div>

                        <div className="mb-6">
                            <label htmlFor="phone" className={labelClassName}>
                                Phone number
                            </label>
                            <div className="flex">
                                <span
                                    className={cn(
                                        inputClassName,
                                        'w-auto shrink-0 rounded-r-none border-r-0 text-[#706f6c] dark:text-[#A1A09A]',
                                    )}
                                >
                                    {phonePrefix}
                                </span>
                                <input
                                    id="phone"
                                    type="tel"
                                    inputMode="numeric"
                                    value={form.data.phone}
                                    onChange={(event) =>
                                        form.setData(
                                            'phone',
                                            event.target.value,
                                        )
                                    }
                                    className={cn(
                                        inputClassName,
                                        'rounded-l-none',
                                    )}
                                    placeholder="81234567890"
                                    autoComplete="tel-national"
                                    required
                                />
                            </div>
                            <p className="mt-1.5 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                Enter your number without the leading 0.
                            </p>
                            <FieldError message={form.errors.phone} />
                        </div>

                        <button
                            type="submit"
                            disabled={form.processing}
                            className="w-full rounded-md border border-[#1b1b18] bg-[#1b1b18] px-4 py-3 text-sm font-medium text-white disabled:opacity-50 dark:border-[#EDEDEC] dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
                        >
                            {form.processing ? 'Saving...' : 'Continue'}
                        </button>
                    </form>
                </main>
            </div>
        </>
    );
}
