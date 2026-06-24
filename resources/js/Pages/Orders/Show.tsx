import OrderDetailCard from '@/Components/OrderDetailCard';
import OrderTracker from '@/Components/OrderTracker';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { OrderDetail } from '@/types';
import { Head, Link } from '@inertiajs/react';

export default function OrderShow({ order }: { order: OrderDetail }) {
    const cancelled = order.status === 'cancelled' || order.status === 'refunded';

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold text-foreground">Order detail</h2>}
        >
            <Head title="Order detail" />

            <div className="mx-auto max-w-5xl space-y-6 px-4 py-8 duration-500 animate-in fade-in slide-in-from-bottom-2 sm:px-6 lg:px-8">
                <Link
                    href={route('orders.index')}
                    className="inline-block text-sm text-muted-foreground hover:text-foreground"
                >
                    ← Back to orders
                </Link>
                {!cancelled && <OrderTracker status={order.tracking_status} />}
                <OrderDetailCard order={order} />
            </div>
        </AuthenticatedLayout>
    );
}
