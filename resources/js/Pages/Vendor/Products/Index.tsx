import Modal from '@/Components/Modal';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCents } from '@/lib/format';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, ChevronsUpDown, ImageOff, Pencil, Plus, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';

interface ProductRow {
    id: string;
    name: string;
    category: string | null;
    base_price_cents: number;
    stock: number;
    variant_count: number;
    status: string;
    thumbnail_url: string | null;
}

interface Props {
    products: {
        data: ProductRow[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        meta: { current_page: number; last_page: number; total: number; from: number | null; to: number | null };
    };
    filters: { search: string; sort: string; direction: string };
}

export default function ProductsIndex({ products, filters }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [deleteId, setDeleteId] = useState<string | null>(null);
    const [deleting, setDeleting] = useState(false);

    // Debounced search.
    useEffect(() => {
        if (search === filters.search) return;
        const handle = setTimeout(() => {
            router.get(
                route('vendor.products.index'),
                { search, sort: filters.sort, direction: filters.direction },
                { preserveState: true, replace: true },
            );
        }, 300);
        return () => clearTimeout(handle);
    }, [search]);

    const sortBy = (column: string) => {
        const direction =
            filters.sort === column && filters.direction === 'asc' ? 'desc' : 'asc';
        router.get(
            route('vendor.products.index'),
            { search, sort: column, direction },
            { preserveState: true, replace: true },
        );
    };

    const SortIcon = ({ column }: { column: string }) => {
        if (filters.sort !== column) return <ChevronsUpDown className="h-3.5 w-3.5 opacity-50" />;
        return filters.direction === 'asc' ? (
            <ArrowUp className="h-3.5 w-3.5" />
        ) : (
            <ArrowDown className="h-3.5 w-3.5" />
        );
    };

    const confirmDelete = () => {
        if (!deleteId) return;
        setDeleting(true);
        router.delete(route('vendor.products.destroy', deleteId), {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setDeleteId(null);
            },
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold text-foreground">Products</h2>
                    <Button asChild size="sm">
                        <Link href={route('vendor.products.create')}>
                            <Plus className="h-4 w-4" /> New Product
                        </Link>
                    </Button>
                </div>
            }
        >
            <Head title="Products" />

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <Card>
                    <CardContent className="pt-6">
                        <div className="mb-4 max-w-sm">
                            <Input
                                placeholder="Search products…"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>

                        {products.data.length === 0 ? (
                            <div className="py-16 text-center">
                                <p className="text-sm text-muted-foreground">
                                    {filters.search
                                        ? 'No products match your search.'
                                        : 'You have no products yet.'}
                                </p>
                                {!filters.search && (
                                    <Button asChild className="mt-4" size="sm">
                                        <Link href={route('vendor.products.create')}>
                                            <Plus className="h-4 w-4" /> Add your first product
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        ) : (
                            <>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-14" />
                                            <TableHead>
                                                <button
                                                    className="flex items-center gap-1"
                                                    onClick={() => sortBy('name')}
                                                >
                                                    Name <SortIcon column="name" />
                                                </button>
                                            </TableHead>
                                            <TableHead>Category</TableHead>
                                            <TableHead>
                                                <button
                                                    className="flex items-center gap-1"
                                                    onClick={() => sortBy('base_price_cents')}
                                                >
                                                    Price <SortIcon column="base_price_cents" />
                                                </button>
                                            </TableHead>
                                            <TableHead>Stock</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead className="text-right">Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {products.data.map((product) => (
                                            <TableRow key={product.id}>
                                                <TableCell>
                                                    {product.thumbnail_url ? (
                                                        <img
                                                            src={product.thumbnail_url}
                                                            alt=""
                                                            className="h-10 w-10 rounded-md object-cover"
                                                        />
                                                    ) : (
                                                        <div className="flex h-10 w-10 items-center justify-center rounded-md bg-muted">
                                                            <ImageOff className="h-4 w-4 text-muted-foreground" />
                                                        </div>
                                                    )}
                                                </TableCell>
                                                <TableCell className="font-medium">
                                                    {product.name}
                                                    <span className="block text-xs text-muted-foreground">
                                                        {product.variant_count} variant
                                                        {product.variant_count === 1 ? '' : 's'}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-muted-foreground">
                                                    {product.category ?? '—'}
                                                </TableCell>
                                                <TableCell>
                                                    {formatCents(product.base_price_cents)}
                                                </TableCell>
                                                <TableCell>
                                                    <span
                                                        className={
                                                            product.stock === 0
                                                                ? 'text-destructive'
                                                                : ''
                                                        }
                                                    >
                                                        {product.stock}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        variant={
                                                            product.status === 'published'
                                                                ? 'success'
                                                                : 'secondary'
                                                        }
                                                    >
                                                        {product.status}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex justify-end gap-1">
                                                        <Button
                                                            asChild
                                                            variant="ghost"
                                                            size="icon"
                                                        >
                                                            <Link
                                                                href={route(
                                                                    'vendor.products.edit',
                                                                    product.id,
                                                                )}
                                                                aria-label="Edit"
                                                            >
                                                                <Pencil className="h-4 w-4" />
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => setDeleteId(product.id)}
                                                            aria-label="Delete"
                                                        >
                                                            <Trash2 className="h-4 w-4 text-destructive" />
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>

                                <div className="mt-4 flex items-center justify-between">
                                    <p className="text-sm text-muted-foreground">
                                        Showing {products.meta.from ?? 0}–{products.meta.to ?? 0} of{' '}
                                        {products.meta.total}
                                    </p>
                                    <div className="flex flex-wrap gap-1">
                                        {products.links.map((link, i) => (
                                            <button
                                                key={i}
                                                disabled={!link.url}
                                                onClick={() =>
                                                    link.url &&
                                                    router.get(
                                                        link.url,
                                                        {},
                                                        { preserveState: true, replace: true },
                                                    )
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
                                </div>
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Modal show={deleteId !== null} onClose={() => setDeleteId(null)} maxWidth="md">
                <div className="p-6">
                    <h3 className="text-lg font-semibold text-foreground">Delete product?</h3>
                    <p className="mt-2 text-sm text-muted-foreground">
                        This permanently removes the product and its variants. This action cannot be
                        undone.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <Button variant="outline" onClick={() => setDeleteId(null)}>
                            Cancel
                        </Button>
                        <Button variant="destructive" onClick={confirmDelete} disabled={deleting}>
                            Delete
                        </Button>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
