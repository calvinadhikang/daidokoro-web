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
}: {
    active: boolean;
    label: string;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'shrink-0 whitespace-nowrap rounded-full border px-3 py-1.5 text-xs font-medium',
                active
                    ? 'border-[#1b1b18] bg-[#1b1b18] text-white dark:border-[#EDEDEC] dark:bg-[#EDEDEC] dark:text-[#1b1b18]'
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
    className?: string;
};

export function CategoryFilterRow({
    categories,
    value,
    onChange,
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
                    onClick={() => onChange('all')}
                />
                <FilterButton
                    active={value === 'recommended'}
                    label="Recommended"
                    onClick={() => onChange('recommended')}
                />
                {visibleCategories.map((item) => (
                    <FilterButton
                        key={item.id}
                        active={value === item.id}
                        label={item.name}
                        onClick={() => onChange(item.id)}
                    />
                ))}
            </div>
        </div>
    );
}

export { FilterButton };
