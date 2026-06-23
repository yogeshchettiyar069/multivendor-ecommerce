import OrderStatusBadge from '@/Components/OrderStatusBadge';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCents, formatDate } from '@/lib/format';
import { OrderShipping } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ImageOff } from 'lucide-react';
import { useState } from 'react';

interface VendorOrderItem {
    product_name: string;
    variant_label: string | null;
    unit_price_cents: number;
    quantity: number;
    line_total_cents: number;
    thumbnail_url: string | null;
    fulfilled: boolean;
}

interface VendorOrder {
    id: string;
    status: string;
    payment_method: string | null;
    placed_at: string | null;
    created_at: string | null;
    shipping: OrderShipping | null;
    items: VendorOrderItem[];
    my_revenue_cents: number;
    fulfilled: boolean;
}

export default function VendorOrderShow({ order }: { order: VendorOrder }) {
    const [working, setWorking] = useState(false);

    const fulfill = () => {
        setWorking(true);
        router.patch(route('vendor.orders.fulfill', order.id), {}, {
            preserveScroll: true,
            onFinish: () => setWorking(false),
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center gap-3">
                    <h2 className="text-xl font-semibold text-foreground">Order</h2>
                    <OrderStatusBadge status={order.status} />
                </div>
            }
        >
            <Head title="Order" />

            <div className="mx-auto max-w-5xl space-y-4 px-4 py-8 sm:px-6 lg:px-8">
                <Link
                    href={route('vendor.orders.index')}
                    className="inline-block text-sm text-muted-foreground hover:text-foreground"
                >
                    ← Back to orders
                </Link>

                <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>Your items</CardTitle>
                            <Badge variant={order.fulfilled ? 'success' : 'warning'}>
                                {order.fulfilled ? 'Fulfilled' : 'To fulfil'}
                            </Badge>
                        </CardHeader>
                        <CardContent className="divide-y divide-border">
                            {order.items.map((item, i) => (
                                <div key={i} className="flex gap-4 py-4 first:pt-0">
                                    <div className="h-16 w-16 shrink-0 overflow-hidden rounded-md bg-muted">
                                        {item.thumbnail_url ? (
                                            <img src={item.thumbnail_url} alt="" className="h-full w-full object-cover" />
                                        ) : (
                                            <div className="flex h-full w-full items-center justify-center">
                                                <ImageOff className="h-5 w-5 text-muted-foreground" />
                                            </div>
                                        )}
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-sm font-medium text-foreground">{item.product_name}</p>
                                        {item.variant_label && (
                                            <p className="text-xs text-muted-foreground">{item.variant_label}</p>
                                        )}
                                        <p className="text-xs text-muted-foreground">
                                            {formatCents(item.unit_price_cents)} × {item.quantity}
                                        </p>
                                    </div>
                                    <div className="text-right text-sm">
                                        <div className="font-medium">{formatCents(item.line_total_cents)}</div>
                                        {item.fulfilled && (
                                            <span className="text-xs text-emerald-600">Fulfilled</span>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Summary</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Your revenue</span>
                                    <span className="font-semibold">{formatCents(order.my_revenue_cents)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Placed</span>
                                    <span>{formatDate(order.placed_at ?? order.created_at)}</span>
                                </div>
                                {!order.fulfilled && (
                                    <Button className="mt-2 w-full" onClick={fulfill} disabled={working}>
                                        Mark my items as fulfilled
                                    </Button>
                                )}
                            </CardContent>
                        </Card>

                        {order.shipping && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Ship to</CardTitle>
                                </CardHeader>
                                <CardContent className="text-sm text-muted-foreground">
                                    <p className="font-medium text-foreground">{order.shipping.name}</p>
                                    <p>{order.shipping.address}</p>
                                    <p>
                                        {order.shipping.city} {order.shipping.postal_code}
                                    </p>
                                    <p>{order.shipping.country}</p>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
