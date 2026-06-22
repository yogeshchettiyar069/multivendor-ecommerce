import ProductCard, { ProductCardData } from '@/Components/storefront/ProductCard';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Skeleton } from '@/Components/ui/skeleton';
import { Slider } from '@/Components/ui/slider';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { formatCents } from '@/lib/format';
import { Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

interface CategoryNode {
    id: string;
    name: string;
    children: Array<{ id: string; name: string }>;
}

interface Filters {
    search: string;
    category: string;
    min_price: string | null;
    max_price: string | null;
    in_stock: boolean;
    sort: string;
}

interface Props {
    products: {
        data: ProductCardData[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        meta: { total: number; from: number | null; to: number | null };
    };
    categories: CategoryNode[];
    priceBounds: { min: number; max: number };
    filters: Filters;
}

const SORTS = [
    { value: 'newest', label: 'Newest' },
    { value: 'price_asc', label: 'Price: low to high' },
    { value: 'price_desc', label: 'Price: high to low' },
    { value: 'name', label: 'Name A–Z' },
];

export default function Catalog({ products, categories, priceBounds, filters }: Props) {
    const minBound = Math.floor(priceBounds.min / 100);
    const maxBound = Math.ceil(priceBounds.max / 100);
    const hasRange = maxBound > minBound;

    const [search, setSearch] = useState(filters.search ?? '');
    const [range, setRange] = useState<[number, number]>([
        filters.min_price ? Number(filters.min_price) : minBound,
        filters.max_price ? Number(filters.max_price) : maxBound,
    ]);
    const [loading, setLoading] = useState(false);

    const apply = (overrides: Record<string, string | number | undefined>) => {
        const params: Record<string, string | number | undefined> = {
            search: search || undefined,
            category: filters.category || undefined,
            in_stock: filters.in_stock ? 1 : undefined,
            sort: filters.sort && filters.sort !== 'newest' ? filters.sort : undefined,
            min_price: range[0] !== minBound ? range[0] : undefined,
            max_price: range[1] !== maxBound ? range[1] : undefined,
            ...overrides,
        };
        router.get(route('catalog'), params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => setLoading(true),
            onFinish: () => setLoading(false),
        });
    };

    // Debounced search.
    useEffect(() => {
        if (search === (filters.search ?? '')) return;
        const handle = setTimeout(() => apply({ search: search || undefined }), 350);
        return () => clearTimeout(handle);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const activeCategory = filters.category;

    return (
        <StorefrontLayout>
            <Head title="Shop" />

            <div className="mx-auto max-w-7xl gap-8 px-4 py-8 sm:px-6 lg:flex lg:px-8">
                {/* Sidebar */}
                <aside className="mb-6 shrink-0 lg:mb-0 lg:w-64">
                    <div className="space-y-6">
                        <div>
                            <h3 className="mb-2 text-sm font-semibold text-foreground">Search</h3>
                            <Input
                                placeholder="Search products…"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>

                        <div>
                            <h3 className="mb-2 text-sm font-semibold text-foreground">Categories</h3>
                            <ul className="space-y-1 text-sm">
                                <li>
                                    <button
                                        onClick={() => apply({ category: undefined })}
                                        className={
                                            !activeCategory
                                                ? 'font-medium text-primary'
                                                : 'text-muted-foreground hover:text-foreground'
                                        }
                                    >
                                        All products
                                    </button>
                                </li>
                                {categories.map((root) => (
                                    <li key={root.id}>
                                        <button
                                            onClick={() => apply({ category: root.id })}
                                            className={
                                                activeCategory === root.id
                                                    ? 'font-medium text-primary'
                                                    : 'text-foreground hover:text-primary'
                                            }
                                        >
                                            {root.name}
                                        </button>
                                        {root.children.length > 0 && (
                                            <ul className="ml-3 mt-1 space-y-1 border-l border-border pl-3">
                                                {root.children.map((child) => (
                                                    <li key={child.id}>
                                                        <button
                                                            onClick={() =>
                                                                apply({ category: child.id })
                                                            }
                                                            className={
                                                                activeCategory === child.id
                                                                    ? 'font-medium text-primary'
                                                                    : 'text-muted-foreground hover:text-foreground'
                                                            }
                                                        >
                                                            {child.name}
                                                        </button>
                                                    </li>
                                                ))}
                                            </ul>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </div>

                        {hasRange && (
                            <div>
                                <h3 className="mb-3 text-sm font-semibold text-foreground">
                                    Price range
                                </h3>
                                <Slider
                                    min={minBound}
                                    max={maxBound}
                                    step={1}
                                    value={range}
                                    onValueChange={(v) => setRange([v[0], v[1]])}
                                    onValueCommit={(v) =>
                                        apply({ min_price: v[0], max_price: v[1] })
                                    }
                                />
                                <div className="mt-2 flex justify-between text-xs text-muted-foreground">
                                    <span>${range[0]}</span>
                                    <span>${range[1]}</span>
                                </div>
                            </div>
                        )}

                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={filters.in_stock}
                                onChange={(e) =>
                                    apply({ in_stock: e.target.checked ? 1 : undefined })
                                }
                                className="rounded border-input text-primary focus:ring-ring"
                            />
                            In stock only
                        </label>
                    </div>
                </aside>

                {/* Results */}
                <div className="flex-1">
                    <div className="mb-4 flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            {products.meta.total} product{products.meta.total === 1 ? '' : 's'}
                        </p>
                        <Select
                            value={filters.sort}
                            onValueChange={(v) => apply({ sort: v === 'newest' ? undefined : v })}
                        >
                            <SelectTrigger className="w-48">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {SORTS.map((s) => (
                                    <SelectItem key={s.value} value={s.value}>
                                        {s.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    {loading ? (
                        <div className="grid grid-cols-2 gap-6 md:grid-cols-3">
                            {Array.from({ length: 6 }).map((_, i) => (
                                <div key={i} className="overflow-hidden rounded-xl border border-border">
                                    <Skeleton className="aspect-square w-full rounded-none" />
                                    <div className="space-y-2 p-4">
                                        <Skeleton className="h-3 w-1/3" />
                                        <Skeleton className="h-4 w-2/3" />
                                        <Skeleton className="h-4 w-1/4" />
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : products.data.length === 0 ? (
                        <div className="rounded-xl border border-dashed border-border py-20 text-center">
                            <p className="text-sm text-muted-foreground">
                                No products match your filters.
                            </p>
                            <Button
                                variant="outline"
                                size="sm"
                                className="mt-4"
                                onClick={() => router.get(route('catalog'))}
                            >
                                Clear filters
                            </Button>
                        </div>
                    ) : (
                        <div className="grid grid-cols-2 gap-6 md:grid-cols-3">
                            {products.data.map((product) => (
                                <ProductCard key={product.id} product={product} />
                            ))}
                        </div>
                    )}

                    {products.links.length > 3 && (
                        <div className="mt-8 flex flex-wrap justify-center gap-1">
                            {products.links.map((link, i) => (
                                <button
                                    key={i}
                                    disabled={!link.url}
                                    onClick={() =>
                                        link.url &&
                                        router.get(link.url, {}, { preserveState: true, preserveScroll: true })
                                    }
                                    className={
                                        'min-w-9 rounded-md border px-3 py-1 text-sm transition-colors disabled:opacity-40 ' +
                                        (link.active
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-border hover:bg-accent')
                                    }
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </StorefrontLayout>
    );
}
