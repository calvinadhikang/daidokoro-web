import { useForm } from '@inertiajs/react';

import { storeItem } from '@/actions/App/Http/Controllers/TransactionController';
import {
    TransactionMenuPicker,
    type TransactionMenuPickerItem,
} from '@/components/admin/transaction-menu-picker';
import type { Menu } from '@/types/menu';

type Props = {
    transactionId: number;
    menus: Menu[];
};

export function TransactionOrderForm({ transactionId, menus }: Props) {
    const form = useForm({
        menu_id: 0,
        quantity: 1,
        addon_option_ids: [] as number[],
    });

    function handleAdd(item: TransactionMenuPickerItem) {
        form.transform(() => ({
            menu_id: item.menu_id,
            quantity: item.quantity,
            addon_option_ids: item.addon_option_ids,
        }));

        form.post(storeItem.url(transactionId), {
            preserveScroll: true,
        });
    }

    return (
        <TransactionMenuPicker
            menus={menus}
            onAdd={handleAdd}
            submitLabel={form.processing ? 'Adding...' : 'Add to order'}
            disabled={form.processing}
        />
    );
}
