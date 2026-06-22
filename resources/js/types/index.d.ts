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

export interface CartLine {
    item_id: string;
    product_id: string;
    slug: string;
    name: string;
    variant_label: string;
    unit_price_cents: number;
    quantity: number;
    line_total_cents: number;
    stock: number;
    thumbnail_url: string | null;
}

export interface CartSummary {
    count: number;
    items: CartLine[];
    subtotalCents: number;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    flash: Flash;
    cart: CartSummary | null;
};
