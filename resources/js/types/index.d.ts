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

export interface OrderItemDetail {
    product_name: string;
    slug: string | null;
    variant_label: string | null;
    unit_price_cents: number;
    quantity: number;
    line_total_cents: number;
    thumbnail_url: string | null;
}

export interface OrderShipping {
    name?: string;
    email?: string;
    address?: string;
    city?: string;
    postal_code?: string;
    country?: string;
}

export interface OrderDetail {
    id: string;
    status: string;
    tracking_status: string;
    payment_method: string | null;
    subtotal_cents: number;
    total_cents: number;
    shipping: OrderShipping | null;
    placed_at: string | null;
    created_at: string | null;
    items: OrderItemDetail[];
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
