import OrderStatusBadge from '@/Components/OrderStatusBadge';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { formatCents, formatDate } from '@/lib/format';
import { OrderDetail } from '@/types';
import { ImageOff } from 'lucide-react';

const PAYMENT_LABELS: Record<string, string> = {
    card: 'Card',
    upi: 'UPI',
    netbanking: 'Netbanking',
    cod: 'Cash on Delivery',
};

export default function OrderDetailCard({ order }: { order: OrderDetail }) {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <Card>
                <CardHeader className="flex flex-row items-center justify-between">
                    <CardTitle>Items</CardTitle>
                    <OrderStatusBadge status={order.status} />
                </CardHeader>
                <CardContent className="divide-y divide-border">
                    {order.items.map((item, i) => (
                        <div key={i} className="flex gap-4 py-4 first:pt-0">
                            <div className="h-16 w-16 shrink-0 overflow-hidden rounded-md bg-muted">
                                {item.thumbnail_url ? (
                                    <img
                                        src={item.thumbnail_url}
                                        alt=""
                                        className="h-full w-full object-cover"
                                    />
                                ) : (
                                    <div className="flex h-full w-full items-center justify-center">
                                        <ImageOff className="h-5 w-5 text-muted-foreground" />
                                    </div>
                                )}
                            </div>
                            <div className="flex-1">
                                <p className="text-sm font-medium text-foreground">
                                    {item.product_name}
                                </p>
                                {item.variant_label && (
                                    <p className="text-xs text-muted-foreground">
                                        {item.variant_label}
                                    </p>
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
                    <CardContent className="space-y-1 text-sm">
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Order</span>
                            <span className="font-mono text-xs">{order.id.slice(-10)}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Placed</span>
                            <span>{formatDate(order.placed_at ?? order.created_at)}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Payment</span>
                            <span>{PAYMENT_LABELS[order.payment_method ?? ''] ?? 'Card'}</span>
                        </div>
                        <div className="flex justify-between border-t border-border pt-2">
                            <span className="text-muted-foreground">Subtotal</span>
                            <span>{formatCents(order.subtotal_cents)}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Shipping</span>
                            <span>Free</span>
                        </div>
                        <div className="flex justify-between pt-2 text-base font-semibold">
                            <span>Total</span>
                            <span>{formatCents(order.total_cents)}</span>
                        </div>
                    </CardContent>
                </Card>

                {order.shipping && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Shipping to</CardTitle>
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
    );
}
