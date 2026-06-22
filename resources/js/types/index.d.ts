export type Role = 'admin' | 'vendor' | 'customer';

export interface User {
    id: string;
    name: string;
    email: string;
    email_verified_at?: string | null;
    role: Role | null;
}

export interface Flash {
    success: string | null;
    error: string | null;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    flash: Flash;
};
