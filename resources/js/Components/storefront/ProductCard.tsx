import { Badge } from '@/Components/ui/badge';
import { formatCents } from '@/lib/format';
import { Link } from '@inertiajs/react';
import { ImageOff } from 'lucide-react';

export interface ProductCardData {
    id: string;
    slug: string;
    name: string;
    price_cents: number;
    in_stock: boolean;
    vendor: string | null;
    category: string | null;
    thumbnail_url: string | null;
}

export default function ProductCard({ product }: { product: ProductCardData }) {
    return (
        <Link
            href={route('products.show', product.slug)}
            className="group block overflow-hidden rounded-xl border border-border bg-card transition-all duration-300 hover:-translate-y-1 hover:border-primary/30 hover:shadow-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
        >
            <div className="aspect-square overflow-hidden bg-muted">
                {product.thumbnail_url ? (
                    <img
                        src={product.thumbnail_url}
                        alt={product.name}
                        loading="lazy"
                        className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                    />
                ) : (
                    <div className="flex h-full w-full items-center justify-center">
                        <ImageOff className="h-10 w-10 text-muted-foreground" />
                    </div>
                )}
            </div>
            <div className="p-4">
                {product.vendor && (
                    <p className="text-xs uppercase tracking-wide text-muted-foreground">
                        {product.vendor}
                    </p>
                )}
                <h3 className="mt-1 line-clamp-1 font-medium text-foreground">{product.name}</h3>
                <div className="mt-2 flex items-center justify-between">
                    <span className="font-semibold text-foreground">
                        {formatCents(product.price_cents)}
                    </span>
                    {!product.in_stock && <Badge variant="secondary">Out of stock</Badge>}
                </div>
            </div>
        </Link>
    );
}
