import { cn } from '@/lib/utils';

export const WEIGHT_PRESETS = [100, 150, 200, 250, 300, 500];
export const MIN_WEIGHT_GRAMS = 1;
export const MAX_WEIGHT_GRAMS = 50000;

export function clampWeightGrams(value: number): number {
    if (!Number.isFinite(value)) {
        return 100;
    }

    return Math.max(
        MIN_WEIGHT_GRAMS,
        Math.min(MAX_WEIGHT_GRAMS, Math.floor(value)),
    );
}

export function weightLineTotal(
    unitPrice: number,
    grams: number,
    addonTotal = 0,
): number {
    return Math.round((unitPrice * grams) / 100) + addonTotal;
}

type Props = {
    value: number;
    onChange: (grams: number) => void;
    disabled?: boolean;
};

export function WeightGramsField({ value, onChange, disabled = false }: Props) {
    return (
        <div>
            <div className="flex items-center gap-2">
                {[-50, -10, 10, 50].map((delta) => (
                    <button
                        key={delta}
                        type="button"
                        disabled={disabled}
                        onClick={() => onChange(clampWeightGrams(value + delta))}
                        className="flex h-10 min-w-12 items-center justify-center rounded-md border border-[#e3e3e0] px-2 text-sm dark:border-[#3E3E3A]"
                    >
                        {delta > 0 ? `+${delta}` : delta}
                    </button>
                ))}
            </div>
            <input
                type="number"
                min={MIN_WEIGHT_GRAMS}
                max={MAX_WEIGHT_GRAMS}
                value={value}
                disabled={disabled}
                onChange={(event) =>
                    onChange(
                        clampWeightGrams(parseInt(event.target.value, 10) || 1),
                    )
                }
                className="mt-3 w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2.5 text-center text-sm outline-none focus:border-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:focus:border-[#EDEDEC]"
            />
            <p className="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                gram
            </p>
            <div className="mt-3 flex flex-wrap gap-2">
                {WEIGHT_PRESETS.map((preset) => (
                    <button
                        key={preset}
                        type="button"
                        disabled={disabled}
                        onClick={() => onChange(preset)}
                        className={cn(
                            'rounded-full border px-2.5 py-1 text-xs font-medium',
                            value === preset
                                ? 'border-[#1b1b18] bg-[#1b1b18] text-white dark:border-[#EDEDEC] dark:bg-[#EDEDEC] dark:text-[#1b1b18]'
                                : 'border-[#e3e3e0] dark:border-[#3E3E3A]',
                        )}
                    >
                        {preset}g
                    </button>
                ))}
            </div>
        </div>
    );
}
