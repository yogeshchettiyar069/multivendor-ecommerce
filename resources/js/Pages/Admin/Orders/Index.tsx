import OrderStatusBadge from '@/Components/OrderStatusBadge';
import { Card, CardContent } from '@/Components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCents, formatDate } from '@/lib/format';
import { Head, router } from '@inertiajs/react';

interface OrderRow {
    id: string;
    customer: string;
    status: string;
    payment_method: string | null;
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
    filters: { status: string | null };
    statuses: string[];
}

export default function AdminOrders({ orders, filters, statuses }: Props) {
    const filter = (status: string) =>
        router.get(route('admin.orders.index'), status === 'all' ? {} : { status }, {
            preserveState: true,
            replace: true,
        });

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold text-foreground">Platform Orders</h2>}
        >
            <Head title="Orders" />

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-4 flex items-center justify-between">
                    <p className="text-sm text-muted-foreground">{orders.meta.total} orders</p>
                    <Select value={filters.status ?? 'all'} onValueChange={filter}>
                        <SelectTrigger className="w-48">
                            <SelectValue placeholder="All statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All statuses</SelectItem>
                            {statuses.map((s) => (
                                <SelectItem key={s} value={s}>
                                    {s.charAt(0).toUpperCase() + s.slice(1)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <Card>
                    <CardContent className="pt-6">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Order</TableHead>
                                    <TableHead>Customer</TableHead>
                                    <TableHead>Items</TableHead>
                                    <TableHead>Total</TableHead>
                                    <TableHead>Payment</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Placed</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {orders.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="py-10 text-center text-muted-foreground">
                                            No orders found.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    orders.data.map((o) => (
                                        <TableRow
                                            key={o.id}
                                            className="cursor-pointer"
                                            onClick={() => router.visit(route('admin.orders.show', o.id))}
                                        >
                                            <TableCell className="font-mono text-xs">{o.id.slice(-10)}</TableCell>
                                            <TableCell>{o.customer}</TableCell>
                                            <TableCell>{o.item_count}</TableCell>
                                            <TableCell>{formatCents(o.total_cents)}</TableCell>
                                            <TableCell className="uppercase text-xs text-muted-foreground">
                                                {o.payment_method ?? 'card'}
                                            </TableCell>
                                            <TableCell>
                                                <OrderStatusBadge status={o.status} />
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {formatDate(o.placed_at ?? o.created_at)}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
