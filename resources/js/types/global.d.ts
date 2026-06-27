import type { Auth } from '@/types/auth';
import type { Customer } from '@/types/customer';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            customer: Customer | null;
            sidebarOpen: boolean;
            flash: {
                success?: string;
            };
            [key: string]: unknown;
        };
    }
}
