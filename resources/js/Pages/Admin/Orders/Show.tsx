import OrderDetailCard from '@/Components/OrderDetailCard';
import OrderTracker from '@/Components/OrderTracker';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { OrderDetail } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface Props {
    order: OrderDetail;
    customer: { name: string | null; email: string | null };
}

export default function AdminOrderShow({ order, customer }: Props) {
    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold text-foreground">Order detail</h2>}
        >
            <Head title="Order detail" />

            <div className="mx-auto max-w-5xl space-y-4 px-4 py-8 sm:px-6 lg:px-8">
                <Link
                    href={route('admin.orders.index')}
                    className="inline-block text-sm text-muted-foreground hover:text-foreground"
                >
                    ← Back to orders
                </Link>

                <Card>
                    <CardHeader>
                        <CardTitle>Customer</CardTitle>
                    </CardHeader>
                    <CardContent className="text-sm text-muted-foreground">
                        <span className="font-medium text-foreground">{customer.name}</span> ·{' '}
                        {customer.email}
                    </CardContent>
                </Card>

                {order.status !== 'cancelled' && <OrderTracker status={order.tracking_status} />}
                <OrderDetailCard order={order} />
            </div>
        </AuthenticatedLayout>
    );
}
