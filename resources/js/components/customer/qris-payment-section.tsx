import { useEffect, useRef, useState } from 'react';
import QRCode from 'qrcode';

import { formatPrice } from '@/components/menu/order-item-groups';

type Props = {
    qrContent: string;
    amount: number;
    expiresAt: string;
    filename: string;
    regenerating: boolean;
    onRegenerate: () => void;
};

function formatExpiry(iso: string): string {
    return new Date(iso).toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

export function QrisPaymentSection({
    qrContent,
    amount,
    expiresAt,
    filename,
    regenerating,
    onRegenerate,
}: Props) {
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const canvas = canvasRef.current;

        if (!canvas) {
            return;
        }

        let cancelled = false;

        QRCode.toCanvas(canvas, qrContent, {
            width: 280,
            margin: 2,
            errorCorrectionLevel: 'L',
            color: {
                dark: '#1b1b18',
                light: '#ffffff',
            },
        })
            .then(() => {
                if (!cancelled) {
                    setError(null);
                }
            })
            .catch(() => {
                if (!cancelled) {
                    setError('Could not draw this QR code.');
                }
            });

        return () => {
            cancelled = true;
        };
    }, [qrContent]);

    function handleDownload() {
        const canvas = canvasRef.current;

        if (!canvas) {
            return;
        }

        const link = document.createElement('a');
        link.href = canvas.toDataURL('image/png');
        link.download = filename;
        link.click();
    }

    return (
        <section className="mt-4 rounded-xl border border-[#e3e3e0] bg-[#FBF6F1] p-4 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]">
            <p className="text-sm font-medium">Pay with QRIS</p>
            <p className="mt-0.5 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                Scan this code in a payment app. Valid until{' '}
                {formatExpiry(expiresAt)}.
            </p>
            <p className="mt-2 text-base font-semibold text-[#C56A3E] tabular-nums">
                Rp {formatPrice(amount)}
            </p>

            <div className="mt-4 flex flex-col items-center gap-3">
                <canvas
                    ref={canvasRef}
                    aria-label="QRIS payment code"
                    className="rounded-md border border-[#e3e3e0] bg-white dark:border-[#3E3E3A]"
                />
                {error !== null && (
                    <p className="text-xs text-[#b42318]">{error}</p>
                )}
                <button
                    type="button"
                    onClick={handleDownload}
                    disabled={error !== null}
                    className="w-full rounded-full border border-[#e3e3e0] bg-white px-4 py-2.5 text-sm font-medium disabled:opacity-50 dark:border-[#3E3E3A] dark:bg-[#161615]"
                >
                    Download QR image
                </button>
                <button
                    type="button"
                    onClick={onRegenerate}
                    disabled={regenerating}
                    className="w-full rounded-full px-4 py-2.5 text-sm font-medium text-[#A4532E] disabled:opacity-50 dark:text-[#F3D5C2]"
                >
                    {regenerating ? 'Creating QR…' : 'Create a new QR'}
                </button>
            </div>
        </section>
    );
}
