export type TransactionStatus = 'in_progress' | 'paid';

export type TransactionServiceType = 'dine_in' | 'takeaway';

export function serviceTypeLabel(type: TransactionServiceType): string {
    return type === 'takeaway' ? 'Takeaway' : 'Dine-in';
}

export type TransactionAddon = {
    menu_addon_option_id: number;
    group_name: string;
    name: string;
    price: number;
};

export type TransactionItem = {
    id: number;
    transaction_id: number;
    menu_id: number;
    menu_name: string;
    quantity: number;
    unit_price: number;
    line_total: number;
    addons: TransactionAddon[] | null;
    created_at: string;
};

export type Transaction = {
    id: number;
    customer_name: string;
    customer_phone: string;
    service_type: TransactionServiceType;
    status: TransactionStatus;
    total_bill: number;
    created_at: string;
    updated_at: string;
    items?: TransactionItem[];
};

export type TransactionOrderForm = {
    menu_id: string;
    quantity: number;
    addon_option_ids: number[];
};

export type CreateTransactionForm = {
    customer_name: string;
    customer_phone: string;
    service_type: TransactionServiceType;
    items: Array<{
        menu_id: number;
        quantity: number;
        addon_option_ids: number[];
    }>;
};
