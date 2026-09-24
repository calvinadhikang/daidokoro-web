import { cn } from '@/lib/utils';
import {
    visibleMenuCategories,
    type MenuBrowseFilter,
} from '@/lib/menu-list';
import type { MenuCategory } from '@/types/menu';

function FilterButton({
    active,
    label,
    onClick,
    tone = 'ink',
}: {
    active: boolean;
    label: string;
    onClick: () => void;
    tone?: 'ink' | 'brand';
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'shrink-0 whitespace-nowrap rounded-full border px-3 py-1.5 text-xs font-medium',
                active
                    ? tone === 'brand'
                        ? 'border-[#E24E1B] bg-[#E24E1B] text-white'
                        : 'border-[#1b1b18] bg-[#1b1b18] text-white dark:border-[#EDEDEC] dark:bg-[#EDEDEC] dark:text-[#1b1b18]'
                    : tone === 'brand'
                      ? 'border-[#F3D2C4] bg-white text-[#9A3412] dark:border-[#5C2A1A] dark:bg-transparent dark:text-[#FDBA8C]'
                      : 'border-[#e3e3e0] text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]',
            )}
        >
            {label}
        </button>
    );
}

type CategoryFilterRowProps = {
    categories: MenuCategory[];
    value: MenuBrowseFilter;
    onChange: (value: MenuBrowseFilter) => void;
    tone?: 'ink' | 'brand';
    className?: string;
};

export function CategoryFilterRow({
    categories,
    value,
    onChange,
    tone = 'ink',
    className,
}: CategoryFilterRowProps) {
    const visibleCategories = visibleMenuCategories(categories);

    return (
        <div
            className={cn(
                '-mx-4 min-w-0 overflow-x-auto overscroll-x-contain px-4 pb-1 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden',
                className,
            )}
        >
            <div className="flex w-max flex-nowrap gap-2">
                <FilterButton
                    active={value === 'all'}
                    label="All"
                    tone={tone}
                    onClick={() => onChange('all')}
                />
                <FilterButton
                    active={value === 'recommended'}
                    label="Recommended"
                    tone={tone}
                    onClick={() => onChange('recommended')}
                />
                {visibleCategories.map((item) => (
                    <FilterButton
                        key={item.id}
                        active={value === item.id}
                        label={item.name}
                        tone={tone}
                        onClick={() => onChange(item.id)}
                    />
                ))}
            </div>
        </div>
    );
}

export { FilterButton };
