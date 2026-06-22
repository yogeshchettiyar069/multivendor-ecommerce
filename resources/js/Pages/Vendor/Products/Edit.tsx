import ProductForm, { ProductFormData } from '@/Components/ProductForm';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

interface EditableProduct {
    id: string;
    name: string;
    category_id: string;
    description: string | null;
    base_price: string;
    status: string;
    thumbnail_url: string | null;
    variants: Array<{
        id: string;
        sku: string;
        size: string;
        color: string;
        price: string;
        stock: number;
    }>;
}

interface Props {
    product: EditableProduct;
    categories: Array<{ id: string; label: string }>;
    statuses: Array<{ value: string; label: string }>;
}

export default function EditProduct({ product, categories, statuses }: Props) {
    const initial: ProductFormData = {
        name: product.name,
        category_id: product.category_id,
        description: product.description ?? '',
        base_price: product.base_price,
        status: product.status,
        thumbnail: null,
        variants: product.variants.map((v) => ({
            id: v.id,
            sku: v.sku,
            size: v.size,
            color: v.color,
            price: v.price,
            stock: String(v.stock),
        })),
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold text-foreground">Edit Product</h2>}
        >
            <Head title={`Edit ${product.name}`} />
            <div className="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
                <ProductForm
                    mode="edit"
                    action={route('vendor.products.update', product.id)}
                    initial={initial}
                    categories={categories}
                    statuses={statuses}
                    existingThumbnailUrl={product.thumbnail_url}
                />
            </div>
        </AuthenticatedLayout>
    );
}
