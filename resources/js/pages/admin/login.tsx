import { Head, useForm } from '@inertiajs/react';

import { BrandLogo } from '@/components/brand-logo';
import {
    inputClassName,
    labelClassName,
} from '@/components/admin/menu-form';

function FieldError({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return <p className="mt-1 text-xs text-[#b42318]">{message}</p>;
}

export default function AdminLogin() {
    const form = useForm({
        username: '',
        password: '',
    });

    function handleSubmit(event: React.FormEvent) {
        event.preventDefault();
        form.post('/admin/login');
    }

    return (
        <>
            <Head title="Admin login" />

            <div className="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <main className="mx-auto flex w-full max-w-md flex-1 flex-col justify-center px-4 py-8">
                    <header className="mb-8">
                        <BrandLogo className="mb-4 h-16 w-16" />
                        <p className="text-xs font-medium tracking-wide text-[#706f6c] uppercase dark:text-[#A1A09A]">
                            Admin
                        </p>
                        <h1 className="mt-1 text-2xl font-semibold">Sign in</h1>
                    </header>

                    <form
                        onSubmit={handleSubmit}
                        className="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]"
                    >
                        <div className="mb-4">
                            <label htmlFor="username" className={labelClassName}>
                                Username
                            </label>
                            <input
                                id="username"
                                type="text"
                                value={form.data.username}
                                onChange={(event) =>
                                    form.setData('username', event.target.value)
                                }
                                className={inputClassName}
                                autoComplete="username"
                                required
                            />
                            <FieldError message={form.errors.username} />
                        </div>

                        <div className="mb-6">
                            <label htmlFor="password" className={labelClassName}>
                                Password
                            </label>
                            <input
                                id="password"
                                type="password"
                                value={form.data.password}
                                onChange={(event) =>
                                    form.setData('password', event.target.value)
                                }
                                className={inputClassName}
                                autoComplete="current-password"
                                required
                            />
                            <FieldError message={form.errors.password} />
                        </div>

                        <button
                            type="submit"
                            disabled={form.processing}
                            className="w-full rounded-md bg-[#1b1b18] px-4 py-3 text-sm font-medium text-white disabled:opacity-50 dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
                        >
                            {form.processing ? 'Signing in...' : 'Sign in'}
                        </button>
                    </form>
                </main>
            </div>
        </>
    );
}
