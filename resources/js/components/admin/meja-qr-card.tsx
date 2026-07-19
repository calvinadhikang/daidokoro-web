import { useEffect, useRef, useState } from 'react';
import QRCode from 'qrcode';

type Props = {
    url: string;
    filename: string;
};

export function MejaQrCard({ url, filename }: Props) {
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const canvas = canvasRef.current;

        if (!canvas) {
            return;
        }

        let cancelled = false;

        QRCode.toCanvas(canvas, url, {
            width: 240,
            margin: 2,
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
                    setError('Could not generate QR code.');
                }
            });

        return () => {
            cancelled = true;
        };
    }, [url]);

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
        <section className="mt-4 rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
            <p className="text-sm font-medium">Ordering QR</p>
            <p className="mt-0.5 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                Customers scan this to open dine-in ordering for this table.
            </p>

            <div className="mt-4 flex flex-col items-center gap-3">
                <canvas
                    ref={canvasRef}
                    className="rounded-md border border-[#e3e3e0] bg-white dark:border-[#3E3E3A]"
                />
                {error !== null && (
                    <p className="text-xs text-[#b42318]">{error}</p>
                )}
                <p className="max-w-full break-all text-center text-xs text-[#706f6c] dark:text-[#A1A09A]">
                    {url}
                </p>
                <button
                    type="button"
                    onClick={handleDownload}
                    className="w-full rounded-md border border-[#e3e3e0] px-4 py-3 text-sm font-medium dark:border-[#3E3E3A]"
                >
                    Download QR
                </button>
            </div>
        </section>
    );
}
