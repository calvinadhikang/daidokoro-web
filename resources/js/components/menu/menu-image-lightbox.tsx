import { useEffect } from 'react';

import { getMenuImageUrl } from '@/lib/menu-image';

type MenuImageLightboxProps = {
    src: string | null | undefined;
    alt: string;
    open: boolean;
    onClose: () => void;
};

export function MenuImageLightbox({
    src,
    alt,
    open,
    onClose,
}: MenuImageLightboxProps) {
    useEffect(() => {
        if (!open) {
            return;
        }

        function handleKeyDown(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                onClose();
            }
        }

        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.body.style.overflow = previousOverflow;
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, [open, onClose]);

    if (!open) {
        return null;
    }

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black">
            <button
                type="button"
                aria-label="Close photo"
                className="absolute inset-0"
                onClick={onClose}
            />
            <img
                src={getMenuImageUrl(src)}
                alt={alt}
                className="relative z-10 max-h-[100dvh] max-w-full object-contain"
            />
            <button
                type="button"
                onClick={onClose}
                aria-label="Close photo"
                className="absolute top-4 right-4 z-20 flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-[#1b1b18] shadow-sm"
            >
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    className="size-5"
                    aria-hidden="true"
                >
                    <path d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
        </div>
    );
}
