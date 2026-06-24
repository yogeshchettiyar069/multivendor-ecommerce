import OrderStatusBadge from '@/Components/OrderStatusBadge';
import StatCard from '@/Components/StatCard';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCents, formatDate } from '@/lib/format';
import { Head, Link } from '@inertiajs/react';
import { Receipt, ShoppingBag, Store } from 'lucide-react';

interface RecentOrder {
    id: string;
    status: string;
    totalCents: number;
    placedAt: string | null;
    itemCount: number;
}

interface Props {
    stats: {
        orders: number;
        totalSpentCents: number;
    };
    recentOrders: RecentOrder[];
}

export default function CustomerDashboard({ stats, recentOrders }: Props) {
    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold text-foreground">My Account</h2>}
        >
            <Head title="Dashboard" />

            <div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <StatCard title="Orders Placed" value={stats.orders} icon={ShoppingBag} />
                    <StatCard
                        title="Total Spent"
                        value={formatCents(stats.totalSpentCents)}
                        icon={Receipt}
                    />
                    <Card className="flex flex-col justify-center">
                        <CardContent className="flex items-center justify-between py-6">
                            <div>
                                <p className="text-sm font-medium">Sell on our marketplace</p>
                                <p className="text-xs text-muted-foreground">Open your own store</p>
                            </div>
                            <Button asChild size="sm">
                                <Link href={route('vendor.apply')}>
                                    <Store className="h-4 w-4" /> Become a Vendor
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Recent Orders</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {recentOrders.length === 0 ? (
                            <p className="py-8 text-center text-sm text-muted-foreground">
                                You haven't placed any orders yet.
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
                                            <tr
                                                key={order.id}
                                                className="border-b border-border/50"
                                            >
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
