import OrderStatusBadge from '@/Components/OrderStatusBadge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCents, formatDate } from '@/lib/format';
import { Head, Link, router } from '@inertiajs/react';
import { ShoppingBag } from 'lucide-react';

interface OrderRow {
    id: string;
    status: string;
    total_cents: number;
    item_count: number;
    placed_at: string | null;
    created_at: string | null;
}

interface Props {
    orders: {
        data: OrderRow[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        meta: { total: number; from: number | null; to: number | null };
    };
}

export default function OrdersIndex({ orders }: Props) {
    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold text-foreground">My Orders</h2>}
        >
            <Head title="My Orders" />

            <div className="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
                <Card>
                    <CardContent className="pt-6">
                        {orders.data.length === 0 ? (
                            <div className="py-16 text-center">
                                <ShoppingBag className="mx-auto h-10 w-10 text-muted-foreground" />
                                <p className="mt-3 text-sm text-muted-foreground">
                                    You haven't placed any orders yet.
                                </p>
                                <Button asChild size="sm" className="mt-4">
                                    <Link href={route('catalog')}>Start shopping</Link>
                                </Button>
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Order</TableHead>
                                        <TableHead>Items</TableHead>
                                        <TableHead>Total</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Placed</TableHead>
                                        <TableHead />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {orders.data.map((order) => (
                                        <TableRow
                                            key={order.id}
                                            className="cursor-pointer"
                                            onClick={() => router.visit(route('orders.show', order.id))}
                                        >
                                            <TableCell className="font-mono text-xs">
                                                {order.id.slice(-10)}
                                            </TableCell>
                                            <TableCell>{order.item_count}</TableCell>
                                            <TableCell>{formatCents(order.total_cents)}</TableCell>
                                            <TableCell>
                                                <OrderStatusBadge status={order.status} />
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {formatDate(order.placed_at ?? order.created_at)}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Link
                                                    href={route('orders.show', order.id)}
                                                    className="text-sm font-medium text-primary hover:underline"
                                                >
                                                    View
                                                </Link>
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
