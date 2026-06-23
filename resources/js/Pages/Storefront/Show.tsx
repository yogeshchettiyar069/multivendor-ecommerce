import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { cn } from '@/lib/utils';
import { formatCents } from '@/lib/format';
import { User } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ImageOff, Minus, Plus, ShoppingCart } from 'lucide-react';
import { useMemo, useState } from 'react';

interface Variant {
    id: string;
    sku: string;
    size: string | null;
    color: string | null;
    price_cents: number;
    stock: number;
}

interface Product {
    id: string;
    slug: string;
    name: string;
    description: string | null;
    vendor: string | null;
    category: string | null;
    base_price_cents: number;
    thumbnail_url: string | null;
    variants: Variant[];
}

export default function Show({ product }: { product: Product }) {
    const user = usePage().props.auth.user as User | null;

    const sizes = useMemo(
        () => [...new Set(product.variants.map((v) => v.size).filter((s): s is string => !!s))],
        [product],
    );
    const colors = useMemo(
        () => [...new Set(product.variants.map((v) => v.color).filter((c): c is string => !!c))],
        [product],
    );

    const [size, setSize] = useState<string | null>(sizes[0] ?? null);
    const [color, setColor] = useState<string | null>(colors[0] ?? null);
    const [quantity, setQuantity] = useState(1);
    const [adding, setAdding] = useState(false);

    const variantFor = (s: string | null, c: string | null): Variant | undefined =>
        product.variants.find(
            (v) => (sizes.length === 0 || v.size === s) && (colors.length === 0 || v.color === c),
        );

    const selected = variantFor(size, color);
    const maxQty = selected?.stock ?? 0;
    const price = selected ? selected.price_cents : product.base_price_cents;

    const addToCart = () => {
        if (!selected) return;
        router.post(
            route('cart.store'),
            { product_id: product.id, variant_id: selected.id, quantity },
            {
                preserveScroll: true,
                onStart: () => setAdding(true),
                onFinish: () => setAdding(false),
            },
        );
    };

    return (
        <StorefrontLayout>
            <Head title={product.name} />

            <div className="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
                <Link
                    href={route('catalog')}
                    className="text-sm text-muted-foreground hover:text-foreground"
                >
                    ← Back to shop
                </Link>

                <div className="mt-6 grid gap-10 lg:grid-cols-2">
                    {/* Gallery */}
                    <div className="overflow-hidden rounded-xl border border-border bg-muted">
                        <div className="flex aspect-square items-center justify-center">
                            {product.thumbnail_url ? (
                                <img
                                    src={product.thumbnail_url}
                                    alt={product.name}
                                    className="h-full w-full object-cover"
                                />
                            ) : (
                                <ImageOff className="h-16 w-16 text-muted-foreground" />
                            )}
                        </div>
                    </div>

                    {/* Details */}
                    <div>
                        {product.vendor && (
                            <p className="text-sm uppercase tracking-wide text-muted-foreground">
                                {product.vendor}
                            </p>
                        )}
                        <h1 className="mt-1 text-3xl font-bold text-foreground">{product.name}</h1>
                        <p className="mt-3 text-2xl font-semibold text-foreground">
                            {formatCents(price)}
                        </p>

                        {sizes.length > 0 && (
                            <div className="mt-6">
                                <p className="mb-2 text-sm font-medium text-foreground">Size</p>
                                <div className="flex flex-wrap gap-2">
                                    {sizes.map((s) => {
                                        const available = (variantFor(s, color)?.stock ?? 0) > 0;
                                        return (
                                            <button
                                                key={s}
                                                onClick={() => setSize(s)}
                                                className={cn(
                                                    'min-w-12 rounded-md border px-3 py-2 text-sm transition-colors',
                                                    size === s
                                                        ? 'border-primary bg-primary text-primary-foreground'
                                                        : 'border-border hover:border-primary',
                                                    !available && 'opacity-40',
                                                )}
                                            >
                                                {s}
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>
                        )}

                        {colors.length > 0 && (
                            <div className="mt-4">
                                <p className="mb-2 text-sm font-medium text-foreground">Color</p>
                                <div className="flex flex-wrap gap-2">
                                    {colors.map((c) => {
                                        const available = (variantFor(size, c)?.stock ?? 0) > 0;
                                        return (
                                            <button
                                                key={c}
                                                onClick={() => setColor(c)}
                                                className={cn(
                                                    'rounded-md border px-3 py-2 text-sm transition-colors',
                                                    color === c
                                                        ? 'border-primary bg-primary text-primary-foreground'
                                                        : 'border-border hover:border-primary',
                                                    !available && 'opacity-40',
                                                )}
                                            >
                                                {c}
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>
                        )}

                        <div className="mt-6 flex items-center gap-4">
                            <div className="flex items-center rounded-md border border-border">
                                <button
                                    className="px-3 py-2 disabled:opacity-40"
                                    disabled={quantity <= 1}
                                    onClick={() => setQuantity((q) => Math.max(1, q - 1))}
                                    aria-label="Decrease quantity"
                                >
                                    <Minus className="h-4 w-4" />
                                </button>
                                <span className="w-10 text-center">{quantity}</span>
                                <button
                                    className="px-3 py-2 disabled:opacity-40"
                                    disabled={quantity >= maxQty}
                                    onClick={() => setQuantity((q) => Math.min(maxQty, q + 1))}
                                    aria-label="Increase quantity"
                                >
                                    <Plus className="h-4 w-4" />
                                </button>
                            </div>

                            {maxQty > 0 ? (
                                <span className="text-sm text-muted-foreground">
                                    {maxQty} in stock
                                </span>
                            ) : (
                                <Badge variant="secondary">Out of stock</Badge>
                            )}
                        </div>

                        <div className="mt-6 flex flex-col gap-3 sm:flex-row">
                            {user ? (
                                <>
                                    <Button
                                        size="lg"
                                        variant="outline"
                                        disabled={!selected || maxQty < 1 || adding}
                                        onClick={addToCart}
                                    >
                                        <ShoppingCart className="h-4 w-4" /> Add to cart
                                    </Button>
                                    <Button
                                        size="lg"
                                        disabled={!selected || maxQty < 1}
                                        onClick={() =>
                                            selected &&
                                            router.visit(
                                                route('checkout', {
                                                    buy_now: 1,
                                                    product: product.id,
                                                    variant: selected.id,
                                                    qty: quantity,
                                                }),
                                            )
                                        }
                                    >
                                        Buy now
                                    </Button>
                                </>
                            ) : (
                                <Button size="lg" asChild>
                                    <Link href={route('login')}>Log in to buy</Link>
                                </Button>
                            )}
                        </div>

                        {product.description && (
                            <div className="mt-8 border-t border-border pt-6">
                                <h2 className="mb-2 text-sm font-semibold text-foreground">
                                    Description
                                </h2>
                                <p className="whitespace-pre-line text-sm text-muted-foreground">
                                    {product.description}
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </StorefrontLayout>
    );
}
