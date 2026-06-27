import { getMenuImageUrl } from '@/lib/menu-image';
import { cn } from '@/lib/utils';

type MenuImageProps = {
    src: string | null | undefined;
    alt: string;
    className?: string;
};

export function MenuImage({ src, alt, className }: MenuImageProps) {
    return (
        <img
            src={getMenuImageUrl(src)}
            alt={alt}
            className={cn('shrink-0 object-cover', className)}
        />
    );
}
