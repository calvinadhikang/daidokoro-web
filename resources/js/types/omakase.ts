export type OmakaseMenuItem = {
    id: number;
    name: string;
};

export type OmakaseSessionSummary = {
    id: number;
    name: string;
    date: string;
    menus_count: number;
    menu_preview: string[];
};

export type OmakaseSessionDetail = {
    id: number;
    name: string;
    date: string;
    menus_count: number;
    menus: OmakaseMenuItem[];
};

export type OmakaseSessionOption = {
    id: number;
    name: string;
    date: string;
    menus_count: number;
};
