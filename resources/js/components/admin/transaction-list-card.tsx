import { Link } from '@inertiajs/react';

import { show as showTransaction } from '@/actions/App/Http/Controllers/TransactionController';
import { TableCodeBadge } from '@/components/admin/table-code-badge';
import type { Transaction } from '@/types/transaction';
import { serviceTypeLabel } from '@/types/transaction';

function formatPrice(price: number): string {
    return price.toLocaleString();
}

function statusLabel(status: Transaction['status']): string {
    return status === 'paid' ? 'Paid' : 'In progress';
}

function statusClassName(status: Transaction['status']): string {
    return status === 'paid'
        ? 'shrink-0 rounded-full bg-[#ecfdf3] px-2.5 py-1 text-xs font-medium text-[#027a48] dark:bg-[#053321] dark:text-[#75e0a7]'
        : 'shrink-0 rounded-full bg-[#fffaeb] px-2.5 py-1 text-xs font-medium text-[#b54708] dark:bg-[#4e1d09] dark:text-[#fec84b]';
}

const adminPillClassName =
    'shrink-0 rounded-full bg-[#eff8ff] px-2.5 py-1 text-xs font-medium text-[#175cd3] dark:bg-[#102a56] dark:text-[#84caff]';

export function AdminTransactionListCard({
    transaction,
}: {
    transaction: Transaction;
}) {
    return (
        <Link
            href={showTransaction.url(transaction.id)}
            className="block rounded-lg border border-[#e3e3e0] bg-white p-4 active:bg-[#FDFDFC] dark:border-[#3E3E3A] dark:bg-[#161615] dark:active:bg-[#0a0a0a]"
        >
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0 flex-1">
                    <p className="truncate font-medium">
                        #{transaction.transaction_number}
                        {' · '}
                        {transaction.customer_name}
                    </p>
                    <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        {transaction.customer_phone_display ??
                            transaction.customer_phone}
                        {' · '}
                        {serviceTypeLabel(transaction.service_type)}
                        {transaction.sales_channel?.type === 'event'
                            ? ` · ${transaction.sales_channel.name}`
                            : ''}
                    </p>
                </div>
                <div className="flex shrink-0 flex-col items-end gap-1.5">
                    <TableCodeBadge code={transaction.table_code} />
                    {transaction.sales_channel?.type === 'event' && (
                        <span className="shrink-0 rounded-full bg-[#fff7ed] px-2.5 py-1 text-xs font-medium text-[#c2410c] dark:bg-[#431407] dark:text-[#fdba74]">
                            {transaction.sales_channel.name}
                        </span>
                    )}
                    {transaction.is_admin_created && (
                        <span className={adminPillClassName}>Admin</span>
                    )}
                    <span className={statusClassName(transaction.status)}>
                        {statusLabel(transaction.status)}
                    </span>
                </div>
            </div>

            <p className="mt-3 tabular-nums text-sm font-medium">
                Total {formatPrice(transaction.total_bill)}
            </p>
        </Link>
    );
}
