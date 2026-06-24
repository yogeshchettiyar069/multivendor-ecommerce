import OrderStatusBadge from '@/Components/OrderStatusBadge';
import OrderTracker from '@/Components/OrderTracker';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
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
    tracking_status: string;
    payment_method: string | null;
    placed_at: string | null;
    created_at: string | null;
    shipping: OrderShipping | null;
    items: VendorOrderItem[];
    my_revenue_cents: number;
}

const STAGES = [
    { value: 'placed', label: 'Order Placed' },
    { value: 'packed', label: 'Packed' },
    { value: 'shipped', label: 'Shipped' },
    { value: 'out_for_delivery', label: 'Out for Delivery' },
    { value: 'delivered', label: 'Delivered' },
];

export default function VendorOrderShow({ order }: { order: VendorOrder }) {
    const [stage, setStage] = useState(order.tracking_status);
    const [working, setWorking] = useState(false);

    const update = () => {
        setWorking(true);
        router.patch(
            route('vendor.orders.tracking', order.id),
            { tracking_status: stage },
            { preserveScroll: true, onFinish: () => setWorking(false) },
        );
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

            <div className="mx-auto max-w-5xl space-y-6 px-4 py-8 duration-500 animate-in fade-in slide-in-from-bottom-2 sm:px-6 lg:px-8">
                <Link
                    href={route('vendor.orders.index')}
                    className="inline-block text-sm text-muted-foreground hover:text-foreground"
                >
                    ← Back to orders
                </Link>

                {order.status !== 'cancelled' && <OrderTracker status={order.tracking_status} />}

                <Card>
                    <CardHeader>
                        <CardTitle>Update shipment status</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <Select value={stage} onValueChange={setStage}>
                            <SelectTrigger className="sm:w-64">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {STAGES.map((s) => (
                                    <SelectItem key={s.value} value={s.value}>
                                        {s.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Button onClick={update} disabled={working || stage === order.tracking_status}>
                            Update status
                        </Button>
                        <p className="text-xs text-muted-foreground">
                            Marking <span className="font-medium">Delivered</span> completes the order
                            and releases your payout.
                        </p>
                    </CardContent>
                </Card>

                <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
                    <Card>
                        <CardHeader>
                            <CardTitle>Your items</CardTitle>
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
                                    <div className="text-sm font-medium">
                                        {formatCents(item.line_total_cents)}
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
