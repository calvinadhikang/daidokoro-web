export const MENU_IMAGE_PLACEHOLDER =
    import.meta.env.VITE_MENU_IMAGE_PLACEHOLDER ??
    'https://is3.cloudhost.id/daidokoro-web/placeholder/menu.png';

export function getMenuImageUrl(image: string | null | undefined): string {
    if (image === null || image === undefined || image.trim() === '') {
        return MENU_IMAGE_PLACEHOLDER;
    }

    return image;
}
