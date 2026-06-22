import OrderStatusBadge from '@/Components/OrderStatusBadge';
import StatCard from '@/Components/StatCard';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCents, formatDate } from '@/lib/format';
import { Head } from '@inertiajs/react';
import { ClipboardList, DollarSign, Package, Store } from 'lucide-react';

interface RecentOrder {
    id: string;
    status: string;
    totalCents: number;
    placedAt: string | null;
    itemCount: number;
}

interface Props {
    stats: {
        pendingVendors: number;
        totalVendors: number;
        totalProducts: number;
        totalOrders: number;
        revenueCents: number;
    };
    recentOrders: RecentOrder[];
}

export default function AdminDashboard({ stats, recentOrders }: Props) {
    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold text-foreground">Admin Overview</h2>}
        >
            <Head title="Admin Dashboard" />

            <div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        title="Platform Revenue"
                        value={formatCents(stats.revenueCents)}
                        icon={DollarSign}
                        hint="Paid & fulfilled orders"
                    />
                    <StatCard
                        title="Vendors"
                        value={stats.totalVendors}
                        icon={Store}
                        hint={`${stats.pendingVendors} pending approval`}
                    />
                    <StatCard title="Products" value={stats.totalProducts} icon={Package} />
                    <StatCard title="Orders" value={stats.totalOrders} icon={ClipboardList} />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Recent Orders</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {recentOrders.length === 0 ? (
                            <p className="py-8 text-center text-sm text-muted-foreground">
                                No orders yet.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-border text-left text-muted-foreground">
                                            <th className="py-2 pr-4 font-medium">Order</th>
                                            <th className="py-2 pr-4 font-medium">Items</th>
                                            <th className="py-2 pr-4 font-medium">Total</th>
                                            <th className="py-2 pr-4 font-medium">Status</th>
                                            <th className="py-2 font-medium">Placed</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {recentOrders.map((order) => (
                                            <tr key={order.id} className="border-b border-border/50">
                                                <td className="py-2 pr-4 font-mono text-xs">
                                                    {order.id.slice(-8)}
                                                </td>
                                                <td className="py-2 pr-4">{order.itemCount}</td>
                                                <td className="py-2 pr-4">
                                                    {formatCents(order.totalCents)}
                                                </td>
                                                <td className="py-2 pr-4">
                                                    <OrderStatusBadge status={order.status} />
                                                </td>
                                                <td className="py-2 text-muted-foreground">
                                                    {formatDate(order.placedAt)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
