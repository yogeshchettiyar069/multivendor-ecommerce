import OrderDetailCard from '@/Components/OrderDetailCard';
import { Button } from '@/Components/ui/button';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { OrderDetail } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';

export default function CheckoutSuccess({ order }: { order: OrderDetail }) {
    return (
        <StorefrontLayout>
            <Head title="Order confirmed" />

            <div className="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="mb-8 text-center">
                    <CheckCircle2 className="mx-auto h-12 w-12 text-emerald-500" />
                    <h1 className="mt-3 text-2xl font-bold text-foreground">Thank you for your order!</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        A confirmation has been recorded. Your order is being processed.
                    </p>
                    <div className="mt-4 flex justify-center gap-3">
                        <Button asChild variant="outline" size="sm">
                            <Link href={route('orders.index')}>View my orders</Link>
                        </Button>
                        <Button asChild size="sm">
                            <Link href={route('catalog')}>Continue shopping</Link>
                        </Button>
                    </div>
                </div>

                <OrderDetailCard order={order} />
            </div>
        </StorefrontLayout>
    );
}
