export type CategoryMenu = {
    id: number;
    name: string;
    is_available: boolean;
};

export type Category = {
    id: number;
    name: string;
    menus_count?: number;
    menus: CategoryMenu[];
};

export type CategoryForm = {
    name: string;
    menu_ids: number[];
};
