import PaymentForm from '@/Components/checkout/PaymentForm';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { formatCents } from '@/lib/format';
import { CartSummary } from '@/types';
import { Head } from '@inertiajs/react';
import { Elements } from '@stripe/react-stripe-js';
import { loadStripe } from '@stripe/stripe-js';
import axios from 'axios';
import { FormEvent, useMemo, useState } from 'react';

interface Props {
    cart: CartSummary;
    stripeKey: string | null;
    customer: { name: string; email: string };
}

const FIELDS: Array<{ key: keyof ShippingState; label: string; type?: string; half?: boolean }> = [
    { key: 'name', label: 'Full name' },
    { key: 'email', label: 'Email', type: 'email' },
    { key: 'address', label: 'Address' },
    { key: 'city', label: 'City', half: true },
    { key: 'postal_code', label: 'Postal code', half: true },
    { key: 'country', label: 'Country' },
];

interface ShippingState {
    name: string;
    email: string;
    address: string;
    city: string;
    postal_code: string;
    country: string;
}

export default function Checkout({ cart, stripeKey, customer }: Props) {
    const stripePromise = useMemo(() => (stripeKey ? loadStripe(stripeKey) : null), [stripeKey]);

    const [shipping, setShipping] = useState<ShippingState>({
        name: customer.name,
        email: customer.email,
        address: '',
        city: '',
        postal_code: '',
        country: '',
    });
    const [step, setStep] = useState<'shipping' | 'payment'>('shipping');
    const [clientSecret, setClientSecret] = useState<string | null>(null);
    const [orderId, setOrderId] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const isDark =
        typeof document !== 'undefined' && document.documentElement.classList.contains('dark');

    const submitShipping = async (e: FormEvent) => {
        e.preventDefault();
        setSubmitting(true);
        setError(null);

        try {
            const { data } = await axios.post<{ clientSecret: string; orderId: string }>(
                route('checkout.store'),
                shipping,
            );
            setClientSecret(data.clientSecret);
            setOrderId(data.orderId);
            setStep('payment');
        } catch (err) {
            const message = (err as { response?: { data?: { message?: string } } })?.response?.data
                ?.message;
            setError(message ?? 'Something went wrong. Please try again.');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <StorefrontLayout>
            <Head title="Checkout" />

            <div className="mx-auto grid max-w-5xl gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[1fr_360px] lg:px-8">
                <div className="space-y-6">
                    <div className="flex items-center gap-2 text-sm">
                        <span className={step === 'shipping' ? 'font-semibold text-primary' : 'text-muted-foreground'}>
                            1. Shipping
                        </span>
                        <span className="text-muted-foreground">→</span>
                        <span className={step === 'payment' ? 'font-semibold text-primary' : 'text-muted-foreground'}>
                            2. Payment
                        </span>
                    </div>

                    {!stripeKey && (
                        <Card>
                            <CardContent className="py-6 text-sm text-destructive">
                                Stripe is not configured. Add STRIPE_KEY / STRIPE_SECRET to your
                                environment.
                            </CardContent>
                        </Card>
                    )}

                    {step === 'shipping' && stripeKey && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Shipping details</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={submitShipping} className="grid grid-cols-2 gap-4">
                                    {FIELDS.map((f) => (
                                        <div key={f.key} className={f.half ? 'col-span-1' : 'col-span-2'}>
                                            <Label htmlFor={f.key}>{f.label}</Label>
                                            <Input
                                                id={f.key}
                                                type={f.type ?? 'text'}
                                                value={shipping[f.key]}
                                                onChange={(e) =>
                                                    setShipping((s) => ({ ...s, [f.key]: e.target.value }))
                                                }
                                                required
                                                className="mt-1"
                                            />
                                        </div>
                                    ))}
                                    {error && (
                                        <p className="col-span-2 text-sm text-destructive">{error}</p>
                                    )}
                                    <div className="col-span-2">
                                        <Button type="submit" size="lg" className="w-full" disabled={submitting}>
                                            {submitting ? 'Preparing payment…' : 'Continue to payment'}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    )}

                    {step === 'payment' && stripePromise && clientSecret && orderId && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Payment</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Elements
                                    stripe={stripePromise}
                                    options={{
                                        clientSecret,
                                        appearance: { theme: isDark ? 'night' : 'stripe' },
                                    }}
                                >
                                    <PaymentForm orderId={orderId} />
                                </Elements>
                            </CardContent>
                        </Card>
                    )}
                </div>

                <aside>
                    <Card>
                        <CardHeader>
                            <CardTitle>Order summary</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <ul className="space-y-3">
                                {cart.items.map((item) => (
                                    <li key={item.item_id} className="flex justify-between gap-3 text-sm">
                                        <span className="text-muted-foreground">
                                            {item.name}
                                            <span className="block text-xs">
                                                {item.variant_label} × {item.quantity}
                                            </span>
                                        </span>
                                        <span className="font-medium">
                                            {formatCents(item.line_total_cents)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                            <div className="space-y-1 border-t border-border pt-4 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Subtotal</span>
                                    <span>{formatCents(cart.subtotalCents)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Shipping</span>
                                    <span>Free</span>
                                </div>
                                <div className="flex justify-between pt-2 text-base font-semibold">
                                    <span>Total</span>
                                    <span>{formatCents(cart.subtotalCents)}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </aside>
            </div>
        </StorefrontLayout>
    );
}
