import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import { show as showCustomer } from '@/actions/App/Http/Controllers/CustomerController';
import { inputClassName } from '@/components/admin/menu-form';
import type { Customer } from '@/types/customer';

type Props = {
    customers: Customer[];
};

function digitsOnly(value: string): string {
    return value.replace(/\D/g, '');
}

function CustomerListItem({ customer }: { customer: Customer }) {
    const transactionCount = customer.transactions_count ?? 0;

    return (
        <Link
            href={showCustomer.url(customer.id)}
            className="block rounded-lg border border-[#e3e3e0] bg-white p-4 active:bg-[#FDFDFC] dark:border-[#3E3E3A] dark:bg-[#161615] dark:active:bg-[#0a0a0a]"
        >
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0 flex-1">
                    <p className="truncate font-medium">{customer.name}</p>
                    <p className="mt-1 text-sm tabular-nums text-[#706f6c] dark:text-[#A1A09A]">
                        {customer.phone_display}
                    </p>
                </div>
                <span className="shrink-0 rounded-full bg-[#eff8ff] px-2.5 py-1 text-xs font-medium text-[#175cd3] dark:bg-[#102a56] dark:text-[#84caff]">
                    {transactionCount}{' '}
                    {transactionCount === 1 ? 'order' : 'orders'}
                </span>
            </div>
        </Link>
    );
}

export default function AdminCustomersIndex({ customers }: Props) {
    const [search, setSearch] = useState('');

    const filteredCustomers = useMemo(() => {
        const query = search.trim().toLowerCase();

        if (query === '') {
            return customers;
        }

        const queryDigits = digitsOnly(query);

        return customers.filter((customer) => {
            if (customer.name.toLowerCase().includes(query)) {
                return true;
            }

            if (
                customer.phone.includes(query) ||
                customer.phone_display.toLowerCase().includes(query) ||
                customer.phone_local.includes(query)
            ) {
                return true;
            }

            return (
                queryDigits !== '' &&
                digitsOnly(customer.phone).includes(queryDigits)
            );
        });
    }, [customers, search]);

    const isSearching = search.trim() !== '';

    return (
        <>
            <Head title="Customers" />
            <div className="flex h-[calc(100dvh-7.5rem)] flex-col px-4 py-4">
                <div className="mx-auto flex w-full max-w-lg shrink-0 flex-col">
                    <header className="mb-4">
                        <h1 className="text-2xl font-semibold">Customers</h1>
                        <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            {isSearching
                                ? `${filteredCustomers.length} of ${customers.length} customers`
                                : `${customers.length} ${customers.length === 1 ? 'customer' : 'customers'}`}
                        </p>
                    </header>

                    <input
                        type="search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search by name or phone..."
                        className={`${inputClassName} mb-4`}
                    />
                </div>

                <div className="mx-auto min-h-0 w-full max-w-lg flex-1 overflow-y-auto overscroll-contain">
                    {customers.length === 0 ? (
                        <div className="rounded-lg border border-[#e3e3e0] bg-white p-10 text-center dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <p className="text-[#706f6c] dark:text-[#A1A09A]">
                                No customers yet.
                            </p>
                        </div>
                    ) : filteredCustomers.length === 0 ? (
                        <div className="rounded-lg border border-[#e3e3e0] bg-white p-10 text-center dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <p className="text-[#706f6c] dark:text-[#A1A09A]">
                                No customers match &quot;{search.trim()}&quot;.
                            </p>
                        </div>
                    ) : (
                        <ul className="space-y-3 pb-4">
                            {filteredCustomers.map((customer) => (
                                <li key={customer.id}>
                                    <CustomerListItem customer={customer} />
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </>
    );
}
