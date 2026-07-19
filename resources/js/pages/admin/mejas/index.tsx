import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import {
    create as createMeja,
    show as showMeja,
} from '@/actions/App/Http/Controllers/MejaController';
import { inputClassName } from '@/components/admin/menu-form';
import type { Meja } from '@/types/meja';

type Props = {
    mejas: Meja[];
};

function MejaListItem({ meja }: { meja: Meja }) {
    return (
        <Link
            href={showMeja.url(meja.id)}
            className="block rounded-lg border border-[#e3e3e0] bg-white p-4 active:bg-[#FDFDFC] dark:border-[#3E3E3A] dark:bg-[#161615] dark:active:bg-[#0a0a0a]"
        >
            <div className="flex items-center justify-between gap-3">
                <p className="truncate font-medium tabular-nums">{meja.code}</p>
                <span className="shrink-0 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    View QR
                </span>
            </div>
        </Link>
    );
}

export default function AdminMejasIndex({ mejas }: Props) {
    const [search, setSearch] = useState('');

    const filteredMejas = useMemo(() => {
        const query = search.trim().toLowerCase();

        if (query === '') {
            return mejas;
        }

        return mejas.filter((meja) =>
            meja.code.toLowerCase().includes(query),
        );
    }, [mejas, search]);

    return (
        <>
            <Head title="Tables" />
            <div className="flex h-[calc(100dvh-7.5rem)] flex-col px-4 py-4">
                <div className="mx-auto flex w-full max-w-lg shrink-0 flex-col">
                    <header className="mb-4">
                        <h1 className="text-2xl font-semibold">Tables</h1>
                        <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            {search.trim() !== ''
                                ? `${filteredMejas.length} of ${mejas.length} tables`
                                : `${mejas.length} ${mejas.length === 1 ? 'table' : 'tables'}`}
                        </p>
                    </header>

                    <input
                        type="search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search table codes..."
                        className={`${inputClassName} mb-4`}
                    />

                    <Link
                        href={createMeja.url()}
                        className="mb-4 flex w-full items-center justify-center rounded-md bg-[#1b1b18] px-4 py-3 text-sm font-medium text-white dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
                    >
                        Add Table
                    </Link>
                </div>

                <div className="mx-auto min-h-0 w-full max-w-lg flex-1 overflow-y-auto overscroll-contain">
                    {mejas.length === 0 ? (
                        <div className="rounded-lg border border-[#e3e3e0] bg-white p-10 text-center dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <p className="text-[#706f6c] dark:text-[#A1A09A]">
                                No tables yet.
                            </p>
                        </div>
                    ) : filteredMejas.length === 0 ? (
                        <div className="rounded-lg border border-[#e3e3e0] bg-white p-10 text-center dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <p className="text-[#706f6c] dark:text-[#A1A09A]">
                                No tables match your search.
                            </p>
                        </div>
                    ) : (
                        <ul className="space-y-3 pb-4">
                            {filteredMejas.map((meja) => (
                                <li key={meja.id}>
                                    <MejaListItem meja={meja} />
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </>
    );
}
