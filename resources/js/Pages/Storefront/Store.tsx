import ProductCard, { ProductCardData } from '@/Components/storefront/ProductCard';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { Head, router } from '@inertiajs/react';
import { Store as StoreIcon } from 'lucide-react';

interface Props {
    vendor: { name: string; slug: string; bio: string | null };
    products: {
        data: ProductCardData[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        meta: { total: number; from: number | null; to: number | null };
    };
}

export default function Store({ vendor, products }: Props) {
    return (
        <StorefrontLayout>
            <Head title={vendor.name} />

            <section className="border-b border-border bg-gradient-to-b from-primary/10 via-primary/5 to-background">
                <div className="mx-auto max-w-7xl px-4 py-12 duration-500 animate-in fade-in slide-in-from-bottom-2 sm:px-6 lg:px-8">
                    <div className="flex items-center gap-4">
                        <div className="flex h-14 w-14 items-center justify-center rounded-full bg-primary/10">
                            <StoreIcon className="h-7 w-7 text-primary" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold text-foreground sm:text-3xl">
                                {vendor.name}
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                {products.meta.total} product{products.meta.total === 1 ? '' : 's'}
                            </p>
                        </div>
                    </div>
                    {vendor.bio && (
                        <p className="mt-4 max-w-2xl text-muted-foreground">{vendor.bio}</p>
                    )}
                </div>
            </section>

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                {products.data.length === 0 ? (
                    <p className="py-16 text-center text-sm text-muted-foreground">
                        This store has no products yet.
                    </p>
                ) : (
                    <div className="grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-4">
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
        </StorefrontLayout>
    );
}
