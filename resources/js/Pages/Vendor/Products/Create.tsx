import ProductForm, { ProductFormData } from '@/Components/ProductForm';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

interface Props {
    categories: Array<{ id: string; label: string }>;
    statuses: Array<{ value: string; label: string }>;
}

export default function CreateProduct({ categories, statuses }: Props) {
    const initial: ProductFormData = {
        name: '',
        category_id: '',
        description: '',
        base_price: '',
        status: 'published',
        thumbnail: null,
        variants: [{ id: null, sku: '', size: '', color: '', price: '', stock: '' }],
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold text-foreground">New Product</h2>}
        >
            <Head title="New Product" />
            <div className="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
                <ProductForm
                    mode="create"
                    action={route('vendor.products.store')}
                    initial={initial}
                    categories={categories}
                    statuses={statuses}
                />
            </div>
        </AuthenticatedLayout>
    );
}
