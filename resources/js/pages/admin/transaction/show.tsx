import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import {
    destroy,
    destroyItem,
    history as transactionHistory,
    index as transactionsIndex,
    update as updateTransaction,
    updateItem,
    updateStatus,
} from '@/actions/App/Http/Controllers/TransactionController';
import { sales as salesReport } from '@/actions/App/Http/Controllers/ReportController';
import {
    OrderItemGroups,
    formatPrice,
} from '@/components/menu/order-item-groups';
import {
    MenuOrderForm,
    type MenuOrderLineItem,
} from '@/components/menu/menu-order-form';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import {
    inputClassName,
    labelClassName,
} from '@/components/admin/menu-form';
import { TableCodeBadge } from '@/components/admin/table-code-badge';
import { TransactionOrderForm } from '@/components/admin/transaction-order-form';
import { cn } from '@/lib/utils';
import type { Menu } from '@/types/menu';
import type {
    Transaction,
    TransactionItem,
    TransactionItemGroup,
    TransactionServiceType,
} from '@/types/transaction';
import { serviceTypeLabel } from '@/types/transaction';

type Props = {
    transaction: Transaction;
    itemGroups: TransactionItemGroup[];
    menus: Menu[];
};

const serviceTypes: TransactionServiceType[] = ['dine_in', 'takeaway'];

function formatDate(iso: string): string {
    return new Date(iso).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

function statusLabel(status: Transaction['status']): string {
    return status === 'paid' ? 'Paid' : 'In progress';
}

function statusClassName(status: Transaction['status']): string {
    return status === 'paid'
        ? 'rounded-full bg-[#ecfdf3] px-2.5 py-1 text-xs font-medium text-[#027a48] dark:bg-[#053321] dark:text-[#75e0a7]'
        : 'rounded-full bg-[#fffaeb] px-2.5 py-1 text-xs font-medium text-[#b54708] dark:bg-[#4e1d09] dark:text-[#fec84b]';
}

const adminPillClassName =
    'rounded-full bg-[#eff8ff] px-2.5 py-1 text-xs font-medium text-[#175cd3] dark:bg-[#102a56] dark:text-[#84caff]';

function FieldError({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return <p className="mt-1 text-xs text-[#b42318]">{message}</p>;
}

function addonOptionIdsFor(item: TransactionItem): number[] {
    return (item.addons ?? []).map((addon) => addon.menu_addon_option_id);
}

export default function AdminTransactionShow({
    transaction,
    itemGroups,
    menus,
}: Props) {
    const { url } = usePage();
    const backHref = url.includes('from=history')
        ? transactionHistory.url()
        : url.includes('from=reports')
          ? salesReport.url()
          : transactionsIndex.url();

    const isPaid = transaction.status === 'paid';
    const itemCount =
        transaction.items?.length ??
        itemGroups.reduce((sum, group) => sum + group.items.length, 0);
    const [markPaidOpen, setMarkPaidOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [markPaidLoading, setMarkPaidLoading] = useState(false);
    const [deleteLoading, setDeleteLoading] = useState(false);
    const [editingItem, setEditingItem] = useState<TransactionItem | null>(null);
    const [itemToDelete, setItemToDelete] = useState<TransactionItem | null>(
        null,
    );
    const [deleteItemLoading, setDeleteItemLoading] = useState(false);

    const headerForm = useForm({
        customer_name: transaction.customer_name,
        customer_phone: transaction.customer_phone,
        service_type: transaction.service_type,
    });

    const itemForm = useForm({
        quantity: 1,
        addon_option_ids: [] as number[],
        note: '' as string | null,
    });

    const editingMenu = useMemo(
        () =>
            editingItem === null
                ? null
                : (menus.find((menu) => menu.id === editingItem.menu_id) ??
                  null),
        [editingItem, menus],
    );

    function handleConfirmMarkPaid() {
        setMarkPaidLoading(true);
        router.patch(
            updateStatus.url(transaction.id),
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    setMarkPaidLoading(false);
                    setMarkPaidOpen(false);
                },
            },
        );
    }

    function handleConfirmDelete() {
        setDeleteLoading(true);
        router.delete(destroy.url(transaction.id), {
            onFinish: () => {
                setDeleteLoading(false);
                setDeleteOpen(false);
            },
        });
    }

    function handleHeaderSubmit(event: React.FormEvent) {
        event.preventDefault();
        headerForm.patch(updateTransaction.url(transaction.id), {
            preserveScroll: true,
        });
    }

    function handleEditItem(item: TransactionItem) {
        setEditingItem(item);
    }

    function handleSaveItem(item: MenuOrderLineItem) {
        if (editingItem === null) {
            return;
        }

        itemForm.transform(() => ({
            quantity: item.quantity,
            addon_option_ids: item.addon_option_ids,
            note: item.note,
        }));

        itemForm.patch(
            updateItem.url({
                transaction: transaction.id,
                item: editingItem.id,
            }),
            {
                preserveScroll: true,
                onSuccess: () => setEditingItem(null),
            },
        );
    }

    function handleConfirmDeleteItem() {
        if (itemToDelete === null) {
            return;
        }

        setDeleteItemLoading(true);
        router.delete(
            destroyItem.url({
                transaction: transaction.id,
                item: itemToDelete.id,
            }),
            {
                preserveScroll: true,
                onFinish: () => {
                    setDeleteItemLoading(false);
                    setItemToDelete(null);
                    if (editingItem?.id === itemToDelete.id) {
                        setEditingItem(null);
                    }
                },
            },
        );
    }

    return (
        <>
            <Head title={`#${transaction.transaction_number} · ${transaction.customer_name}`} />

            <ConfirmDialog
                open={markPaidOpen}
                title="Mark as paid?"
                description={`Confirm payment of ${formatPrice(transaction.total_bill)} for #${transaction.transaction_number} · ${transaction.customer_name}. This cannot be undone.`}
                confirmLabel="Mark paid"
                loading={markPaidLoading}
                onConfirm={handleConfirmMarkPaid}
                onCancel={() => setMarkPaidOpen(false)}
            />

            <ConfirmDialog
                open={deleteOpen}
                title="Delete transaction?"
                description={`Delete the in-progress transaction #${transaction.transaction_number} for ${transaction.customer_name}? All ordered items will be removed.`}
                confirmLabel="Delete"
                variant="danger"
                loading={deleteLoading}
                onConfirm={handleConfirmDelete}
                onCancel={() => setDeleteOpen(false)}
            />

            <ConfirmDialog
                open={itemToDelete !== null}
                title="Remove item?"
                description={
                    itemToDelete
                        ? `Remove ${itemToDelete.quantity}× ${itemToDelete.menu_name} from this transaction?`
                        : ''
                }
                confirmLabel="Remove"
                variant="danger"
                loading={deleteItemLoading}
                onConfirm={handleConfirmDeleteItem}
                onCancel={() => setItemToDelete(null)}
            />

            <header className="sticky top-0 z-10 border-b border-[#e3e3e0] bg-[#FDFDFC]/95 px-4 py-4 backdrop-blur dark:border-[#3E3E3A] dark:bg-[#0a0a0a]/95">
                <div className="mx-auto flex max-w-lg items-center gap-3">
                    <Link
                        href={backHref}
                        className="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Back
                    </Link>
                    <h1 className="truncate text-lg font-semibold">
                        #{transaction.transaction_number}
                        {' · '}
                        {transaction.customer_name}
                        {transaction.table_code
                            ? ` · Meja ${transaction.table_code}`
                            : ''}
                    </h1>
                </div>
            </header>

            <div className="mx-auto flex h-[calc(100dvh-8.5rem)] max-w-lg flex-col px-4 py-4">
                <section className="mb-3 shrink-0 rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <div className="flex items-start justify-between gap-3">
                        <div className="min-w-0 flex-1">
                            <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                Transaction number
                            </p>
                            <p className="font-medium tabular-nums">
                                #{transaction.transaction_number}
                            </p>
                        </div>
                        <div className="flex flex-col items-end gap-1.5">
                            <TableCodeBadge code={transaction.table_code} />
                            {transaction.is_admin_created && (
                                <span className={adminPillClassName}>Admin</span>
                            )}
                            <span className={statusClassName(transaction.status)}>
                                {statusLabel(transaction.status)}
                            </span>
                        </div>
                    </div>

                    {isPaid ? (
                        <div className="mt-4 space-y-2">
                            <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                Customer
                            </p>
                            <p className="font-medium">
                                {transaction.customer_name}
                            </p>
                            <p className="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                Phone
                            </p>
                            <p>{transaction.customer_phone}</p>
                            <p className="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                Order type
                            </p>
                            <p>{serviceTypeLabel(transaction.service_type)}</p>
                            {transaction.table_code && (
                                <>
                                    <p className="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                        Meja
                                    </p>
                                    <p className="font-medium tabular-nums">
                                        {transaction.table_code}
                                    </p>
                                </>
                            )}
                        </div>
                    ) : (
                        <form
                            onSubmit={handleHeaderSubmit}
                            className="mt-4 space-y-3 border-t border-[#e3e3e0] pt-4 dark:border-[#3E3E3A]"
                        >
                            <div>
                                <label
                                    htmlFor="customer_name"
                                    className={labelClassName}
                                >
                                    Name
                                </label>
                                <input
                                    id="customer_name"
                                    type="text"
                                    value={headerForm.data.customer_name}
                                    onChange={(event) =>
                                        headerForm.setData(
                                            'customer_name',
                                            event.target.value,
                                        )
                                    }
                                    className={inputClassName}
                                />
                                <FieldError
                                    message={headerForm.errors.customer_name}
                                />
                            </div>
                            <div>
                                <label
                                    htmlFor="customer_phone"
                                    className={labelClassName}
                                >
                                    Phone
                                </label>
                                <input
                                    id="customer_phone"
                                    type="tel"
                                    value={headerForm.data.customer_phone}
                                    onChange={(event) =>
                                        headerForm.setData(
                                            'customer_phone',
                                            event.target.value,
                                        )
                                    }
                                    className={inputClassName}
                                />
                                <FieldError
                                    message={headerForm.errors.customer_phone}
                                />
                            </div>
                            <div>
                                <p className={labelClassName}>Order type</p>
                                <div className="grid grid-cols-2 gap-2">
                                    {serviceTypes.map((type) => (
                                        <button
                                            key={type}
                                            type="button"
                                            onClick={() =>
                                                headerForm.setData(
                                                    'service_type',
                                                    type,
                                                )
                                            }
                                            className={cn(
                                                'rounded-md border px-3 py-2.5 text-sm font-medium',
                                                headerForm.data.service_type ===
                                                    type
                                                    ? 'border-[#1b1b18] bg-[#1b1b18] text-white dark:border-[#EDEDEC] dark:bg-[#EDEDEC] dark:text-[#1b1b18]'
                                                    : 'border-[#e3e3e0] dark:border-[#3E3E3A]',
                                            )}
                                        >
                                            {serviceTypeLabel(type)}
                                        </button>
                                    ))}
                                </div>
                                <FieldError
                                    message={headerForm.errors.service_type}
                                />
                            </div>
                            {transaction.table_code ? (
                                <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                    Meja {transaction.table_code}
                                </p>
                            ) : null}
                            <button
                                type="submit"
                                disabled={headerForm.processing}
                                className="w-full rounded-md border border-[#e3e3e0] px-4 py-2.5 text-sm font-medium disabled:opacity-50 dark:border-[#3E3E3A]"
                            >
                                {headerForm.processing
                                    ? 'Saving...'
                                    : 'Save customer details'}
                            </button>
                        </form>
                    )}

                    <div className="mt-4 grid grid-cols-2 gap-3 border-t border-[#e3e3e0] pt-4 dark:border-[#3E3E3A]">
                        <div>
                            <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                Total items
                            </p>
                            <p className="text-xl font-semibold tabular-nums">
                                {itemCount}
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                Total bill
                            </p>
                            <p className="text-xl font-semibold tabular-nums">
                                {formatPrice(transaction.total_bill)}
                            </p>
                        </div>
                    </div>
                    <p className="mt-2 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                        {formatDate(transaction.created_at)}
                    </p>
                </section>

                {!isPaid && (
                    <button
                        type="button"
                        onClick={() => setMarkPaidOpen(true)}
                        className="mb-4 w-full shrink-0 rounded-md border border-[#f5c518] bg-[#f5c518] px-4 py-3 text-sm font-semibold text-[#1b1b18] dark:border-[#f5c518] dark:bg-[#f5c518] dark:text-[#1b1b18]"
                    >
                        Mark as paid
                    </button>
                )}

                <div className="min-h-0 flex-1 overflow-y-auto overscroll-contain pb-4">
                    <h2 className="mb-3 text-base font-semibold">Ordered items</h2>

                    <div className="mb-6">
                        <OrderItemGroups
                            groups={itemGroups}
                            onEditItem={isPaid ? undefined : handleEditItem}
                            onDeleteItem={
                                isPaid ? undefined : setItemToDelete
                            }
                        />
                    </div>

                    {!isPaid && editingItem !== null && (
                        <section className="mb-6">
                            <div className="mb-3 flex items-center justify-between gap-3">
                                <h2 className="text-base font-semibold">
                                    Edit {editingItem.menu_name}
                                </h2>
                                <button
                                    type="button"
                                    onClick={() => setEditingItem(null)}
                                    className="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Cancel
                                </button>
                            </div>
                            <div className="rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                {editingMenu ? (
                                    <MenuOrderForm
                                        key={editingItem.id}
                                        menu={editingMenu}
                                        onSubmit={handleSaveItem}
                                        submitLabel={
                                            itemForm.processing
                                                ? 'Saving...'
                                                : 'Save item'
                                        }
                                        disabled={itemForm.processing}
                                        initialQuantity={editingItem.quantity}
                                        initialAddonOptionIds={addonOptionIdsFor(
                                            editingItem,
                                        )}
                                        initialNote={editingItem.note ?? null}
                                        errors={{
                                            quantity: itemForm.errors.quantity,
                                            addon_option_ids:
                                                itemForm.errors
                                                    .addon_option_ids,
                                            note: itemForm.errors.note,
                                        }}
                                    />
                                ) : (
                                    <p className="text-sm text-[#b42318]">
                                        Menu data for this item is no longer
                                        available.
                                    </p>
                                )}
                            </div>
                        </section>
                    )}

                    {!isPaid && (
                        <section className="mb-3">
                            <h2 className="mb-3 text-base font-semibold">
                                Add menu for customer
                            </h2>
                            <div className="rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <TransactionOrderForm
                                    transactionId={transaction.id}
                                    menus={menus}
                                />
                            </div>
                        </section>
                    )}

                    {!isPaid && (
                        <section className="mt-8 border-t border-[#e3e3e0] pt-6 dark:border-[#3E3E3A]">
                            <h2 className="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">
                                Danger zone
                            </h2>
                            <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                Permanently remove this in-progress transaction
                                and all its items.
                            </p>
                            <button
                                type="button"
                                onClick={() => setDeleteOpen(true)}
                                className="mt-4 w-full rounded-md border border-[#fda29b] px-4 py-2.5 text-sm font-medium text-[#b42318] dark:border-[#912018]"
                            >
                                Delete transaction
                            </button>
                        </section>
                    )}
                </div>
            </div>
        </>
    );
}
