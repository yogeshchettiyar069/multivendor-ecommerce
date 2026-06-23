import { Button } from '@/Components/ui/button';
import { PaymentElement, useElements, useStripe } from '@stripe/react-stripe-js';
import { router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

export default function PaymentForm({ orderId }: { orderId: string }) {
    const stripe = useStripe();
    const elements = useElements();
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const pay = async (e: FormEvent) => {
        e.preventDefault();
        if (!stripe || !elements) return;

        setProcessing(true);
        setError(null);

        const { error: stripeError, paymentIntent } = await stripe.confirmPayment({
            elements,
            redirect: 'if_required',
        });

        if (stripeError) {
            setError(stripeError.message ?? 'Payment failed. Please try again.');
            setProcessing(false);
            return;
        }

        if (paymentIntent && paymentIntent.status === 'succeeded') {
            router.visit(route('checkout.success', orderId));
            return;
        }

        setError('Payment did not complete. Please try again.');
        setProcessing(false);
    };

    return (
        <form onSubmit={pay} className="space-y-5">
            <PaymentElement />
            {error && <p className="text-sm text-destructive">{error}</p>}
            <Button type="submit" size="lg" className="w-full" disabled={!stripe || processing}>
                {processing ? 'Processing…' : 'Pay now'}
            </Button>
            <p className="text-center text-xs text-muted-foreground">
                Test card: 4242 4242 4242 4242 · any future expiry · any CVC
            </p>
        </form>
    );
}
