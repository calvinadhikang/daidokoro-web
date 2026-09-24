import { cn } from '@/lib/utils';

type BrandLogoProps = {
    className?: string;
};

export function BrandLogo({ className }: BrandLogoProps) {
    return (
        <img
            src="/images/daidokoro-logo.png"
            alt="Daidokoro Japanese Cuisine"
            className={cn('object-contain', className)}
        />
    );
}
