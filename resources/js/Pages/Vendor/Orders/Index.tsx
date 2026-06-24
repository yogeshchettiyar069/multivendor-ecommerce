import OrderStatusBadge from '@/Components/OrderStatusBadge';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent } from '@/Components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCents, formatDate } from '@/lib/format';
import { Head, router } from '@inertiajs/react';
import { PackageCheck } from 'lucide-react';

interface OrderRow {
    id: string;
    status: string;
    tracking_status: string;
    payment_method: string | null;
    my_items: number;
    my_revenue_cents: number;
    placed_at: string | null;
    created_at: string | null;
}

const TRACKING_LABELS: Record<string, string> = {
    placed: 'Order Placed',
    packed: 'Packed',
    shipped: 'Shipped',
    out_for_delivery: 'Out for Delivery',
    delivered: 'Delivered',
};

interface Props {
    orders: {
        data: OrderRow[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        meta: { total: number; from: number | null; to: number | null };
    };
}

export default function VendorOrders({ orders }: Props) {
    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold text-foreground">Orders</h2>}
        >
            <Head title="Orders" />

            <div className="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
                <Card>
                    <CardContent className="pt-6">
                        {orders.data.length === 0 ? (
                            <div className="py-16 text-center">
                                <PackageCheck className="mx-auto h-10 w-10 text-muted-foreground" />
                                <p className="mt-3 text-sm text-muted-foreground">
                                    No orders yet. Sales for your products will appear here.
                                </p>
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Order</TableHead>
                                        <TableHead>Your items</TableHead>
                                        <TableHead>Your revenue</TableHead>
                                        <TableHead>Payment</TableHead>
                                        <TableHead>Order status</TableHead>
                                        <TableHead>Fulfilment</TableHead>
                                        <TableHead>Placed</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {orders.data.map((o) => (
                                        <TableRow
                                            key={o.id}
                                            className="cursor-pointer"
                                            onClick={() =>
                                                router.visit(route('vendor.orders.show', o.id))
                                            }
                                        >
                                            <TableCell className="font-mono text-xs">
                                                {o.id.slice(-10)}
                                            </TableCell>
                                            <TableCell>{o.my_items}</TableCell>
                                            <TableCell>{formatCents(o.my_revenue_cents)}</TableCell>
                                            <TableCell className="text-xs uppercase text-muted-foreground">
                                                {o.payment_method ?? 'card'}
                                            </TableCell>
                                            <TableCell>
                                                <OrderStatusBadge status={o.status} />
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        o.tracking_status === 'delivered'
                                                            ? 'success'
                                                            : 'warning'
                                                    }
                                                >
                                                    {TRACKING_LABELS[o.tracking_status] ??
                                                        'Order Placed'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {formatDate(o.placed_at ?? o.created_at)}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
