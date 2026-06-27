export type MenuAddonOption = {
    id: number;
    menu_addon_group_id: number;
    name: string;
    price: number;
    is_available: boolean;
    sort_order: number;
};

export type MenuAddonGroup = {
    id: number;
    menu_id: number;
    name: string;
    selection_type: 'single' | 'multiple';
    is_required: boolean;
    sort_order: number;
    options: MenuAddonOption[];
};

export type Menu = {
    id: number;
    name: string;
    image: string | null;
    price: number;
    is_available: boolean;
    is_recommended: boolean;
    addon_groups: MenuAddonGroup[];
};

export type MenuAddonOptionForm = {
    name: string;
    price: string;
    is_available: boolean;
};

export type MenuAddonGroupForm = {
    name: string;
    selection_type: 'single' | 'multiple';
    is_required: boolean;
    options: MenuAddonOptionForm[];
};

export type MenuForm = {
    name: string;
    price: string;
    is_available: boolean;
    is_recommended: boolean;
    addon_groups: MenuAddonGroupForm[];
};
