export type SalesChannelType = 'store' | 'event';

export type SalesChannelStatus = 'store' | 'upcoming' | 'active' | 'ended';

export type SalesChannel = {
    id: number;
    type: SalesChannelType;
    name: string;
    starts_at: string | null;
    ends_at: string | null;
    closes_store: boolean;
    status: SalesChannelStatus;
    is_store: boolean;
    is_archived: boolean;
};

export type ChannelMenu = {
    id: number;
    name: string;
    price: number;
    price_label: string;
    type: 'standard' | 'weight_based';
    pricing_type?: 'standard' | 'weight_based';
    is_available: boolean;
    price_override?: number | null;
};

export type EventSourceMenu = {
    id: number;
    name: string;
    price_label: string;
    price_override: number | null;
    is_available: boolean;
};

export type EventCopySource = {
    id: number;
    name: string;
    starts_at: string | null;
    ends_at: string | null;
    is_archived: boolean;
    menus_count: number;
    menus?: EventSourceMenu[];
};

export type SalesChannelOption = {
    id: number;
    name: string;
    type: SalesChannelType;
};
