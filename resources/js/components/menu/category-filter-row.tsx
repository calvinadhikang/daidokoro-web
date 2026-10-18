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
                        ? 'border-[#C56A3E] bg-[#C56A3E] text-white'
                        : 'border-[#1b1b18] bg-[#1b1b18] text-white dark:border-[#EDEDEC] dark:bg-[#EDEDEC] dark:text-[#1b1b18]'
                    : tone === 'brand'
                      ? 'border-[#d6d3d1] bg-white text-[#1b1b18] dark:border-[#57534e] dark:bg-[#161615] dark:text-[#EDEDEC]'
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
                '-mx-4 min-w-0 max-w-full touch-pan-x overflow-x-auto overflow-y-hidden overscroll-x-contain px-4 pb-1 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:h-0 [&::-webkit-scrollbar]:w-0',
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
