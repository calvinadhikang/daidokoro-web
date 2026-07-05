import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';

import { CustomerNavbar } from '@/components/customer/customer-navbar';

type CustomerLayoutProps = {
    children: ReactNode;
};

type PageProps = {
    flash: {
        success?: string;
    };
};

export default function CustomerLayout({ children }: CustomerLayoutProps) {
    const { props } = usePage<PageProps>();
    const { flash } = props;

    return (
        <div className="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
            {flash.success && (
                <div className="px-4 pt-4">
                    <div className="mx-auto max-w-md rounded-lg border border-[#abefc6] bg-[#ecfdf3] px-4 py-3 text-sm text-[#027a48] dark:border-[#053321] dark:bg-[#053321] dark:text-[#75e0a7]">
                        {flash.success}
                    </div>
                </div>
            )}

            <main className="pb-24">{children}</main>

            <CustomerNavbar />
        </div>
    );
}
