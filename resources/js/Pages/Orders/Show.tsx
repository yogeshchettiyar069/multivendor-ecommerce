import OrderDetailCard from '@/Components/OrderDetailCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { OrderDetail } from '@/types';
import { Head, Link } from '@inertiajs/react';

export default function OrderShow({ order }: { order: OrderDetail }) {
    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold text-foreground">Order detail</h2>}
        >
            <Head title="Order detail" />

            <div className="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
                <Link
                    href={route('orders.index')}
                    className="mb-4 inline-block text-sm text-muted-foreground hover:text-foreground"
                >
                    ← Back to orders
                </Link>
                <OrderDetailCard order={order} />
            </div>
        </AuthenticatedLayout>
    );
}
