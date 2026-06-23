import PaymentForm from '@/Components/checkout/PaymentForm';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { cn } from '@/lib/utils';
import { formatCents } from '@/lib/format';
import { CartSummary, OrderShipping } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Elements } from '@stripe/react-stripe-js';
import { loadStripe } from '@stripe/stripe-js';
import { Banknote, CreditCard, Landmark, Smartphone } from 'lucide-react';
import axios from 'axios';
import { FormEvent, useMemo, useState } from 'react';

interface Props {
    cart: CartSummary;
    stripeKey: string | null;
    customer: { name: string; email: string };
    savedAddress: OrderShipping | null;
}

interface ShippingState {
    name: string;
    email: string;
    address: string;
    city: string;
    postal_code: string;
    country: string;
}

type PaymentMethod = 'card' | 'upi' | 'netbanking' | 'cod';

const FIELDS: Array<{ key: keyof ShippingState; label: string; type?: string; half?: boolean }> = [
    { key: 'name', label: 'Full name' },
    { key: 'email', label: 'Email', type: 'email' },
    { key: 'address', label: 'Address' },
    { key: 'city', label: 'City', half: true },
    { key: 'postal_code', label: 'Postal code', half: true },
    { key: 'country', label: 'Country' },
];

const METHODS: Array<{ value: PaymentMethod; label: string; icon: typeof CreditCard; note: string }> = [
    { value: 'card', label: 'Card', icon: CreditCard, note: 'Pay securely by card via Stripe.' },
    { value: 'upi', label: 'UPI', icon: Smartphone, note: 'Order placed; UPI payment confirmed on processing.' },
    { value: 'netbanking', label: 'Netbanking', icon: Landmark, note: 'Order placed; bank payment confirmed on processing.' },
    { value: 'cod', label: 'Cash on Delivery', icon: Banknote, note: 'Pay in cash when your order arrives.' },
];

function blankShipping(customer: { name: string; email: string }): ShippingState {
    return { name: customer.name, email: customer.email, address: '', city: '', postal_code: '', country: '' };
}

function fromSaved(saved: OrderShipping, customer: { name: string; email: string }): ShippingState {
    return {
        name: saved.name ?? customer.name,
        email: saved.email ?? customer.email,
        address: saved.address ?? '',
        city: saved.city ?? '',
        postal_code: saved.postal_code ?? '',
        country: saved.country ?? '',
    };
}

export default function Checkout({ cart, stripeKey, customer, savedAddress }: Props) {
    const stripePromise = useMemo(() => (stripeKey ? loadStripe(stripeKey) : null), [stripeKey]);
    const isDark =
        typeof document !== 'undefined' && document.documentElement.classList.contains('dark');

    const hasSaved = savedAddress !== null;
    const [addressMode, setAddressMode] = useState<'saved' | 'new'>(hasSaved ? 'saved' : 'new');
    const [shipping, setShipping] = useState<ShippingState>(
        savedAddress ? fromSaved(savedAddress, customer) : blankShipping(customer),
    );
    const [step, setStep] = useState<'shipping' | 'payment'>('shipping');
    const [method, setMethod] = useState<PaymentMethod>('card');
    const [clientSecret, setClientSecret] = useState<string | null>(null);
    const [orderId, setOrderId] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const chooseSaved = () => {
        setAddressMode('saved');
        if (savedAddress) setShipping(fromSaved(savedAddress, customer));
    };
    const chooseNew = () => {
        setAddressMode('new');
        setShipping(blankShipping(customer));
    };

    const continueToPayment = (e: FormEvent) => {
        e.preventDefault();
        const complete = FIELDS.every((f) => shipping[f.key].trim() !== '');
        if (!complete) {
            setError('Please complete your shipping details.');
            return;
        }
        setError(null);
        setStep('payment');
    };

    const submitPayment = async () => {
        setSubmitting(true);
        setError(null);
        try {
            const { data } = await axios.post<{ mode: string; clientSecret?: string; orderId: string }>(
                route('checkout.store'),
                { ...shipping, payment_method: method },
            );
            if (data.mode === 'card' && data.clientSecret) {
                setClientSecret(data.clientSecret);
                setOrderId(data.orderId);
            } else {
                router.visit(route('checkout.success', data.orderId));
            }
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

                    {step === 'shipping' && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Shipping details</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {hasSaved && (
                                    <div className="mb-4 grid gap-3 sm:grid-cols-2">
                                        <button
                                            type="button"
                                            onClick={chooseSaved}
                                            className={cn(
                                                'rounded-lg border p-3 text-left text-sm',
                                                addressMode === 'saved' ? 'border-primary ring-1 ring-primary' : 'border-border',
                                            )}
                                        >
                                            <span className="font-medium">Deliver to saved address</span>
                                            <span className="mt-1 block text-xs text-muted-foreground">
                                                {savedAddress?.name}, {savedAddress?.address}, {savedAddress?.city}
                                            </span>
                                        </button>
                                        <button
                                            type="button"
                                            onClick={chooseNew}
                                            className={cn(
                                                'rounded-lg border p-3 text-left text-sm',
                                                addressMode === 'new' ? 'border-primary ring-1 ring-primary' : 'border-border',
                                            )}
                                        >
                                            <span className="font-medium">Use a new address</span>
                                            <span className="mt-1 block text-xs text-muted-foreground">
                                                Enter different shipping details
                                            </span>
                                        </button>
                                    </div>
                                )}

                                <form onSubmit={continueToPayment} className="grid grid-cols-2 gap-4">
                                    {addressMode === 'new' ? (
                                        FIELDS.map((f) => (
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
                                        ))
                                    ) : (
                                        <div className="col-span-2 rounded-md bg-muted/50 p-4 text-sm">
                                            <p className="font-medium text-foreground">{shipping.name}</p>
                                            <p className="text-muted-foreground">{shipping.address}</p>
                                            <p className="text-muted-foreground">
                                                {shipping.city} {shipping.postal_code}, {shipping.country}
                                            </p>
                                        </div>
                                    )}
                                    {error && <p className="col-span-2 text-sm text-destructive">{error}</p>}
                                    <div className="col-span-2">
                                        <Button type="submit" size="lg" className="w-full">
                                            Continue to payment
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    )}

                    {step === 'payment' && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Payment</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-5">
                                <div className="grid gap-2 sm:grid-cols-2">
                                    {METHODS.map((m) => (
                                        <button
                                            key={m.value}
                                            type="button"
                                            onClick={() => {
                                                setMethod(m.value);
                                                setClientSecret(null);
                                            }}
                                            className={cn(
                                                'flex items-start gap-3 rounded-lg border p-3 text-left',
                                                method === m.value ? 'border-primary ring-1 ring-primary' : 'border-border',
                                            )}
                                        >
                                            <m.icon className="mt-0.5 h-5 w-5 text-primary" />
                                            <span>
                                                <span className="block text-sm font-medium">{m.label}</span>
                                                <span className="block text-xs text-muted-foreground">{m.note}</span>
                                            </span>
                                        </button>
                                    ))}
                                </div>

                                {method === 'card' && clientSecret && stripePromise && orderId ? (
                                    <Elements
                                        stripe={stripePromise}
                                        options={{ clientSecret, appearance: { theme: isDark ? 'night' : 'stripe' } }}
                                    >
                                        <PaymentForm orderId={orderId} />
                                    </Elements>
                                ) : (
                                    <>
                                        {method === 'card' && !stripeKey && (
                                            <p className="text-sm text-destructive">
                                                Stripe is not configured.
                                            </p>
                                        )}
                                        {error && <p className="text-sm text-destructive">{error}</p>}
                                        <Button
                                            size="lg"
                                            className="w-full"
                                            disabled={submitting || (method === 'card' && !stripeKey)}
                                            onClick={submitPayment}
                                        >
                                            {submitting
                                                ? 'Processing…'
                                                : method === 'card'
                                                  ? 'Continue to card payment'
                                                  : 'Place order'}
                                        </Button>
                                    </>
                                )}

                                <button
                                    type="button"
                                    onClick={() => setStep('shipping')}
                                    className="text-xs text-muted-foreground hover:text-foreground"
                                >
                                    ← Edit shipping
                                </button>
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
                                        <span className="font-medium">{formatCents(item.line_total_cents)}</span>
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
