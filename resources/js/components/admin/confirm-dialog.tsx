import { useEffect, useId, useRef } from 'react';

import { cn } from '@/lib/utils';

type ConfirmDialogProps = {
    open: boolean;
    title: string;
    description: string;
    confirmLabel?: string;
    cancelLabel?: string;
    variant?: 'default' | 'danger';
    loading?: boolean;
    onConfirm: () => void;
    onCancel: () => void;
};

export function ConfirmDialog({
    open,
    title,
    description,
    confirmLabel = 'Confirm',
    cancelLabel = 'Cancel',
    variant = 'default',
    loading = false,
    onConfirm,
    onCancel,
}: ConfirmDialogProps) {
    const titleId = useId();
    const descriptionId = useId();
    const cancelRef = useRef<HTMLButtonElement>(null);

    useEffect(() => {
        if (!open) {
            return;
        }

        cancelRef.current?.focus();

        function handleKeyDown(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                onCancel();
            }
        }

        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, [open, onCancel]);

    if (!open) {
        return null;
    }

    return (
        <div className="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center">
            <button
                type="button"
                aria-label="Close dialog"
                className="absolute inset-0 bg-black/40"
                onClick={onCancel}
            />

            <div
                role="dialog"
                aria-modal="true"
                aria-labelledby={titleId}
                aria-describedby={descriptionId}
                className="relative w-full max-w-sm rounded-lg border border-[#e3e3e0] bg-white p-5 shadow-lg dark:border-[#3E3E3A] dark:bg-[#161615]"
            >
                <h2 id={titleId} className="text-base font-semibold">
                    {title}
                </h2>
                <p
                    id={descriptionId}
                    className="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]"
                >
                    {description}
                </p>

                <div className="mt-5 flex gap-3">
                    <button
                        ref={cancelRef}
                        type="button"
                        onClick={onCancel}
                        disabled={loading}
                        className="flex flex-1 items-center justify-center rounded-md border border-[#e3e3e0] px-4 py-2.5 text-sm font-medium disabled:opacity-50 dark:border-[#3E3E3A]"
                    >
                        {cancelLabel}
                    </button>
                    <button
                        type="button"
                        onClick={onConfirm}
                        disabled={loading}
                        className={cn(
                            'flex flex-1 items-center justify-center rounded-md px-4 py-2.5 text-sm font-medium disabled:opacity-50',
                            variant === 'danger'
                                ? 'bg-[#b42318] text-white'
                                : 'bg-[#1b1b18] text-white dark:bg-[#EDEDEC] dark:text-[#1b1b18]',
                        )}
                    >
                        {loading ? 'Please wait...' : confirmLabel}
                    </button>
                </div>
            </div>
        </div>
    );
}
