export type Customer = {
    id: number;
    name: string;
    phone: string;
    phone_display: string;
    phone_local: string;
};

export type CustomerLoginForm = {
    name: string;
    phone: string;
    service_type: 'dine_in' | 'takeaway' | '';
};
