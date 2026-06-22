import ProductCard, { ProductCardData } from '@/Components/storefront/ProductCard';
import { Button } from '@/Components/ui/button';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';

interface Props {
    featured: ProductCardData[];
    categories: Array<{ id: string; name: string }>;
}

export default function Home({ featured, categories }: Props) {
    return (
        <StorefrontLayout>
            <Head title="Shop" />

            <section className="border-b border-border bg-primary/5">
                <div className="mx-auto max-w-7xl px-4 py-20 text-center sm:px-6 lg:px-8">
                    <h1 className="mx-auto max-w-3xl text-4xl font-bold tracking-tight text-foreground sm:text-5xl">
                        Everything you need, from independent sellers
                    </h1>
                    <p className="mx-auto mt-4 max-w-xl text-lg text-muted-foreground">
                        A curated marketplace of products from vendors around the world. One cart,
                        one checkout.
                    </p>
                    <Button asChild size="lg" className="mt-8">
                        <Link href={route('catalog')}>
                            Browse all products <ArrowRight className="h-4 w-4" />
                        </Link>
                    </Button>
                </div>
            </section>

            {categories.length > 0 && (
                <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                    <h2 className="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                        Shop by category
                    </h2>
                    <div className="flex flex-wrap gap-2">
                        {categories.map((c) => (
                            <Link
                                key={c.id}
                                href={route('catalog', { category: c.id })}
                                className="rounded-full border border-border px-4 py-1.5 text-sm transition-colors hover:border-primary hover:text-primary"
                            >
                                {c.name}
                            </Link>
                        ))}
                    </div>
                </section>
            )}

            <section className="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
                <div className="mb-6 flex items-center justify-between">
                    <h2 className="text-2xl font-bold text-foreground">Featured products</h2>
                    <Link
                        href={route('catalog')}
                        className="text-sm font-medium text-primary hover:underline"
                    >
                        View all
                    </Link>
                </div>

                {featured.length === 0 ? (
                    <p className="text-sm text-muted-foreground">No products available yet.</p>
                ) : (
                    <div className="grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-4">
                        {featured.map((product) => (
                            <ProductCard key={product.id} product={product} />
                        ))}
                    </div>
                )}
            </section>
        </StorefrontLayout>
    );
}
