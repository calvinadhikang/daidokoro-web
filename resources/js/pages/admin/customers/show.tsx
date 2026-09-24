import { Head, Link, useForm } from '@inertiajs/react';

import {
    index as customersIndex,
    update,
} from '@/actions/App/Http/Controllers/CustomerController';
import { AdminTransactionListCard } from '@/components/admin/transaction-list-card';
import { PhoneInput } from '@/components/phone-input';
import { DEFAULT_PHONE_REGION } from '@/lib/phone-countries';
import {
    inputClassName,
    labelClassName,
} from '@/components/admin/menu-form';
import type { Customer, CustomerAdminForm } from '@/types/customer';
import type { Transaction } from '@/types/transaction';

type Props = {
    customer: Customer;
    transactions: Transaction[];
};

function FieldError({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return <p className="mt-1 text-xs text-[#b42318]">{message}</p>;
}

export default function AdminCustomersShow({
    customer,
    transactions,
}: Props) {
    const form = useForm<CustomerAdminForm>({
        name: customer.name,
        phone: customer.phone_local,
        phone_country: customer.phone_country ?? DEFAULT_PHONE_REGION,
    });

    function handleSubmit(event: React.FormEvent) {
        event.preventDefault();
        form.put(update.url(customer.id));
    }

    return (
        <>
            <Head title={customer.name} />

            <header className="sticky top-0 z-10 border-b border-[#e3e3e0] bg-[#FDFDFC]/95 px-4 py-4 backdrop-blur dark:border-[#3E3E3A] dark:bg-[#0a0a0a]/95">
                <div className="mx-auto flex max-w-lg items-center gap-3">
                    <Link
                        href={customersIndex.url()}
                        className="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Back
                    </Link>
                    <h1 className="truncate text-lg font-semibold">
                        {customer.name}
                    </h1>
                </div>
            </header>

            <form
                id="customer-edit-form"
                onSubmit={handleSubmit}
                className="mx-auto max-w-lg px-4 py-6 pb-36"
            >
                <section className="rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <label htmlFor="customer-name" className={labelClassName}>
                        Name
                    </label>
                    <input
                        id="customer-name"
                        type="text"
                        value={form.data.name}
                        onChange={(event) =>
                            form.setData('name', event.target.value)
                        }
                        className={inputClassName}
                        autoComplete="name"
                    />
                    <FieldError message={form.errors.name} />

                    <label
                        htmlFor="customer-phone"
                        className={`${labelClassName} mt-4`}
                    >
                        Phone
                    </label>
                    <PhoneInput
                        id="customer-phone"
                        country={form.data.phone_country}
                        nationalNumber={form.data.phone}
                        onCountryChange={(region) =>
                            form.setData('phone_country', region)
                        }
                        onNationalNumberChange={(value) =>
                            form.setData('phone', value)
                        }
                    />
                    <p className="mt-1.5 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                        Changing this also updates matching past orders.
                    </p>
                    <FieldError message={form.errors.phone} />
                </section>

                <section className="mt-6">
                    <h2 className="mb-3 text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">
                        {transactions.length}{' '}
                        {transactions.length === 1
                            ? 'transaction'
                            : 'transactions'}
                    </h2>

                    {transactions.length === 0 ? (
                        <div className="rounded-lg border border-[#e3e3e0] bg-white p-10 text-center dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <p className="text-[#706f6c] dark:text-[#A1A09A]">
                                No transactions for this customer yet.
                            </p>
                        </div>
                    ) : (
                        <ul className="space-y-3">
                            {transactions.map((transaction) => (
                                <li key={transaction.id}>
                                    <AdminTransactionListCard
                                        transaction={transaction}
                                    />
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </form>

            <div className="fixed inset-x-0 bottom-16 z-20 border-t border-[#e3e3e0] bg-[#FDFDFC]/95 px-4 py-3 backdrop-blur dark:border-[#3E3E3A] dark:bg-[#0a0a0a]/95">
                <div className="mx-auto flex max-w-lg gap-3">
                    <Link
                        href={customersIndex.url()}
                        className="flex flex-1 items-center justify-center rounded-md border border-[#e3e3e0] px-4 py-3 text-sm font-medium dark:border-[#3E3E3A]"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        form="customer-edit-form"
                        disabled={form.processing}
                        className="flex flex-1 items-center justify-center rounded-md bg-[#1b1b18] px-4 py-3 text-sm font-medium text-white disabled:opacity-50 dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
                    >
                        {form.processing ? 'Saving...' : 'Update customer'}
                    </button>
                </div>
            </div>
        </>
    );
}
