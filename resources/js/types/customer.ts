export type Customer = {
    id: number;
    name: string;
    phone: string;
    phone_display: string;
    phone_local: string;
};

export type CustomerNav = {
    cartCount: number;
    cartTotal: number;
    hasOrder: boolean;
};

export type CustomerLoginForm = {
    name: string;
    phone: string;
    service_type: 'dine_in' | 'takeaway' | '';
};

export type CartItem = {
    menu_id: number;
    menu_name: string;
    quantity: number;
    unit_price: number;
    line_total: number;
    addon_option_ids: number[];
    addons: Array<{
        menu_addon_option_id: number;
        group_name: string;
        name: string;
        price: number;
    }>;
};
